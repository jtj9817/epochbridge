<?php

namespace Tests\Unit\Services;

use App\Services\Chinese\ChineseLunisolarCalendar;
use App\Services\Chinese\LunarMonth;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ChineseLunisolarCalendarTest extends TestCase
{
    public function test_months_for_2023_include_leap_second_month(): void
    {
        $calendar = new ChineseLunisolarCalendar;

        $months = $calendar->monthsForChineseYear(2023);

        $this->assertCount(13, $months);

        $leapMonths = array_values(array_filter(
            $months,
            static fn (LunarMonth $month): bool => $month->isLeap
        ));

        $this->assertCount(1, $leapMonths);

        $leapMonth = $leapMonths[0];

        $this->assertSame(2, $leapMonth->number);
        $this->assertSame('2023-03-22', $leapMonth->startDate->toDateString());
        $this->assertSame(29, $leapMonth->length());
        $this->assertFalse($leapMonth->hasMajorSolarTerm);
    }

    public function test_lunar_month_contains_boundary_dates(): void
    {
        $calendar = new ChineseLunisolarCalendar;

        $months = $calendar->monthsForChineseYear(2023);
        $firstMonth = $months[0];

        $newYear = CarbonImmutable::create(2023, 1, 22, 0, 0, 0, 'Asia/Shanghai');
        $eve = CarbonImmutable::create(2023, 1, 21, 23, 0, 0, 'Asia/Shanghai');

        $this->assertTrue($firstMonth->contains($newYear));
        $this->assertFalse($firstMonth->contains($eve));
    }
}
