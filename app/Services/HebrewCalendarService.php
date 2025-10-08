<?php

namespace App\Services;

use Carbon\Carbon;
use Fisharebest\ExtCalendar\JewishCalendar;

class HebrewCalendarService extends CalendarConversionService
{
    private JewishCalendar $jewishCalendar;

    public function __construct()
    {
        $this->jewishCalendar = new JewishCalendar();
    }

    private const HEBREW_MONTH_NAMES = [
        1 => 'Tishrei',
        2 => 'Cheshvan', 
        3 => 'Kislev',
        4 => 'Tevet',
        5 => 'Shevat',
        6 => 'Adar',
        7 => 'Adar II', // Leap year
        8 => 'Nisan',
        9 => 'Iyar',
        10 => 'Sivan',
        11 => 'Tammuz',
        12 => 'Av',
        13 => 'Elul',
    ];

    private const HEBREW_DAY_NAMES = [
        0 => 'Yom Rishon',    // Sunday
        1 => 'Yom Sheni',     // Monday
        2 => 'Yom Shlishi',   // Tuesday
        3 => 'Yom Revi\'i',   // Wednesday
        4 => 'Yom Chamishi',  // Thursday
        5 => 'Yom Shishi',    // Friday
        6 => 'Yom Shabbat',   // Saturday
    ];

    public function fromGregorian(Carbon $date): array
    {
        $date = $this->ensureTimezone($date, 'Asia/Jerusalem');
        
        $jd = \GregorianToJD($date->month, $date->day, $date->year);
        $hebrewDate = \JDToJewish($jd);
        
        list($month, $day, $year) = explode('/', $hebrewDate);
        $month = (int)$month;
        $day = (int)$day;
        $year = (int)$year;

        $dayOfWeek = \jddayofweek($jd, 0);
        $monthName = self::HEBREW_MONTH_NAMES[$month] ?? 'Unknown';
        $dayName = self::HEBREW_DAY_NAMES[$dayOfWeek] ?? 'Unknown';

        // Check for leap year (13 months)
        $isLeapYear = $this->jewishCalendar->isLeapYear($year);

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $monthName,
            'day' => $day,
            'day_of_week' => $dayName,
            'is_leap_year' => $isLeapYear,
            'full_date' => "{$year}/{$month}/{$day}",
            'display' => "{$day} {$monthName} {$year}",
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $year = $dateData['year'] ?? $dateData['hebrew_year'] ?? null;
        $month = $dateData['month'] ?? $dateData['hebrew_month'] ?? null;
        $day = $dateData['day'] ?? $dateData['hebrew_day'] ?? null;

        if (!$year || !$month || !$day) {
            $this->handleValidationError('Invalid Hebrew date components');
        }

        $jd = \JewishToJD($month, $day, $year);
        $gregorian = \JDToGregorian($jd);
        
        if ($gregorian === '0/0/0') {
            $this->handleInvalidDate('Invalid Hebrew date');
        }

        list($month, $day, $year) = explode('/', $gregorian);
        
        return Carbon::create($year, $month, $day, 0, 0, 0, 'Asia/Jerusalem');
    }

    public function getKey(): string
    {
        return 'hebrew';
    }

    public function getName(): string
    {
        return 'Hebrew Calendar';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        if ($year < 1 || $month < 1 || $month > 13 || $day < 1) {
            return false;
        }

        // Check if month exists in this year (leap year handling)
        $isLeapYear = $this->jewishCalendar->isLeapYear($year);
        if ($month === 7 && !$isLeapYear) {
            return false; // Adar II only exists in leap years
        }

        $maxDays = $this->jewishCalendar->daysInMonth($year, $month);
        return $day <= $maxDays;
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        return $this->jewishCalendar->daysInMonth($year, $month);
    }
}
