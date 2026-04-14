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
