<?php

namespace App\Services;

use Carbon\Carbon;

class JulianDayService extends CalendarConversionService
{
    public function fromGregorian(Carbon $date): array
    {
        $date = $this->ensureTimezone($date);
        
        $jd = GregorianToJD($date->month, $date->day, $date->year);
        $mjd = $jd - 2400000.5;
        
        // Calculate Julian DateTime (JD with fractional day)
        $fractionalDay = ($date->hour + $date->minute / 60 + $date->second / 3600) / 24;
        $julianDateTime = $jd + $fractionalDay;

        return [
            'julian_day' => $jd,
            'modified_julian_day' => $mjd,
            'julian_date_time' => $julianDateTime,
            'display' => "JD {$jd}",
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        if (isset($dateData['julian_day'])) {
            $jd = (float) $dateData['julian_day'];
        } elseif (isset($dateData['modified_julian_day'])) {
            $jd = (float) $dateData['modified_julian_day'] + 2400000.5;
        } else {
            $this->handleInvalidDate('No Julian Day or Modified Julian Day provided');
        }

        $gregorian = JDToGregorian($jd);
        if ($gregorian === '0/0/0') {
            $this->handleInvalidDate('Invalid Julian Day number');
        }

        list($month, $day, $year) = explode('/', $gregorian);
        
        // Handle fractional day for time
        $fraction = $jd - floor($jd);
        $totalSeconds = (int) ($fraction * 86400);
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return Carbon::create($year, $month, $day, $hours, $minutes, $seconds, 'UTC');
    }

    public function getKey(): string
    {
        return 'julian';
    }

    public function getName(): string
    {
        return 'Julian Day';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        // Julian Day numbers are valid from JD 0 to JD 9999999
        // This is a simplified validation
        return $year >= 0 && $year <= 9999999;
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        // Julian Day doesn't have months in the traditional sense
        return 0;
    }
}
