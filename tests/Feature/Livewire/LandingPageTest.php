<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use App\Services\DateTimeConverterService;
use Livewire\Volt\Volt;
use Mockery;
use Mockery\MockInterface;

beforeEach(function () {
    $this->mock(DateTimeConverterService::class, function (MockInterface $mock) {
        $mock->shouldReceive('convert')
            ->andReturnUsing(function (string $dateString) {
                $baseData = match ($dateString) {
                    'not-a-valid-date' => throw new \InvalidArgumentException('Invalid date format provided for the selected input type.'),
                    '' => throw new \InvalidArgumentException('Input date cannot be empty.'),
                    '2024-01-01' => [
                        'gregorian' => [
                            'full_date' => '2024-01-01',
                            'name' => 'Gregorian',
                            'month_name' => 'January',
                            'day' => 1,
                            'day_of_week' => 'Monday',
                            'year' => 2024,
                        ],
                        'jalali' => [
                            'full_date' => '1402/10/11',
                            'name' => 'Jalali',
                            'month_name' => 'Dey',
                            'month_name_en' => 'Dey',
                            'day' => 11,
                            'day_of_week' => 'Doshanbeh',
                            'day_of_week_en' => 'Monday',
                            'year' => 1402,
                        ],
                        'unix' => 1704067200,
                        'iso8601' => '2024-01-01T00:00:00+00:00',
                    ],
                    default => [
                        'gregorian' => [
                            'full_date' => '2024-09-01',
                            'name' => 'Gregorian',
                            'month_name' => 'September',
                            'day' => 1,
                            'day_of_week' => 'Sunday',
                            'year' => 2024,
                        ],
                        'jalali' => [
                            'full_date' => '1403/06/11',
                            'name' => 'Jalali',
                            'month_name' => 'Shahrivar',
                            'month_name_en' => 'Shahrivar',
                            'day' => 11,
                            'day_of_week' => 'Yekshanbeh',
                            'day_of_week_en' => 'Sunday',
                            'year' => 1403,
                            'tags' => ['solar'],
                        ],
                        'unix' => 1725148800,
                        'iso8601' => '2024-09-01T00:00:00+00:00',
                    ],
                };

                $frenchData = config('calendars.french_republican.apply_paris_offset', true)
                    ? [
                        'name' => 'French Republican',
                        'full_date' => '232/12/16',
                        'display' => '16 Fructidor An 232',
                        'tags' => ['historical', 'solar'],
                        'year' => 232,
                        'month_name' => 'Fructidor',
                        'day' => 16,
                        'day_of_week' => 'Quartidi',
                    ]
                    : [
                        'name' => 'French Republican',
                        'full_date' => '232/12/15',
                        'display' => '15 Fructidor An 232',
                        'tags' => ['historical', 'solar'],
                        'year' => 232,
                        'month_name' => 'Fructidor',
                        'day' => 15,
                        'day_of_week' => 'Tridi',
                    ];

                return array_merge($baseData, [
                    'julian-day' => 2460310,
                    'modified-julian-day' => 60309.5,
                    'hebrew' => [
                        'name' => 'Hebrew',
                        'full_date' => '5784/10/19',
                        'tags' => ['modern', 'lunisolar'],
                        'year' => 5784,
                        'month_name' => 'Tevet',
                        'day' => 19,
                        'day_of_week' => 'Yom Sheni',
                    ],
                    'islamic' => [
                        'name' => 'Islamic',
                        'full_date' => '1445/06/19',
                        'tags' => ['modern', 'lunar'],
                        'year' => 1445,
                        'month_name' => 'Jumada al-Thani',
                        'day' => 19,
                        'day_of_week' => 'Yawm al-Ithnayn',
                    ],
                    'french_republican' => $frenchData,
                    'coptic' => [
                        'name' => 'Coptic',
                        'full_date' => '1740/04/22',
                        'tags' => ['historical', 'solar'],
                        'year' => 1740,
                        'month_name' => 'Tobi',
                        'day' => 22,
                        'day_of_week' => 'Pesnau',
                    ],
                    'ethiopian' => [
                        'name' => 'Ethiopian',
                        'full_date' => '2016/04/22',
                        'tags' => ['historical', 'solar'],
                        'year' => 2016,
                        'month_name' => 'Ṭərr',
                        'day' => 22,
                        'day_of_week' => 'Maksäño',
                    ],
                ]);
            });

        $mock->shouldReceive('convertAdditional')->andReturn([
            'julian' => [
                'year' => 2024,
                'month' => 12,
                'month_name' => 'December',
                'day' => 19,
                'day_of_week' => 'Thursday',
                'full_date' => '2024/12/19',
                'difference_label' => '13 days behind Gregorian',
            ],
        ]);

        $mock->shouldReceive('getFullConversionResults')
            ->andReturnUsing(function (string $dateString, string $inputType = 'gregorian') {
                return match ($dateString) {
                    '2024-01-01' => [
                        'gregorian' => [
                            'full_date' => '2024-01-01',
                            'name' => 'Gregorian',
                            'month_name' => 'January',
                            'day' => 1,
                            'day_of_week' => 'Monday',
                            'year' => 2024,
                        ],
                        'jalali' => [
                            'full_date' => '1402/10/11',
                            'name' => 'Jalali',
                            'month_name' => 'Dey',
                            'month_name_en' => 'Dey',
                            'day' => 11,
                            'day_of_week' => 'Doshanbeh',
                            'day_of_week_en' => 'Monday',
                            'year' => 1402,
                        ],
                        'unix' => 1704067200,
                        'iso8601' => '2024-01-01T00:00:00+00:00',
                    ],
                    default => [
                        'gregorian' => [
                            'full_date' => '2024-09-01',
                            'name' => 'Gregorian',
                            'month_name' => 'September',
                            'day' => 1,
                            'day_of_week' => 'Sunday',
                            'year' => 2024,
                        ],
                        'jalali' => [
                            'full_date' => '1403/06/11',
                            'name' => 'Jalali',
                            'month_name' => 'Shahrivar',
                            'month_name_en' => 'Shahrivar',
                            'day' => 11,
                            'day_of_week' => 'Yekshanbeh',
                            'day_of_week_en' => 'Sunday',
                            'year' => 1403,
                            'tags' => ['solar'],
                        ],
                        'unix' => 1725148800,
                        'iso8601' => '2024-09-01T00:00:00+00:00',
                    ],
                };
            });
    });
});

