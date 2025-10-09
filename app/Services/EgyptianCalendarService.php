<?php

namespace App\Services;

use Carbon\Carbon;

class EgyptianCalendarService extends CalendarConversionService
{
    private const EGYPTIAN_EPOCH = 1448638; // JD for Feb 26, 747 BCE (Nabonassar era)

    private const EGYPTIAN_MONTH_NAMES = [
        1 => 'Thoth',
        2 => 'Phaophi',
        3 => 'Athyr',
        4 => 'Choiak',
        5 => 'Tybi',
        6 => 'Mechir',
        7 => 'Phamenoth',
        8 => 'Pharmuthi',
        9 => 'Pachon',
        10 => 'Payni',
        11 => 'Epiphi',
        12 => 'Mesore',
        13 => 'Epagomenal Days',
    ];

    public function fromGregorian(Carbon $date): array
    {
        $date = $this->ensureTimezone($date, 'Africa/Cairo');
        $jd = GregorianToJD($date->month, $date->day, $date->year);

        $daysSinceEpoch = $jd - self::EGYPTIAN_EPOCH;
        $year = floor($daysSinceEpoch / 365) + 1;
        
        $dayOfYear = $daysSinceEpoch % 365;
        
        $month = floor($dayOfYear / 30) + 1;
        $day = ($dayOfYear % 30) + 1;

        return [
            'year' => (int)$year,
            'month' => (int)$month,
            'month_name' => self::EGYPTIAN_MONTH_NAMES[(int)$month] ?? 'Unknown',
            'day' => (int)$day,
            'full_date' => (int)$year . '/' . (int)$month . '/' . (int)$day,
            'display' => (int)$day . ' ' . (self::EGYPTIAN_MONTH_NAMES[(int)$month] ?? 'Unknown') . ' ' . (int)$year,
            'disclaimer' => 'Based on the 365-day Egyptian civil calendar (no leap years). Year is relative to the Nabonassar era (747 BCE).',
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $year = $dateData['year'] ?? null;
        $month = $dateData['month'] ?? null;
        $day = $dateData['day'] ?? null;

        if (!$year || !$month || !$day) {
            $this->handleInvalidDate('Invalid Egyptian date components');
        }

        $days = ($year - 1) * 365 + ($month - 1) * 30 + ($day - 1);
        $jd = self::EGYPTIAN_EPOCH + $days;

        $gregorian = JDToGregorian($jd);
        list($m, $d, $y) = explode('/', $gregorian);

        return Carbon::create($y, $m, $d, 0, 0, 0, 'Africa/Cairo');
    }

    public function getKey(): string
    {
        return 'egyptian';
    }

    public function getName(): string
    {
        return 'Egyptian Civil Calendar';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        if ($year < 1 || $month < 1 || $month > 13 || $day < 1 || $day > 30) {
            return false;
        }
        if ($month <= 12) {
            return $day <= 30;
        }
        // Month 13 (Epagomenal Days)
        return $day <= 5;
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        return ($month <= 12) ? 30 : 5;
    }

    public function getTags(): array
    {
        return ['ancient', 'solar'];
    }
}
