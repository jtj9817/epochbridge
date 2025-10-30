<?php

namespace Tests\Feature\Livewire;

use App\Models\SavedConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('authenticated user can update existing note', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->call('updateNote', $conversion->id, 'Updated note');

    expect($conversion->fresh()->note)->toBe('Updated note');
});

test('note length validation enforces 2048 char limit', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    $longNote = str_repeat('a', 2049);

    Volt::test('components.saved-conversions-section')
        ->call('updateNote', $conversion->id, $longNote);

    expect($conversion->fresh()->note)->toBe('Original note')
        ->and(strlen($conversion->fresh()->note))->not()->toBe(2049);
});

test('note within 2048 char limit is accepted', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    $validNote = str_repeat('a', 2048);

    Volt::test('components.saved-conversions-section')
        ->call('updateNote', $conversion->id, $validNote);

    expect($conversion->fresh()->note)->toBe($validNote)
        ->and(strlen($conversion->fresh()->note))->toBe(2048);
});

test('note whitespace is trimmed before saving', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->call('updateNote', $conversion->id, '  Trimmed note  ');

    expect($conversion->fresh()->note)->toBe('Trimmed note');
});

test('computed property refreshes after note update', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    $component = Volt::test('components.saved-conversions-section');
    
    $originalCount = $component->get('savedConversions')->count();
    
    $component->call('updateNote', $conversion->id, 'Updated note');
    
    $updatedConversions = $component->get('savedConversions');
    expect($updatedConversions->count())->toBe($originalCount);
    
    $updatedConversion = $updatedConversions->firstWhere('id', $conversion->id);
    expect($updatedConversion)->not()->toBeNull();
});

test('user cannot update another users conversion note', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user1->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user2);

    Volt::test('components.saved-conversions-section')
        ->call('updateNote', $conversion->id, 'Hacked note');

    expect($conversion->fresh()->note)->toBe('Original note');
});

test('unauthenticated user cannot update note', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    Volt::test('components.saved-conversions-section')
        ->call('updateNote', $conversion->id, 'Guest hack note');

    expect($conversion->fresh()->note)->toBe('Original note');
});

test('note can be updated to empty string', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->call('updateNote', $conversion->id, '');

    expect($conversion->fresh()->note)->toBe('');
});

test('updating non-existent conversion note fails gracefully', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->call('updateNote', 99999, 'Non-existent note');

    $this->assertDatabaseMissing('saved_conversions', [
        'id' => 99999,
    ]);
});

test('note update preserves other conversion fields', function () {
    $user = User::factory()->create();
    $conversionData = [
        'gregorian' => ['full_date' => '2024-01-15'],
        'jalali' => ['full_date' => '1402/10/25'],
        'unix' => 1705276800,
        'iso8601' => '2024-01-15T00:00:00+00:00',
    ];

    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'conversion_data' => $conversionData,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->call('updateNote', $conversion->id, 'Updated note');

    $fresh = $conversion->fresh();
    
    expect($fresh->note)->toBe('Updated note')
        ->and($fresh->original_input)->toBe('2024-01-15')
        ->and($fresh->conversion_data)->toBe($conversionData)
        ->and($fresh->user_id)->toBe($user->id);
});

test('multiline note updates are preserved', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    $multilineNote = "Line 1\nLine 2\nLine 3";

    Volt::test('components.saved-conversions-section')
        ->call('updateNote', $conversion->id, $multilineNote);

    expect($conversion->fresh()->note)->toBe($multilineNote);
});

test('unicode characters in note updates are preserved', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    $unicodeNote = '日本語 العربية Ελληνικά 🎉';

    Volt::test('components.saved-conversions-section')
        ->call('updateNote', $conversion->id, $unicodeNote);

    expect($conversion->fresh()->note)->toBe($unicodeNote);
});
