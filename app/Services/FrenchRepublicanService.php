<?php

namespace App\Services;

use Carbon\Carbon;

class FrenchRepublicanService extends CalendarConversionService
{
    private const FRENCH_MONTH_NAMES = [
        1 => 'Vendémiaire',
        2 => 'Brumaire',
        3 => 'Frimaire',
        4 => 'Nivôse',
        5 => 'Pluviôse',
        6 => 'Ventôse',
        7 => 'Germinal',
        8 => 'Floréal',
        9 => 'Prairial',
        10 => 'Messidor',
        11 => 'Thermidor',
        12 => 'Fructidor',
    ];

    private const FRENCH_DAY_NAMES = [
        0 => 'Primidi',
        1 => 'Duodi',
        2 => 'Tridi',
        3 => 'Quartidi',
        4 => 'Quintidi',
        5 => 'Sextidi',
        6 => 'Septidi',
        7 => 'Octidi',
        8 => 'Nonidi',
        9 => 'Décadi',
    ];

    public function fromGregorian(Carbon $date): array
    {
        $date = $this->ensureTimezone($date, 'Europe/Paris');
        
        $jd = GregorianToJD($date->month, $date->day, $date->year);
        $frenchDate = JDToFrench($jd);
        
        if ($frenchDate === '0/0/0') {
            return ['error' => 'Date outside French Republican calendar range'];
        }

        list($month, $day, $year) = explode('/', $frenchDate);
        $month = (int)$month;
        $day = (int)$day;
        $year = (int)$year;

        $dayOfDecade = ($day - 1) % 10;
        $monthName = self::FRENCH_MONTH_NAMES[$month] ?? 'Unknown';
        $dayName = self::FRENCH_DAY_NAMES[$dayOfDecade] ?? 'Unknown';

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $monthName,
            'day' => $day,
            'day_of_week' => $dayName,
            'full_date' => "{$year}/{$month}/{$day}",
            'display' => "{$day} {$monthName} An {$year}",
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $year = $dateData['year'] ?? $dateData['french_year'] ?? null;
        $month = $dateData['month'] ?? $dateData['french_month'] ?? null;
        $day = $dateData['day'] ?? $dateData['french_day'] ?? null;

        if (!$year || !$month || !$day) {
            $this->handleInvalidDate('Invalid French Republican date components');
        }

        $jd = FrenchToJD($month, $day, $year);
        $gregorian = JDToGregorian($jd);
        
        if ($gregorian === '0/0/0') {
            $this->handleInvalidDate('Invalid French Republican date');
        }

        list($month, $day, $year) = explode('/', $gregorian);
        
        return Carbon::create($year, $month, $day, 0, 0, 0, 'Europe/Paris');
    }

    public function getKey(): string
    {
        return 'french_republican';
    }

    public function getName(): string
    {
        return 'French Republican Calendar';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        if ($year < 1 || $month < 1 || $month > 13 || $day < 1) {
            return false;
        }

        // French Republican calendar has 12 months of 30 days + 5-6 complementary days
        if ($month <= 12) {
            return $day <= 30;
        }

        // Month 13 contains complementary days (5-6 days)
        $isLeapYear = $this->isFrenchLeapYear($year);
        return $day <= ($isLeapYear ? 6 : 5);
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        if ($month <= 12) {
            return 30;
        }

        // Month 13 (complementary days)
        return $this->isFrenchLeapYear($year) ? 6 : 5;
    }

    private function isFrenchLeapYear(int $year): bool
    {
        // French Republican leap year rule (simplified)
        return $year % 4 === 0 && ($year % 100 !== 0 || $year % 400 === 0);
    }
}
