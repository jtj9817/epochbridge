<?php

namespace App\Services;

use Carbon\Carbon;
use Fisharebest\ExtCalendar\FrenchCalendar;
use InvalidArgumentException;

class FrenchRepublicanService extends CalendarConversionService
{
    private FrenchCalendar $calendar;

    public function __construct()
    {
        $this->calendar = new FrenchCalendar;
    }

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
        13 => 'Sansculottides',
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

    private const COMPLEMENTARY_DAY_NAMES = [
        1 => 'Jour de la Vertu',
        2 => 'Jour du Génie',
        3 => 'Jour du Travail',
        4 => 'Jour de l\'Opinion',
        5 => 'Jour des Récompenses',
        6 => 'Jour de la Révolution',
    ];

    public function fromGregorian(Carbon $date): array
    {
        $date = $this->ensureTimezone($date, 'Europe/Paris');

        $jd = gregoriantojd($date->month, $date->day, $date->year);

        if ($jd < $this->calendar->jdStart() || $jd > $this->calendar->jdEnd()) {
            $this->handleRangeError('Date outside French Republican calendar range');
        }

        try {
            [$year, $month, $day] = $this->calendar->jdToYmd($jd);
        } catch (InvalidArgumentException $exception) {
            $this->handleCalculationError('Unable to convert date to French Republican calendar', $exception);
        }

        $dayOfDecade = $month === 13 ? null : (($day - 1) % 10);
        $monthName = self::FRENCH_MONTH_NAMES[$month] ?? 'Unknown';
        $dayName = $month === 13
            ? (self::COMPLEMENTARY_DAY_NAMES[$day] ?? 'Complementary Day')
            : (self::FRENCH_DAY_NAMES[$dayOfDecade] ?? 'Unknown');
        $isLeapYear = $this->calendar->isLeapYear($year);

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $monthName,
            'day' => $day,
            'day_of_week' => $dayName,
            'is_leap_year' => $isLeapYear,
            'decade_day' => $dayOfDecade !== null ? $dayOfDecade + 1 : null,
            'full_date' => "{$year}/{$month}/{$day}",
            'display' => "{$day} {$monthName} An {$year}",
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $year = $dateData['year'] ?? $dateData['french_year'] ?? null;
        $month = $dateData['month'] ?? $dateData['french_month'] ?? null;
        $day = $dateData['day'] ?? $dateData['french_day'] ?? null;

        if (! $year || ! $month || ! $day) {
            $this->handleValidationError('Invalid French Republican date components');
        }

        $year = (int) $year;
        $month = (int) $month;
        $day = (int) $day;

        if (! $this->validateDate($year, $month, $day)) {
            $this->handleValidationError('Invalid French Republican date components');
        }

        try {
            $jd = $this->calendar->ymdToJd($year, $month, $day);
        } catch (InvalidArgumentException $exception) {
            $this->handleInvalidDate('Invalid French Republican date');
        }

        if ($jd < $this->calendar->jdStart() || $jd > $this->calendar->jdEnd()) {
            $this->handleRangeError('Date outside French Republican calendar range');
        }

        $gregorian = jdtogregorian($jd);
        [$gMonth, $gDay, $gYear] = explode('/', $gregorian);

        return Carbon::create($gYear, $gMonth, $gDay, 0, 0, 0, 'Europe/Paris');
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
        // Leap years were historically observed in Years III, VII, and XI (year % 4 === 3)
        return $this->calendar->isLeapYear($year);
    }

    public function getTags(): array
    {
        return ['historical', 'solar'];
    }
}
