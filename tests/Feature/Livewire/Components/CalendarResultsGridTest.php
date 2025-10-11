<?php

namespace Tests\Feature\Livewire\Components;

use Livewire\Volt\Volt;

beforeEach(function () {
    $this->baseResults = [
        'gregorian' => [
            'name' => 'Gregorian',
            'full_date' => '2024-01-01',
            'tags' => ['solar'],
            'year' => 2024,
            'month_name' => 'January',
            'day' => 1,
            'day_of_week' => 'Monday',
        ],
        'jalali' => [
            'name' => 'Jalali',
            'full_date' => '1402/10/11',
            'tags' => ['solar'],
            'year' => 1402,
            'month_name' => 'Dey',
            'month_name_en' => 'Dey',
            'day' => 11,
            'day_of_week' => 'Doshanbeh',
            'day_of_week_en' => 'Monday',
        ],
        'islamic' => [
            'name' => 'Islamic',
            'full_date' => '1445/06/19',
            'tags' => ['lunar'],
            'year' => 1445,
            'month_name' => 'Jumada al-Thani',
            'day' => 19,
            'day_of_week' => 'Yawm al-Ithnayn',
        ],
        'unix' => 1704067200,
        'iso8601' => '2024-01-01T00:00:00+00:00',
        'julian-day' => 2460310,
        'modified-julian-day' => 60309.5,
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
        ->assertDontSee('Jalali')
        // Scalar values should still appear since they don't have tags
        ->assertSee('1704067200') // Unix timestamp
        ->assertSee('2024-01-01T00:00:00+00:00'); // ISO8601
});

it('shows loading skeletons when additional calendars are not loaded', function () {
    Volt::test('components.calendar-results-grid', [
        'conversionResults' => $this->baseResults,
        'additionalCalendarsLoaded' => false,
    ])
        ->assertSeeHtmlInOrder(['<div', 'animate-pulse']);
});
