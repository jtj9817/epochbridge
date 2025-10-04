<?php

namespace Tests\Feature\Livewire;

use App\Services\DateTimeConverterService;
use Illuminate\Support\Carbon;
use Livewire\Volt\Volt;

it('renders the landing page component', function () {
    $this->get('/')->assertSeeLivewire('landing');
});

it('can convert a valid date', function () {
    Volt::test('landing')
        ->set('dateInput', '2024-09-01')
        ->call('convertDate')
        ->assertSet('conversionResults.gregorian', '2024-09-01')
        ->assertSet('conversionResults.jalali', '1403-06-11')
        ->assertSee('1403-06-11')
        ->assertHasNoErrors();
});

it('shows a validation error for an invalid date format', function () {
    Volt::test('landing')
        ->set('dateInput', 'not-a-valid-date')
        ->call('convertDate')
        ->assertHasErrors(['dateInput' => 'date_format']);
});

it('shows a validation error for a required date field', function () {
    Volt::test('landing')
        ->set('dateInput', '')
        ->call('convertDate')
        ->assertHasErrors(['dateInput' => 'required']);
});

it('can set the date to today', function () {
    $today = Carbon::today()->format('Y-m-d');
    
    Volt::test('landing')
        ->call('setToday')
        ->assertSet('dateInput', $today);
});

it('can set the date to yesterday', function () {
    $yesterday = Carbon::yesterday()->format('Y-m-d');

    Volt::test('landing')
        ->call('setYesterday')
        ->assertSet('dateInput', $yesterday);
});

it('can set the date to tomorrow', function () {
    $tomorrow = Carbon::tomorrow()->format('Y-m-d');

    Volt::test('landing')
        ->call('setTomorrow')
        ->assertSet('dateInput', $tomorrow);
});

it('shows a generic error message if the service fails unexpectedly', function () {
    // Mock the DateTimeConverterService to throw an exception
    $this->mock(DateTimeConverterService::class, function ($mock) {
        $mock->shouldReceive('convert')->andThrow(new \InvalidArgumentException('Service failed unexpectedly.'));
    });

    Volt::test('landing')
        ->set('dateInput', '2024-01-15') // A valid date to pass validation
        ->call('convertDate')
        ->assertSet('conversionError', 'An unexpected error occurred. Please try again.')
        ->assertSee('An unexpected error occurred. Please try again.')
        ->assertHasNoErrors(); // No validation errors should be present
});
