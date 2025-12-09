<?php

namespace Tests\Feature\Livewire\Components;

use App\Models\SavedConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('save-note-update event routes to updateNote for authenticated users', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->dispatch('save-note-update', [
            'conversionId' => $conversion->id,
            'note' => 'Updated via event',
            'isGuest' => false,
        ]);

    expect($conversion->fresh()->note)->toBe('Updated via event');
});

test('save-note-update event does not update for guest flag true', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->dispatch('save-note-update', [
            'conversionId' => $conversion->id,
            'note' => 'Guest attempted update',
            'isGuest' => true,
        ]);

    expect($conversion->fresh()->note)->toBe('Original note');
});

test('save-note-update event passes correct conversion ID and note text', function () {
    $user = User::factory()->create();
    $conversion1 = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Conversion 1 note',
    ]);

    $conversion2 = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Conversion 2 note',
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->dispatch('save-note-update', [
            'conversionId' => $conversion2->id,
            'note' => 'Only conversion 2 updated',
            'isGuest' => false,
        ]);

    expect($conversion1->fresh()->note)->toBe('Conversion 1 note')
        ->and($conversion2->fresh()->note)->toBe('Only conversion 2 updated');
});

test('save-note-update event handler in saved-conversions-section processes correctly', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    $component = Volt::test('components.saved-conversions-section');

    $component->dispatch('save-note-update', [
        'conversionId' => $conversion->id,
        'note' => 'Event handler processed',
        'isGuest' => false,
    ]);

    $conversions = $component->get('savedConversions');
    $updatedConversion = $conversions->firstWhere('id', $conversion->id);

    expect($conversion->fresh()->note)->toBe('Event handler processed');
});

test('save-note-update event with invalid conversion ID fails gracefully', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->dispatch('save-note-update', [
            'conversionId' => 99999,
            'note' => 'Invalid ID',
            'isGuest' => false,
        ]);

    $this->assertDatabaseMissing('saved_conversions', [
        'id' => 99999,
    ]);
});

test('save-note-update event respects user authorization', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $conversion = SavedConversion::factory()->create([
        'user_id' => $user1->id,
        'note' => 'User 1 note',
    ]);

    $this->actingAs($user2);

    Volt::test('components.saved-conversions-section')
        ->dispatch('save-note-update', [
            'conversionId' => $conversion->id,
            'note' => 'User 2 unauthorized update',
            'isGuest' => false,
        ]);

    expect($conversion->fresh()->note)->toBe('User 1 note');
});

test('save-note-update event validates note length', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    $longNote = str_repeat('a', 2049);

    Volt::test('components.saved-conversions-section')
        ->dispatch('save-note-update', [
            'conversionId' => $conversion->id,
            'note' => $longNote,
            'isGuest' => false,
        ]);

    expect($conversion->fresh()->note)->toBe('Original note');
});

test('save-note-update event trims note before saving', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->dispatch('save-note-update', [
            'conversionId' => $conversion->id,
            'note' => '  Trimmed note  ',
            'isGuest' => false,
        ]);

    expect($conversion->fresh()->note)->toBe('Trimmed note');
});

test('save-note-update event allows empty note', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->dispatch('save-note-update', [
            'conversionId' => $conversion->id,
            'note' => '',
            'isGuest' => false,
        ]);

    expect($conversion->fresh()->note)->toBe('');
});
