<?php

namespace Tests\Unit\Services;

use App\Contracts\Calendar\FrenchRepublicanCalendarAdapter;
use App\Exceptions\CalendarRangeException;
use App\Services\FrenchRepublicanService;
use App\Support\Calendars\FrenchRepublicanExtCalendarAdapter;
use Carbon\Carbon;
use Tests\TestCase;

class FrenchRepublicanServiceTest extends TestCase
{
    /**
     * @dataProvider roundTripDataProvider
     */
    public function test_round_trip_conversions(string $gregorianDate, array $expected): void
    {
        $service = $this->makeService();
        $gregorian = Carbon::createFromFormat('Y-m-d', $gregorianDate, 'Europe/Paris');

        $french = $service->fromGregorian($gregorian);

        $this->assertSame($expected['year'], $french['year']);
        $this->assertSame($expected['month'], $french['month']);
        $this->assertSame($expected['day'], $french['day']);
        $this->assertSame($expected['month_name'], $french['month_name']);
        $this->assertSame($expected['day_of_week'], $french['day_of_week']);
        $this->assertSame($expected['decade_day'], $french['decade_day']);
        $this->assertSame($expected['full_date'], $french['full_date']);
        $this->assertSame($expected['display'], $french['display']);

        $roundTrip = $service->toGregorian([
            'year' => $french['year'],
            'month' => $french['month'],
            'day' => $french['day'],
        ]);

        $this->assertEquals($gregorianDate, $roundTrip->format('Y-m-d'));
    }

    public static function roundTripDataProvider(): array
    {
        return [
            'epoch_start' => [
                '1792-09-22',
                [
                    'year' => 1,
                    'month' => 1,
                    'day' => 1,
                    'month_name' => 'Vendémiaire',
                    'day_of_week' => 'Primidi',
                    'decade_day' => 1,
                    'full_date' => '1/1/1',
                    'display' => '1 Vendémiaire An 1',
                ],
            ],
            'napoleon_coup' => [
                '1799-11-09',
                [
                    'year' => 8,
                    'month' => 2,
                    'day' => 18,
                    'month_name' => 'Brumaire',
                    'day_of_week' => 'Octidi',
                    'decade_day' => 8,
                    'full_date' => '8/2/18',
                    'display' => '18 Brumaire An 8',
                ],
            ],
            'calendar_final_day' => [
                '1805-12-31',
                [
                    'year' => 14,
                    'month' => 4,
                    'day' => 10,
                    'month_name' => 'Nivôse',
                    'day_of_week' => 'Décadi',
                    'decade_day' => 10,
                    'full_date' => '14/4/10',
                    'display' => '10 Nivôse An 14',
                ],
            ],
            'leap_year_revolution_day' => [
                '1799-09-22',
                [
                    'year' => 7,
                    'month' => 13,
                    'day' => 6,
                    'month_name' => 'Sansculottides',
                    'day_of_week' => 'Jour de la Révolution',
                    'decade_day' => null,
                    'full_date' => '7/13/6',
                    'display' => '6 Sansculottides An 7',
                ],
            ],
        ];
    }

    public function test_year_start_dates_match_fixture(): void
    {
        $service = $this->makeService();
        $yearStarts = require base_path('tests/__fixtures/french_republican_year_starts.php');

        foreach ($yearStarts as $year => $gregorianDate) {
            $result = $service->fromGregorian(
                Carbon::createFromFormat('Y-m-d', $gregorianDate, 'Europe/Paris')
            );

            $this->assertSame((int) $year, $result['year']);
            $this->assertSame(1, $result['month']);
            $this->assertSame(1, $result['day']);
            $this->assertSame('Vendémiaire', $result['month_name']);
            $this->assertSame('Primidi', $result['day_of_week']);
        }
    }

