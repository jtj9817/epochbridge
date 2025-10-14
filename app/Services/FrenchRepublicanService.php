<?php

namespace App\Services;

use App\Contracts\Calendar\FrenchRepublicanCalendarAdapter;
use Carbon\Carbon;
use InvalidArgumentException;

class FrenchRepublicanService extends CalendarConversionService
{
    private FrenchRepublicanCalendarAdapter $calendar;

    /**
     * @var array<int, array{0:int,1:int,2:int}>
     */
    private array $jdToYmdCache = [];

    /**
     * @var array<string, int>
     */
    private array $ymdToJdCache = [];

    private const FRENCH_EPOCH_JD = 2375840;

    public function __construct(FrenchRepublicanCalendarAdapter $calendar)
    {
        $this->calendar = $calendar;
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

        $adjustedDate = $this->applyParisMeanSolarOffset($date);
        $jd = gregoriantojd($adjustedDate->month, $adjustedDate->day, $adjustedDate->year);

        $jdStart = $this->calendar->jdStart();
        $jdEnd = $this->calendar->jdEnd();
        $prolepticEnabled = $this->prolepticEnabled();
        $useFallback = false;

        if ($jd < $jdStart || $jd > $jdEnd) {
            if (! $prolepticEnabled || $jd < self::FRENCH_EPOCH_JD) {
                $this->handleRangeError('Date outside French Republican calendar range');
            }

            $useFallback = true;
        }

        if (isset($this->jdToYmdCache[$jd])) {
            [$year, $month, $day] = $this->jdToYmdCache[$jd];
        } else {
            if ($useFallback) {
                [$year, $month, $day] = $this->fallbackJdToYmd($jd);
            } else {
                try {
                    [$year, $month, $day] = $this->calendar->jdToYmd($jd);
                } catch (InvalidArgumentException $exception) {
                    $this->handleCalculationError('Unable to convert date to French Republican calendar', $exception);
                }
            }

            $this->jdToYmdCache[$jd] = [$year, $month, $day];
        }

        $dayOfDecade = $month === 13 ? null : (($day - 1) % 10);
        $monthName = self::FRENCH_MONTH_NAMES[$month] ?? 'Unknown';
        $dayName = $month === 13
            ? (self::COMPLEMENTARY_DAY_NAMES[$day] ?? 'Complementary Day')
            : (self::FRENCH_DAY_NAMES[$dayOfDecade] ?? 'Unknown');
        $isLeapYear = $this->isFrenchLeapYear($year);

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

        $ymdKey = "{$year}-{$month}-{$day}";

        $jdStart = $this->calendar->jdStart();
        $jdEnd = $this->calendar->jdEnd();
        $prolepticEnabled = $this->prolepticEnabled();

        if (isset($this->ymdToJdCache[$ymdKey])) {
            $jd = $this->ymdToJdCache[$ymdKey];
        } else {
            try {
                $jd = $this->calendar->ymdToJd($year, $month, $day);
            } catch (InvalidArgumentException $exception) {
                if (! $prolepticEnabled) {
                    $this->handleInvalidDate('Invalid French Republican date');
                }

                $jd = $this->fallbackYmdToJd($year, $month, $day);
            }

            if ($prolepticEnabled && ($jd < $jdStart || $jd > $jdEnd)) {
                $jd = $this->fallbackYmdToJd($year, $month, $day);
            } elseif ($jd < $jdStart || $jd > $jdEnd) {
                $this->handleRangeError('Date outside French Republican calendar range');
            }

            $this->ymdToJdCache[$ymdKey] = $jd;
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
        if ($year <= 14) {
            return $this->calendar->isLeapYear($year);
        }

        return $this->isRommeLeapYear($year);
    }

    private function prolepticEnabled(): bool
    {
        return (bool) config('calendars.french_republican.allow_proleptic', false);
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function fallbackJdToYmd(int $julianDay): array
    {
        if ($julianDay < self::FRENCH_EPOCH_JD) {
            $this->handleRangeError('Date outside French Republican calendar range');
        }

        $low = 1;
        $high = max(1, intdiv($julianDay - self::FRENCH_EPOCH_JD, 365) + 2);
        $year = 1;

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $start = $this->fallbackYearStartJd($mid);

            if ($start <= $julianDay) {
                $year = $mid;
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        $yearStart = $this->fallbackYearStartJd($year);
        $dayOfYear = $julianDay - $yearStart;

        if ($dayOfYear < 360) {
            $month = intdiv($dayOfYear, 30) + 1;
            $day = ($dayOfYear % 30) + 1;
        } else {
            $month = 13;
            $day = ($dayOfYear - 360) + 1;
        }

        return [$year, $month, $day];
    }

    private function fallbackYearStartJd(int $year): int
    {
        if ($year < 1) {
            $this->handleRangeError('Date outside French Republican calendar range');
        }

        $yearsBefore = $year - 1;
        $leapYears = $this->rommeLeapYearsCount($yearsBefore);

        return self::FRENCH_EPOCH_JD + ($yearsBefore * 365) + $leapYears;
    }

    private function fallbackYmdToJd(int $year, int $month, int $day): int
    {
        $yearStart = $this->fallbackYearStartJd($year);

        if ($month <= 12) {
            return $yearStart + (($month - 1) * 30) + ($day - 1);
        }

        $maxComplementary = $this->isRommeLeapYear($year) ? 6 : 5;

        if ($day > $maxComplementary) {
            $this->handleValidationError('Invalid French Republican date components');
        }

        return $yearStart + 360 + ($day - 1);
    }

    private function applyParisMeanSolarOffset(Carbon $date): Carbon
    {
        if (! config('calendars.french_republican.apply_paris_offset', true)) {
            return $date;
        }

        return $date->copy()->addSeconds(561);
    }

    public function getTags(): array
    {
        return ['historical', 'solar'];
    }

    private function isRommeLeapYear(int $year): bool
    {
        if ($year % 4000 === 0) {
            return false;
        }

        if ($year % 400 === 0) {
            return true;
        }

        if ($year % 100 === 0) {
            return false;
        }

        return $year % 4 === 0;
    }

    private function rommeLeapYearsCount(int $years): int
    {
        if ($years <= 0) {
            return 0;
        }

        return intdiv($years, 4)
            - intdiv($years, 100)
            + intdiv($years, 400)
            - intdiv($years, 4000);
    }
}
