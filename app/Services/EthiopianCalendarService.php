<?php

namespace App\Services;

use Carbon\Carbon;

class EthiopianCalendarService extends CalendarConversionService
{
    private const ETHIOPIAN_EPOCH = 1724220; // JD for August 29, 8 CE (Julian)

    private const ETHIOPIAN_MONTH_NAMES = [
        1 => 'Mäskäräm',
        2 => 'Ṭəqəmt',
        3 => 'Ḫədar',
        4 => 'Taḫśaś',
        5 => 'Ṭərr',
        6 => 'Yäkatit',
        7 => 'Mägabit',
        8 => 'Miyazya',
        9 => 'Gənbo',
        10 => 'Säne',
        11 => 'Ḥamle',
        12 => 'Nähase',
        13 => 'Ṗagʷəmen',
    ];

    private const ETHIOPIAN_DAY_NAMES = [
        0 => 'Ihud',        // Sunday
        1 => 'Säño',        // Monday
        2 => 'Maksäño',     // Tuesday
        3 => 'Räbu',        // Wednesday
        4 => 'Hamus',       // Thursday
        5 => 'Arb',         // Friday
        6 => 'Qədame',      // Saturday
    ];
    
    public function fromGregorian(Carbon $date): array
    {
        $date = $this->ensureTimezone($date, 'Africa/Addis_Ababa');
        $jd = GregorianToJD($date->month, $date->day, $date->year);

        $days = $jd - self::ETHIOPIAN_EPOCH;
        $year = floor((4 * $days + 1463) / 1461);
        
        $jdc = self::ETHIOPIAN_EPOCH + 365 * ($year - 1) + floor(($year - 1) / 4);
        $dayOfYear = $jd - $jdc;
        
        $month = floor($dayOfYear / 30) + 1;
        $day = $dayOfYear - (($month - 1) * 30);

        return [
            'year' => (int)$year,
            'month' => (int)$month,
            'month_name' => self::ETHIOPIAN_MONTH_NAMES[(int)$month] ?? 'Unknown',
            'day' => (int)$day,
            'day_of_week' => self::ETHIOPIAN_DAY_NAMES[jddayofweek($jd, 0)] ?? 'Unknown',
            'full_date' => (int)$year . '/' . (int)$month . '/' . (int)$day,
            'display' => (int)$day . ' ' . (self::ETHIOPIAN_MONTH_NAMES[(int)$month] ?? 'Unknown') . ' ' . (int)$year,
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $year = $dateData['year'] ?? null;
        $month = $dateData['month'] ?? null;
        $day = $dateData['day'] ?? null;

        if (!$year || !$month || !$day) {
            $this->handleInvalidDate('Invalid Ethiopian date components');
        }

        $jd = self::ETHIOPIAN_EPOCH + ($year - 1) * 365 + floor($year / 4) + ($month - 1) * 30 + $day - 1;

        $gregorian = JDToGregorian($jd);
        list($m, $d, $y) = explode('/', $gregorian);

        return Carbon::create($y, $m, $d, 0, 0, 0, 'Africa/Addis_Ababa');
    }

    public function getKey(): string
    {
        return 'ethiopian';
    }

    public function getName(): string
    {
        return 'Ethiopian Calendar';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        if ($year < 1 || $month < 1 || $month > 13 || $day < 1 || $day > 30) {
            return false;
        }
        if ($month <= 12) {
            return $day <= 30;
        }
        // Month 13 (Ṗagʷəmen)
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

    public function getTags(): array
    {
        return ['historical', 'solar'];
    }
}
