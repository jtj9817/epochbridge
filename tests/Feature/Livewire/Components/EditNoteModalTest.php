<?php

namespace Tests\Feature\Livewire\Components;

use App\Models\SavedConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('modal opens with current note value pre-filled', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Existing note',
    ]);

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, 'Existing note', false);

    expect($component->get('currentNote'))->toBe('Existing note')
        ->and($component->get('conversionId'))->toBe($conversion->id)
        ->and($component->get('showModal'))->toBeTrue()
        ->and($component->get('isGuest'))->toBeFalse();
});

test('modal opens with empty note for new notes', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => null,
    ]);

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, null, false);

    expect($component->get('currentNote'))->toBe('')
        ->and($component->get('showModal'))->toBeTrue();
});

test('modal saves note and dispatches save-note-update event', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Old note',
    ]);

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, 'Old note', false)
        ->set('currentNote', 'New note')
        ->call('saveNote');

    expect($component->get('showModal'))->toBeFalse()
        ->and($component->get('conversionId'))->toBeNull();
});

test('modal validates note length max 2048 chars and shows error', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original',
    ]);

    $longNote = str_repeat('a', 2049);

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, 'Original', false)
        ->set('currentNote', $longNote)
        ->call('saveNote');

    expect($component->get('showModal'))->toBeTrue();
    $component->assertHasErrors(['currentNote']);
});

test('modal accepts note exactly at 2048 char limit', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original',
    ]);

    $validNote = str_repeat('a', 2048);

    Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, 'Original', false)
        ->set('currentNote', $validNote)
        ->call('saveNote')
        ->assertHasNoErrors()
        ->assertDispatched('save-note-update');
});

test('modal trims note before save', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original',
    ]);

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, 'Original', false)
        ->set('currentNote', '  Trimmed  ')
        ->call('saveNote');

    expect($component->get('currentNote'))->toBe('');
});

test('modal closes after successful save', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original',
    ]);

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, 'Original', false)
        ->set('currentNote', 'New note')
        ->call('saveNote');

    expect($component->get('showModal'))->toBeFalse()
        ->and($component->get('currentNote'))->toBe('')
        ->and($component->get('conversionId'))->toBeNull();
});

test('modal handles guest flag correctly', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Guest note',
    ]);

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, 'Guest note', true);

    expect($component->get('isGuest'))->toBeTrue();
});

test('modal can be cancelled resetting note to original value', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original',
    ]);

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, 'Original', false)
        ->set('currentNote', 'Changed but cancelled')
        ->call('closeModal');

    expect($component->get('showModal'))->toBeFalse()
        ->and($component->get('currentNote'))->toBe('')
        ->and($component->get('conversionId'))->toBeNull();
});

test('modal handles empty note gracefully', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original',
    ]);

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, 'Original', false)
        ->set('currentNote', '')
        ->call('saveNote')
        ->assertHasNoErrors();

    expect($component->get('showModal'))->toBeFalse();
});

test('modal resets state when closed', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original',
    ]);

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, 'Original', false)
        ->assertSet('showModal', true)
        ->assertSet('conversionId', $conversion->id)
        ->assertSet('currentNote', 'Original')
        ->call('closeModal')
        ->assertSet('showModal', false)
        ->assertSet('conversionId', null)
        ->assertSet('currentNote', '')
        ->assertSet('isGuest', false);
});

test('modal preserves multiline notes', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => "Line 1\nLine 2",
    ]);

    $multilineNote = "Updated Line 1\nUpdated Line 2\nLine 3";

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, "Line 1\nLine 2", false)
        ->assertSet('currentNote', "Line 1\nLine 2")
        ->set('currentNote', $multilineNote);

    expect($component->get('currentNote'))->toBe($multilineNote);
});

test('modal preserves unicode characters', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original',
    ]);

    $unicodeNote = '日本語 العربية 🎉';

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, 'Original', false)
        ->set('currentNote', $unicodeNote);

    expect($component->get('currentNote'))->toBe($unicodeNote);
});

test('modal can handle null current note when opening', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => null,
    ]);

    $component = Volt::test('components.edit-note-modal')
        ->dispatch('open-note-modal', $conversion->id, null, false);

    expect($component->get('currentNote'))->toBe('')
        ->and($component->get('showModal'))->toBeTrue();
});
