<?php

namespace App\Support\Calendars;

use App\Contracts\Calendar\FrenchRepublicanCalendarAdapter;
use Fisharebest\ExtCalendar\FrenchCalendar;

class FrenchRepublicanExtCalendarAdapter implements FrenchRepublicanCalendarAdapter
{
    private FrenchCalendar $calendar;

    public function __construct(?FrenchCalendar $calendar = null)
    {
        $this->calendar = $calendar ?? new FrenchCalendar;
    }

    public function jdToYmd(int $julianDay): array
    {
        return $this->calendar->jdToYmd($julianDay);
    }

    public function ymdToJd(int $year, int $month, int $day): int
    {
        return $this->calendar->ymdToJd($year, $month, $day);
    }

    public function jdStart(): int
    {
        return $this->calendar->jdStart();
    }

    public function jdEnd(): int
    {
        return $this->calendar->jdEnd();
    }

    public function isLeapYear(int $year): bool
    {
        return $this->calendar->isLeapYear($year);
    }
}
