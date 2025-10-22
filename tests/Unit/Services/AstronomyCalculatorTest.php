<?php

namespace Tests\Unit\Services;

use App\Services\Chinese\AstronomyCalculator;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class AstronomyCalculatorTest extends TestCase
{
    public function test_new_moon_for_k298_matches_february_2024_conjunction(): void
    {
        $astronomy = new AstronomyCalculator;

        $jde = $astronomy->newMoon(298);
        $beijing = $astronomy->jdeToCarbon($jde, 'Asia/Shanghai');

        $expected = CarbonImmutable::create(2024, 2, 10, 6, 59, 0, 'Asia/Shanghai');

        $this->assertSame('2024-02-10', $beijing->toDateString());
        $this->assertLessThanOrEqual(60, $beijing->diffInSeconds($expected));
    }

    public function test_major_solar_terms_between_consecutive_winter_solstices(): void
    {
        $astronomy = new AstronomyCalculator;

        $start = $astronomy->winterSolstice(2023);
        $end = $astronomy->winterSolstice(2024);

        $terms = $astronomy->majorSolarTermsBetween($start, $end);

        $this->assertCount(13, $terms);

        $firstTerm = $astronomy->jdeToCarbon($terms[0], 'Asia/Shanghai');
        $lastTerm = $astronomy->jdeToCarbon($terms[array_key_last($terms)], 'Asia/Shanghai');

        $this->assertSame('2023-12-22', $firstTerm->toDateString());
        $this->assertTrue($lastTerm->lessThan($astronomy->jdeToCarbon($end, 'Asia/Shanghai')));
    }

    public function test_julian_day_conversion_round_trip(): void
    {
        $astronomy = new AstronomyCalculator;

        $timestamp = CarbonImmutable::create(2024, 2, 10, 6, 59, 1, 'UTC');
        $jd = $astronomy->julianDayFromCarbon($timestamp);
        $roundTrip = $astronomy->julianDayToCarbon($jd);

        $this->assertSame($timestamp->toIso8601String(), $roundTrip->toIso8601String());
    }
}
