<?php

namespace App\Services;

use Carbon\Carbon;

class AssyrianCalendarService extends CalendarConversionService
{
    private const ASSYRIAN_MONTH_NAMES = [
        1 => 'Nisan',
        2 => 'Ayar',
        3 => 'Siman',
        4 => 'Tammuz',
        5 => 'Ab',
        6 => 'Elul',
        7 => 'Tishrin I',
        8 => 'Tishrin II',
        9 => 'Kanon I',
        10 => 'Kanon II',
        11 => 'Shbat',
        12 => 'Adar',
    ];
    
    public function fromGregorian(Carbon $date): array
    {
        // The modern Assyrian calendar is a solar calendar based on the Gregorian.
        // It starts from 4750 BC.
        $assyrianYear = $date->year + 4750;
        $month = $date->month;
        $day = $date->day;

        return [
            'year' => $assyrianYear,
            'month' => $month,
            'month_name' => self::ASSYRIAN_MONTH_NAMES[$month] ?? 'Unknown',
            'day' => $day,
            'full_date' => "{$assyrianYear}/{$month}/{$day}",
            'display' => "{$day} " . (self::ASSYRIAN_MONTH_NAMES[$month] ?? 'Unknown') . " {$assyrianYear}",
            'disclaimer' => 'Based on the modern Assyrian calendar (solar). Ancient Assyrian calendar was lunisolar, similar to Babylonian.',
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $year = $dateData['year'] ?? null;
        if ($year === null) {
            $this->handleValidationError('Assyrian year is required.');
        }

        $gregorianYear = $year - 4750;

        return Carbon::create(
            $gregorianYear,
            $dateData['month'] ?? 1,
            $dateData['day'] ?? 1,
            0, 0, 0, 'Asia/Baghdad'
        );
    }

    public function getKey(): string
    {
        return 'assyrian';
    }

    public function getName(): string
    {
        return 'Assyrian Calendar';
    }
    
    public function validateDate(int $year, int $month, int $day): bool
    {
        // Since it's based on Gregorian, validation is similar
        return checkdate($month, $day, $year - 4750);
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        return cal_days_in_month(CAL_GREGORIAN, $month, $year - 4750);
    }
}
