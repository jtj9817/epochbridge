<?php

declare(strict_types=1);

use App\Models\SavedConversion;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('requires authentication for index', function () {
    $response = $this->getJson(route('frontend.saved-conversions.index'));

    $response->assertUnauthorized();
});

it('lists saved conversions for authenticated user', function () {
    SavedConversion::factory()->count(3)->create(['user_id' => $this->user->id]);
    SavedConversion::factory()->count(2)->create(); // Other user's conversions

    $response = $this->actingAs($this->user)
        ->getJson(route('frontend.saved-conversions.index'));

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'original_input',
                    'note',
                    'conversion_data',
                    'uses_legacy_fallback',
                    'created_at',
                ],
            ],
        ]);
});

it('creates a saved conversion', function () {
    $conversionData = [
        'gregorian' => [
            'key' => 'gregorian',
            'name' => 'Gregorian',
            'full_date' => '2024-01-15',
        ],
        'unix' => 1705276800,
    ];

    $response = $this->actingAs($this->user)
        ->postJson(route('frontend.saved-conversions.store'), [
            'original_input' => '2024-01-15 (gregorian)',
            'conversion_data' => $conversionData,
            'note' => 'Test note',
        ]);

    $response->assertCreated()
        ->assertJsonPath('original_input', '2024-01-15 (gregorian)')
        ->assertJsonPath('note', 'Test note');

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $this->user->id,
        'original_input' => '2024-01-15 (gregorian)',
        'note' => 'Test note',
    ]);
});

it('requires authentication to create', function () {
    $response = $this->postJson(route('frontend.saved-conversions.store'), [
        'original_input' => 'test',
        'conversion_data' => [],
    ]);

    $response->assertUnauthorized();
});

it('deletes a saved conversion', function () {
    $conversion = SavedConversion::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson(route('frontend.saved-conversions.destroy', $conversion));

    $response->assertSuccessful()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('saved_conversions', ['id' => $conversion->id]);
});

it('cannot delete another user conversion', function () {
    $otherUser = User::factory()->create();
    $conversion = SavedConversion::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson(route('frontend.saved-conversions.destroy', $conversion));

    $response->assertForbidden();
});

it('updates a note on a saved conversion', function () {
    $conversion = SavedConversion::factory()->create([
        'user_id' => $this->user->id,
        'note' => 'Original note',
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson(route('frontend.saved-conversions.note.update', $conversion), [
            'note' => 'Updated note',
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('note', 'Updated note');

    $this->assertDatabaseHas('saved_conversions', [
        'id' => $conversion->id,
        'note' => 'Updated note',
    ]);
});

it('cannot update note on another user conversion', function () {
    $otherUser = User::factory()->create();
    $conversion = SavedConversion::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($this->user)
        ->patchJson(route('frontend.saved-conversions.note.update', $conversion), [
            'note' => 'Hacked note',
        ]);

    $response->assertForbidden();
});

it('normalizes legacy fallback correctly', function () {
    // Create a legacy-style record without conversion_data
    $conversion = SavedConversion::factory()->create([
        'user_id' => $this->user->id,
        'conversion_data' => null,
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/25',
        'unix_timestamp' => '1705276800',
        'iso8601_string' => '2024-01-15T00:00:00Z',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('frontend.saved-conversions.index'));

    $response->assertSuccessful();

    $item = collect($response->json('data'))->firstWhere('id', $conversion->id);

    expect($item['uses_legacy_fallback'])->toBeTrue();
    expect($item['conversion_data'])->toHaveKey('gregorian');
    expect($item['conversion_data'])->toHaveKey('unix');
});

// Migrate endpoint tests

it('requires authentication to migrate guest conversions', function () {
    $response = $this->postJson(route('frontend.saved-conversions.migrate'), [
        'items' => [],
    ]);

    $response->assertUnauthorized();
});

it('migrates guest conversions to authenticated user', function () {
    $guestItems = [
        [
            'original_input' => '2024-01-15',
            'conversion_data' => [
                'gregorian' => ['full_date' => '2024-01-15', 'name' => 'Gregorian'],
                'jalali' => ['full_date' => '1402/10/25', 'name' => 'Jalali'],
            ],
            'note' => 'Guest note',
            'created_at' => '2024-01-15T10:00:00Z',
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson(route('frontend.saved-conversions.migrate'), [
            'items' => $guestItems,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonPath('migrated_count', 1);

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $this->user->id,
        'original_input' => '2024-01-15',
        'note' => 'Guest note',
    ]);
});

it('skips duplicates during migration', function () {
    SavedConversion::factory()->create([
        'user_id' => $this->user->id,
        'original_input' => '2024-01-15',
    ]);

    $guestItems = [
        [
            'original_input' => '2024-01-15',
            'conversion_data' => [
                'gregorian' => ['full_date' => '2024-01-15'],
            ],
        ],
        [
            'original_input' => '2024-02-20',
            'conversion_data' => [
                'gregorian' => ['full_date' => '2024-02-20'],
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson(route('frontend.saved-conversions.migrate'), [
            'items' => $guestItems,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('migrated_count', 1);

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $this->user->id,
        'original_input' => '2024-02-20',
    ]);
});

it('validates migrate payload structure', function () {
    $response = $this->actingAs($this->user)
        ->postJson(route('frontend.saved-conversions.migrate'), [
            'items' => [
                ['missing_fields' => true],
            ],
        ]);

    $response->assertUnprocessable();
});

it('rejects migration with more than 3 items', function () {
    $items = array_map(fn ($i) => [
        'original_input' => "2024-01-0{$i}",
        'conversion_data' => ['gregorian' => ['full_date' => "2024-01-0{$i}"]],
    ], range(1, 4));

    $response = $this->actingAs($this->user)
        ->postJson(route('frontend.saved-conversions.migrate'), [
            'items' => $items,
        ]);

    $response->assertUnprocessable();
});

it('migrates guest conversions with empty notes', function () {
    $guestItems = [
        [
            'original_input' => '2024-01-15',
            'conversion_data' => [
                'gregorian' => ['full_date' => '2024-01-15'],
            ],
            'note' => '',
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson(route('frontend.saved-conversions.migrate'), [
            'items' => $guestItems,
        ]);

    $response->assertSuccessful();

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $this->user->id,
        'original_input' => '2024-01-15',
        'note' => '',
    ]);
});

it('preserves created_at from guest data during migration', function () {
    $guestCreatedAt = '2024-01-10T08:30:00Z';

    $guestItems = [
        [
            'original_input' => '2024-01-15',
            'conversion_data' => [
                'gregorian' => ['full_date' => '2024-01-15'],
            ],
            'created_at' => $guestCreatedAt,
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson(route('frontend.saved-conversions.migrate'), [
            'items' => $guestItems,
        ]);

    $response->assertSuccessful();

    $conversion = SavedConversion::where('user_id', $this->user->id)
        ->where('original_input', '2024-01-15')
        ->first();

    expect($conversion)->not->toBeNull();
    expect($conversion->created_at->toIso8601String())->not->toBeNull();
});

it('validates note max length on store', function () {
    $response = $this->actingAs($this->user)
        ->postJson(route('frontend.saved-conversions.store'), [
            'original_input' => '2024-01-15',
            'conversion_data' => ['gregorian' => ['full_date' => '2024-01-15']],
            'note' => str_repeat('a', 1001),
        ]);

    $response->assertUnprocessable();
});

it('validates note max length on note update', function () {
    $conversion = SavedConversion::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson(route('frontend.saved-conversions.note.update', $conversion), [
            'note' => str_repeat('a', 1001),
        ]);

    $response->assertUnprocessable();
});
