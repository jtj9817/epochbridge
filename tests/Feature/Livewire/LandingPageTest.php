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
                return match ($dateString) {
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
                        ],
                        'unix' => 1725148800,
                        'iso8601' => '2024-09-01T00:00:00+00:00',
                    ],
                };
            });

        $mock->shouldReceive('convertAdditional')->andReturn([]);
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
        ->assertSee('Jalali')
        ->assertSee('1403')
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
        'gregorian_date' => '2024-01-01 00:00:00',
    ]);
});

it('does not save conversion for a guest user via saveConversion method', function () {
    Volt::test('landing')
        ->set('dateInput', '2024-01-01')
        ->call('convertDate')
        ->call('saveConversion');

    $this->assertDatabaseCount('saved_conversions', 0);
});
