<?php

namespace App\Contracts\Calendar;

interface FrenchRepublicanCalendarAdapter
{
    /**
     * Convert a Julian Day Number to French Republican year, month, day.
     *
     * @return array{0:int,1:int,2:int}
     */
    public function jdToYmd(int $julianDay): array;

    /**
     * Convert a French Republican date to a Julian Day Number.
     */
    public function ymdToJd(int $year, int $month, int $day): int;

    /**
     * Get the lowest Julian Day Number supported by this adapter.
     */
    public function jdStart(): int;

    /**
     * Get the highest Julian Day Number supported by this adapter.
     */
    public function jdEnd(): int;

    /**
     * Determine whether the provided year is a leap year.
     */
    public function isLeapYear(int $year): bool;
}
