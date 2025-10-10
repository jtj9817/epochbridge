<?php

namespace Tests\Feature\Livewire\Components;

use App\Models\SavedConversion;
use App\Models\User;
use Livewire\Volt\Volt;

it('renders saved conversions for an authenticated user', function () {
    $user = User::factory()->create();
    SavedConversion::factory()->for($user)->create(['original_input' => '2024-01-01']);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->assertSee('2024-01-01');
});

it('can delete a conversion for an authenticated user', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->for($user)->create();

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->call('deleteConversion', $conversion->id);

    $this->assertDatabaseMissing('saved_conversions', ['id' => $conversion->id]);
});

it('refreshes conversions when conversion-saved event is dispatched', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Volt::test('components.saved-conversions-section');
    expect($component->get('savedConversions'))->toHaveCount(0);

    SavedConversion::factory()->for($user)->create();

    $component->dispatch('conversion-saved')
        ->assertSet('savedConversions', fn ($conversions) => $conversions->count() === 1);
});