    public function test_validate_date_accepts_valid_combinations(): void
    {
        $service = $this->makeService();

        $this->assertTrue($service->validateDate(1, 1, 30));
        $this->assertTrue($service->validateDate(3, 13, 6)); // Leap year complementary day
        $this->assertTrue($service->validateDate(4, 13, 5));
    }

    /**
     * @dataProvider invalidDateProvider
     */
    public function test_validate_date_rejects_invalid_combinations(int $year, int $month, int $day): void
    {
        $service = $this->makeService();

        $this->assertFalse($service->validateDate($year, $month, $day));
    }

    public static function invalidDateProvider(): array
    {
        return [
            'month_zero' => [1, 0, 1],
            'month_too_high' => [1, 14, 1],
            'day_too_high_regular_month' => [1, 2, 31],
            'complementary_day_overflow_non_leap' => [4, 13, 6],
            'day_zero' => [1, 1, 0],
        ];
    }

    public function test_rejects_gregorian_date_outside_supported_range(): void
    {
        $this->expectException(CalendarRangeException::class);
        $service = $this->makeService();

        $service->fromGregorian(Carbon::create(1806, 1, 1, 0, 0, 0, 'Europe/Paris'));
    }

    public function test_rejects_french_date_outside_supported_range(): void
    {
        $this->expectException(CalendarRangeException::class);
        $service = $this->makeService();

        $service->toGregorian([
            'year' => 15,
            'month' => 1,
            'day' => 1,
        ]);
    }

    public function test_reuses_cached_results_for_identical_inputs(): void
    {
        $adapter = $this->makeRecordingAdapter();
        $service = new FrenchRepublicanService($adapter);
        $date = Carbon::create(1799, 11, 9, 0, 0, 0, 'Europe/Paris');

        $service->fromGregorian($date);
        $service->fromGregorian($date);

        $this->assertSame(1, $adapter->jdToYmdCalls);

        $payload = ['year' => 8, 'month' => 2, 'day' => 18];
        $service->toGregorian($payload);
        $service->toGregorian($payload);

        $this->assertSame(1, $adapter->ymdToJdCalls);
    }

    public function test_paris_offset_can_be_disabled(): void
    {
        $date = Carbon::create(1793, 9, 21, 23, 55, 0, 'Europe/Paris');

        $serviceWithOffset = $this->makeService();
        $withOffset = $serviceWithOffset->fromGregorian($date);

        $this->assertSame(2, $withOffset['year']);
        $this->assertSame(1, $withOffset['month']);
        $this->assertSame(1, $withOffset['day']);

        config()->set('calendars.french_republican.apply_paris_offset', false);

        $serviceWithoutOffset = $this->makeService();
        $withoutOffset = $serviceWithoutOffset->fromGregorian($date);

        $this->assertSame(1, $withoutOffset['year']);
        $this->assertSame(13, $withoutOffset['month']);
        $this->assertSame(5, $withoutOffset['day']);
        $this->assertSame('Sansculottides', $withoutOffset['month_name']);
        $this->assertSame('Jour des Récompenses', $withoutOffset['day_of_week']);

        config()->set('calendars.french_republican.apply_paris_offset', true);
    }

    private function makeService(?FrenchRepublicanCalendarAdapter $adapter = null): FrenchRepublicanService
    {
        return new FrenchRepublicanService($adapter ?? app(FrenchRepublicanCalendarAdapter::class));
    }

    private function makeRecordingAdapter(): RecordingFrenchRepublicanAdapter
    {
        return new RecordingFrenchRepublicanAdapter;
    }
}

class RecordingFrenchRepublicanAdapter extends FrenchRepublicanExtCalendarAdapter
{
    public int $jdToYmdCalls = 0;

    public int $ymdToJdCalls = 0;

    public function jdToYmd(int $julianDay): array
    {
        $this->jdToYmdCalls++;

        return parent::jdToYmd($julianDay);
    }

    public function ymdToJd(int $year, int $month, int $day): int
    {
        $this->ymdToJdCalls++;

        return parent::ymdToJd($year, $month, $day);
    }
}
