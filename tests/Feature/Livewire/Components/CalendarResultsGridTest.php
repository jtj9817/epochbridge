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
    $component = Volt::test('components.calendar-results-grid', ['conversionResults' => $this->baseResults])
        ->set('searchTerm', 'Jalali');

    $component
        ->assertSet('filteredResults', function ($results) {
            expect(array_keys($results))->toBe(['jalali']);

            return true;
        })
        ->assertSee('Jalali')
        ->assertDontSeeText('Gregorian');
});

it('filters results by type', function () {
    // 'islamic' has 'lunar' tag. 'gregorian' and 'jalali' do not.
    $component = Volt::test('components.calendar-results-grid', ['conversionResults' => $this->baseResults])
        ->set('selectedTypes', ['lunar']);

    $component
        ->assertSet('filteredResults', function ($results) {
            expect(array_keys($results))->toBe(['islamic']);

            return true;
        })
        ->assertSee('Islamic')
        ->assertDontSeeText('Gregorian')
        ->assertDontSeeText('Jalali')
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

it('renders french republican calendar details with display format', function () {
    $mockData = [
        'french_republican' => [
            'name' => 'French Republican',
            'full_date' => '232/12/16',
            'display' => '16 Fructidor An 232',
            'month_name' => 'Fructidor',
            'day' => 16,
            'day_of_week' => 'Quartidi',
            'year' => 232,
            'tags' => ['historical', 'solar'],
        ],
    ];

    Volt::test('components.calendar-results-grid', ['conversionResults' => $mockData])
        ->assertSee('French Republican')
        ->assertSee('16 Fructidor An 232')
        ->assertSee('Quartidi')
        ->assertSee('Fructidor');
});

it('renders french republican calendar with paris offset disabled', function () {
    $mockData = [
        'french_republican' => [
            'name' => 'French Republican',
            'full_date' => '232/12/15',
            'display' => '15 Fructidor An 232',
            'month_name' => 'Fructidor',
            'day' => 15,
            'day_of_week' => 'Tridi',
            'year' => 232,
            'tags' => ['historical', 'solar'],
        ],
    ];

    Volt::test('components.calendar-results-grid', ['conversionResults' => $mockData])
        ->assertSee('French Republican')
        ->assertSee('15 Fructidor An 232')
        ->assertSee('Tridi');
});

it('renders additional calendar data when loaded', function () {
    $baseData = [
        'gregorian' => [
            'name' => 'Gregorian',
            'full_date' => '2024-09-01',
            'tags' => ['solar'],
            'year' => 2024,
            'month_name' => 'September',
            'day' => 1,
            'day_of_week' => 'Sunday',
        ],
    ];

    $additionalData = [
        'julian' => [
            'name' => 'Julian Calendar',
            'year' => 2024,
            'month' => 12,
            'month_name' => 'December',
            'day' => 19,
            'day_of_week' => 'Thursday',
            'full_date' => '2024/12/19',
            'difference_label' => '13 days behind Gregorian',
        ],
    ];

    Volt::test('components.calendar-results-grid', [
        'conversionResults' => $baseData,
        'additionalConversionResults' => $additionalData,
        'additionalCalendarsLoaded' => true,
    ])
        ->assertSee('Julian Calendar')
        ->assertSee('Difference:')
        ->assertSee('13 days behind Gregorian');
});
