<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use Livewire\Volt\Volt;

it('renders the landing page component', function () {
    $this->get('/')->assertSeeLivewire('landing');
});

it('can convert a valid date', function () {
    Volt::test('landing')
        ->set('dateInput', '2024-09-01')
        ->call('convertDate')
        ->assertSet('conversionResults.gregorian.full_date', '2024-09-01')
        ->assertSet('conversionResults.jalali.full_date', '1403/06/11')
        ->assertSee('1403/06/11')
        ->assertSet('conversionError', '');
});

it('shows an error message for an invalid date format', function () {
    Volt::test('landing')
        ->set('dateInput', 'not-a-valid-date')
        ->call('convertDate')
        ->assertSet('conversionError', 'Invalid date format provided for the selected input type.');
});

it('shows an error message for an empty date field', function () {
    Volt::test('landing')
        ->set('dateInput', '')
        ->call('convertDate')
        ->assertSet('conversionError', 'Input date cannot be empty.');
});

it('updates dateInput when date-selected event is dispatched', function () {
    $today = now()->format('Y-m-d');
    Volt::test('landing')
        ->dispatch('date-selected', date: $today)
        ->assertSet('dateInput', $today);
});

it('can save a conversion for an authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('landing')
        ->set('dateInput', '2024-01-01')
        ->call('convertDate')
        ->assertSet('conversionError', '')
        ->call('saveConversion')
        ->assertDispatched('conversion-saved');

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'original_input' => '2024-01-01',
        'gregorian_date' => '2024-01-01',
    ]);
});

it('does not save conversion for a guest user via saveConversion method', function () {
    Volt::test('landing')
        ->set('dateInput', '2024-01-01')
        ->call('convertDate')
        ->call('saveConversion');

    $this->assertDatabaseCount('saved_conversions', 0);
});