afterEach(function () {
    Mockery::close();
});

it('renders the landing page component', function () {
    $this->get('/')->assertSeeLivewire('landing');
});

it('can convert a valid date', function () {
    Volt::test('landing')
        ->set('dateInput', '2024-09-01')
        ->call('convertDate')
        ->assertSet('conversionResults.gregorian.full_date', '2024-09-01')
        ->assertSet('conversionResults.jalali.full_date', '1403/06/11')
        ->assertSet('conversionResults.jalali.year', 1403)
        ->assertSet('conversionError', '');
});

it('sets conversion results state after converting a date', function () {
    Volt::test('landing')
        ->set('dateInput', '2024-09-01')
        ->call('convertDate')
        ->assertSet('conversionResults.gregorian.full_date', '2024-09-01')
        ->assertSet('conversionResults.jalali.full_date', '1403/06/11')
        ->assertSet('conversionResults.french_republican.full_date', '232/12/16')
        ->assertSet('conversionResults.hebrew.full_date', '5784/10/19')
        ->assertSet('conversionResults.islamic.full_date', '1445/06/19')
        ->assertSet('conversionResults.coptic.full_date', '1740/04/22')
        ->assertSet('conversionResults.ethiopian.full_date', '2016/04/22')
        ->assertSet('conversionResults.unix', 1725148800)
        ->assertSet('conversionResults.iso8601', '2024-09-01T00:00:00+00:00')
        ->assertSet('conversionError', '');
});

it('sets french republican calendar data in state', function () {
    Volt::test('landing')
        ->set('dateInput', '2024-09-01')
        ->call('convertDate')
        ->assertSet('conversionResults.french_republican.display', '16 Fructidor An 232')
        ->assertSet('conversionResults.french_republican.day_of_week', 'Quartidi')
        ->assertSet('conversionResults.french_republican.month_name', 'Fructidor')
        ->assertSet('conversionResults.french_republican.year', 232);
});

it('reflects paris offset toggle in conversion state', function () {
    config()->set('calendars.french_republican.apply_paris_offset', false);

    Volt::test('landing')
        ->set('dateInput', '2024-09-01')
        ->call('convertDate')
        ->assertSet('conversionResults.french_republican.display', '15 Fructidor An 232')
        ->assertSet('conversionResults.french_republican.day_of_week', 'Tridi')
        ->assertSet('conversionResults.french_republican.day', 15);

    config()->set('calendars.french_republican.apply_paris_offset', true);
});

it('loads additional julian calendar data when event dispatched', function () {
    Volt::test('landing')
        ->set('dateInput', '2024-09-01')
        ->call('convertDate')
        ->dispatch('load-additional-calendars')
        ->assertSet('additionalCalendarsLoaded', true)
        ->assertSet('additionalConversionResults.julian.full_date', '2024/12/19')
        ->assertSet('additionalConversionResults.julian.difference_label', '13 days behind Gregorian');
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

it('synchronizes search filters via filters-updated event', function () {
    Volt::test('landing')
        ->dispatch('filters-updated', searchTerm: 'Jalali', selectedTypes: ['Solar', 'solar', ''])
        ->assertSet('searchTerm', 'Jalali')
        ->assertSet('selectedTypes', ['solar']);
});

it('can save a conversion for an authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('landing')
        ->set('dateInput', '2024-01-01')
        ->call('convertDate')
        ->assertSet('conversionError', '')
        ->dispatch('save-conversion')
        ->assertDispatched('conversion-saved');

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'original_input' => '2024-01-01',
        'gregorian_date' => '2024-01-01 00:00:00',
    ]);
});

it('does not save conversion for a guest user via save-conversion event', function () {
    Volt::test('landing')
        ->set('dateInput', '2024-01-01')
        ->call('convertDate')
        ->dispatch('save-conversion');

    $this->assertDatabaseCount('saved_conversions', 0);
});

it('dispatches success toast when authenticated user saves conversion', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Volt::test('landing')
        ->set('dateInput', '2024-01-01')
        ->call('convertDate')
        ->assertSet('conversionError', '')
        ->dispatch('save-conversion');

    // Check that conversion-saved event is dispatched (existing functionality)
    $component->assertDispatched('conversion-saved');
    
    // Check that JavaScript is dispatched to show toast (browser event)
    // Note: $this->js() doesn't create a Livewire event, so we can't assert it directly
    // This is tested via browser tests instead
});

it('does not dispatch conversion-saved when authenticated user saves duplicate conversion', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Volt::test('landing')
        ->set('dateInput', '2024-01-01')
        ->call('convertDate')
        ->dispatch('save-conversion');

    $component->assertDispatched('conversion-saved');

    $component->dispatch('save-conversion')
        ->assertNotDispatched('conversion-saved');

    $this->assertDatabaseCount('saved_conversions', 1);
});
