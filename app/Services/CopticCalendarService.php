<?php

namespace App\Services;

use Carbon\Carbon;

class CopticCalendarService extends CalendarConversionService
{
    private const COPTIC_EPOCH = 1825030; // JD for 29 August 284 CE (Julian)

    private const COPTIC_MONTH_NAMES = [
        1 => 'Thout',
        2 => 'Paopi',
        3 => 'Hathor',
        4 => 'Koiak',
        5 => 'Tobi',
        6 => 'Meshir',
        7 => 'Paremhat',
        8 => 'Parmouti',
        9 => 'Pashons',
        10 => 'Paoni',
        11 => 'Epip',
        12 => 'Mesori',
        13 => 'Epagomenai', // Pi Kogi Enavot
    ];

    private const COPTIC_DAY_NAMES = [
        0 => 'Tkyriaka',    // Sunday
        1 => 'Pesnau',      // Monday
        2 => 'Pshoment',    // Tuesday
        3 => 'Peftoou',     // Wednesday
        4 => 'Ptiou',       // Thursday
        5 => 'Psou',        // Friday
        6 => 'Psabbaton',   // Saturday
    ];

    public function fromGregorian(Carbon $date): array
    {
        $date = $this->ensureTimezone($date, 'Africa/Cairo');
        $jd = GregorianToJD($date->month, $date->day, $date->year);

        $days = $jd - self::COPTIC_EPOCH;
        $year = floor((4 * $days + 1463) / 1461);
        
        $jdc = self::COPTIC_EPOCH + 365 * ($year - 1) + floor(($year - 1) / 4);
        $dayOfYear = $jd - $jdc;
        
        $month = floor($dayOfYear / 30) + 1;
        $day = $dayOfYear - (($month - 1) * 30);

        return [
            'year' => (int)$year,
            'month' => (int)$month,
            'month_name' => self::COPTIC_MONTH_NAMES[(int)$month] ?? 'Unknown',
            'day' => (int)$day,
            'day_of_week' => self::COPTIC_DAY_NAMES[jddayofweek($jd, 0)] ?? 'Unknown',
            'full_date' => (int)$year . '/' . (int)$month . '/' . (int)$day,
            'display' => (int)$day . ' ' . (self::COPTIC_MONTH_NAMES[(int)$month] ?? 'Unknown') . ' ' . (int)$year . ' AM',
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $year = $dateData['year'] ?? null;
        $month = $dateData['month'] ?? null;
        $day = $dateData['day'] ?? null;
        
        if (!$year || !$month || !$day) {
            $this->handleInvalidDate('Invalid Coptic date components');
        }

        $jd = self::COPTIC_EPOCH + ($year - 1) * 365 + floor($year / 4) + ($month - 1) * 30 + $day - 1;
        
        $gregorian = JDToGregorian($jd);
        list($m, $d, $y) = explode('/', $gregorian);

        return Carbon::create($y, $m, $d, 0, 0, 0, 'Africa/Cairo');
    }

    public function getKey(): string
    {
        return 'coptic';
    }

    public function getName(): string
    {
        return 'Coptic Calendar';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        if ($year < 1 || $month < 1 || $month > 13 || $day < 1 || $day > 30) {
            return false;
        }
        if ($month <= 12) {
            return $day <= 30;
        }
        // Month 13 (Epagomenai)
        $isLeap = ($year % 4) === 3;
        return $day <= ($isLeap ? 6 : 5);
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        if ($month <= 12) {
            return 30;
        }
        $isLeap = ($year % 4) === 3;
        return $isLeap ? 6 : 5;
    }
}
