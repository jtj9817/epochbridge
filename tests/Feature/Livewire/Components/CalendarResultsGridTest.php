<?php

namespace Tests\Feature\Livewire\Components;

use App\Services\DateTimeConverterService;
use Livewire\Volt\Volt;

it('renders results grid with data', function () {
    $service = new DateTimeConverterService;
    $results = $service->convert('2024-01-01', 'gregorian');

    Volt::test('components.calendar-results-grid', ['conversionResults' => $results])
        ->assertSee('Gregorian')
        ->assertSee('Jalali')
        ->assertSee('Unix');
});

it('shows an error message when passed', function () {
    Volt::test('components.calendar-results-grid', ['conversionError' => 'Test Error'])
        ->assertSee('Test Error');
});

it('filters results by search term', function () {
    $service = new DateTimeConverterService;
    $results = $service->convert('2024-01-01', 'gregorian');

    Volt::test('components.calendar-results-grid', ['conversionResults' => $results])
        ->set('searchTerm', 'Jalali')
        ->assertSee('Jalali')
        ->assertDontSee('Gregorian');
});

it('filters results by type', function () {
    $service = new DateTimeConverterService;
    $results = $service->convert('2024-01-01', 'gregorian');

    // 'islamic' has 'lunar' tag. 'gregorian' does not.
    Volt::test('components.calendar-results-grid', ['conversionResults' => $results])
        ->set('selectedTypes', ['lunar'])
        ->assertSee('Islamic')
        ->assertDontSee('Gregorian')
        ->assertDontSee('Jalali');
});

it('shows loading skeletons when additional calendars are not loaded', function () {
    $service = new DateTimeConverterService;
    $results = $service->convert('2024-01-01', 'gregorian');

    Volt::test('components.calendar-results-grid', [
        'conversionResults' => $results,
        'additionalCalendarsLoaded' => false,
    ])
        ->assertSeeHtmlInOrder(['<div', 'animate-pulse']);
});
