<?php

namespace App\Services;

use Carbon\Carbon;

class JulianCalendarService extends CalendarConversionService
{
    private const MONTH_NAMES = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    private const DAY_NAMES = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function fromGregorian(Carbon $date): array
    {
        $date = $this->ensureTimezone($date);

        $delta = $this->calculateDeltaDays($date->year, $date->month, $date->day);
        $julianDate = $date->copy()->subDays($delta)->startOfDay();

        $jdJulian = cal_to_jd(CAL_JULIAN, $julianDate->month, $julianDate->day, $julianDate->year);
        $dayOfWeekIndex = jddayofweek($jdJulian, 0);

        return [
            'year' => (int) $julianDate->year,
            'month' => (int) $julianDate->month,
            'month_name' => self::MONTH_NAMES[(int) $julianDate->month] ?? $julianDate->format('F'),
            'day' => (int) $julianDate->day,
            'day_of_week' => self::DAY_NAMES[$dayOfWeekIndex] ?? $julianDate->format('l'),
            'full_date' => sprintf('%d/%02d/%02d', $julianDate->year, $julianDate->month, $julianDate->day),
            'display' => sprintf('%d %s %d', $julianDate->day, self::MONTH_NAMES[(int) $julianDate->month] ?? $julianDate->format('F'), $julianDate->year),
            'difference_days' => $delta,
            'difference_label' => $this->differenceLabel($delta),
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $year = $dateData['year'] ?? null;
        $month = $dateData['month'] ?? null;
        $day = $dateData['day'] ?? null;

        if (! $year || ! $month || ! $day) {
            $this->handleValidationError('Invalid Julian date components');
        }

        $year = (int) $year;
        $month = (int) $month;
        $day = (int) $day;

        if (! $this->validateDate($year, $month, $day)) {
            $this->handleValidationError('Invalid Julian date components');
        }

        $julianDay = cal_to_jd(CAL_JULIAN, $month, $day, $year);
        $gregorian = jdtogregorian($julianDay);

        if ($gregorian === '0/0/0') {
            $this->handleRangeError('Unable to convert Julian date to Gregorian');
        }

        [$gMonth, $gDay, $gYear] = array_map('intval', explode('/', $gregorian));

        return Carbon::create($gYear, $gMonth, $gDay, 0, 0, 0, 'UTC');
    }

    public function getKey(): string
    {
        return 'julian';
    }

    public function getName(): string
    {
        return 'Julian Calendar';
    }

    public function getTags(): array
    {
        return ['historical', 'solar'];
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        if ($month < 1 || $month > 12 || $day < 1) {
            return false;
        }

        return $day <= $this->getDaysInMonth($year, $month);
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        if (in_array($month, [1, 3, 5, 7, 8, 10, 12], true)) {
            return 31;
        }

        if (in_array($month, [4, 6, 9, 11], true)) {
            return 30;
        }

        return $this->isJulianLeapYear($year) ? 29 : 28;
    }

    private function isJulianLeapYear(int $year): bool
    {
        return $year % 4 === 0;
    }

    private function calculateDeltaDays(int $year, int $month, int $day): int
    {
        if ($year > 1582 || ($year === 1582 && ($month > 10 || ($month === 10 && $day >= 15)))) {
            $century = intdiv($year, 100);

            return $century - intdiv($century, 4) - 2;
        }

        return 0;
    }

    private function differenceLabel(int $delta): string
    {
        if ($delta === 0) {
            return 'Aligned with Gregorian';
        }

        return $delta === 1
            ? '1 day behind Gregorian'
            : "{$delta} days behind Gregorian";
    }
}

