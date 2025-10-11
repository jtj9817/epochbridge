<?php

namespace Tests\Feature\Livewire\Components;

use Livewire\Volt\Volt;

beforeEach(function () {
    $this->baseResults = [
        'gregorian' => [
            'name' => 'Gregorian',
            'full_date' => '2024-01-01',
            'tags' => ['solar'],
        ],
        'jalali' => [
            'name' => 'Jalali',
            'full_date' => '1402/10/11',
            'tags' => ['solar'],
        ],
        'islamic' => [
            'name' => 'Islamic',
            'full_date' => '1445/06/01',
            'tags' => ['lunar'],
        ],
        'unix' => 1704067200,
        'iso8601' => '2024-01-01T00:00:00+00:00',
    ];
});

it('renders results grid with data', function () {
    Volt::test('components.calendar-results-grid', ['conversionResults' => $this->baseResults])
        ->assertSee('Gregorian')
        ->assertSee('Jalali')
        ->assertSee('Unix');
});

it('shows an error message when passed', function () {
    Volt::test('components.calendar-results-grid', ['conversionError' => 'Test Error'])
        ->assertSee('Test Error');
});

it('filters results by search term', function () {
    Volt::test('components.calendar-results-grid', ['conversionResults' => $this->baseResults])
        ->set('searchTerm', 'Jalali')
        ->assertSee('Jalali')
        ->assertDontSee('Gregorian');
});

it('filters results by type', function () {
    // 'islamic' has 'lunar' tag. 'gregorian' does not.
    Volt::test('components.calendar-results-grid', ['conversionResults' => $this->baseResults])
        ->set('selectedTypes', ['lunar'])
        ->assertSee('Islamic')
        ->assertDontSee('Gregorian')
        ->assertDontSee('Jalali');
});

it('shows loading skeletons when additional calendars are not loaded', function () {
    Volt::test('components.calendar-results-grid', [
        'conversionResults' => $this->baseResults,
        'additionalCalendarsLoaded' => false,
    ])
        ->assertSeeHtmlInOrder(['<div', 'animate-pulse']);
});
