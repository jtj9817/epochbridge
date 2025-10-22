<?php

namespace App\Services\Chinese;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Core engine for Chinese lunisolar calendar calculations.
 */
class ChineseLunisolarCalendar
{
    private AstronomyCalculator $astronomy;

    /**
     * Cached suì data keyed by "<startYear>-<endYear>".
     *
     * @var array<string, array{start: float, end: float, months: array<int,LunarMonth>}>
     */
    private array $suiCache = [];

    /**
     * Cached Chinese year month listings keyed by Chinese year.
     *
     * @var array<int, array<int,LunarMonth>>
     */
    private array $yearMonthCache = [];

    public function __construct(?AstronomyCalculator $astronomy = null)
    {
        $this->astronomy = $astronomy ?? new AstronomyCalculator;
    }

    /**
     * Convert a Gregorian date (any timezone) to Chinese calendar components.
     *
     * @return array{
     *     year:int,
     *     month:int,
     *     day:int,
     *     is_leap:bool,
     *     month_length:int,
     *     month_start:CarbonImmutable,
     *     month_end:CarbonImmutable
     * }
     */
    public function chineseDateFor(CarbonInterface $date): array
    {
        $beijingTime = CarbonImmutable::createFromInterface($date)->setTimezone('Asia/Shanghai');

        $month = $this->locateMonthForDate($beijingTime);

        if (! $month) {
            throw new \RuntimeException('Unable to resolve lunar month for date '.$beijingTime->toIso8601String());
        }

        $dayStart = $beijingTime->setTime(0, 0, 0);
        $day = $month->startDate->diffInDays($dayStart) + 1;

        return [
            'year' => $month->chineseYear,
            'month' => $month->number,
            'day' => $day,
            'is_leap' => $month->isLeap,
            'month_length' => $month->length(),
            'month_start' => $month->startDate,
            'month_end' => $month->endDate,
        ];
    }

    /**
     * Resolve the Gregorian date (Asia/Shanghai) for a Chinese calendar input.
     */
    public function gregorianDateFor(int $year, int $month, int $day, bool $isLeap): CarbonImmutable
    {
        $months = $this->monthsForChineseYear($year);

        $candidate = null;
        foreach ($months as $lunation) {
            if ($lunation->number === $month && $lunation->isLeap === $isLeap && $lunation->chineseYear === $year) {
                $candidate = $lunation;
                break;
            }
        }

        if (! $candidate) {
            throw new \RuntimeException("No lunar month {$month} (leap=".($isLeap ? 'true' : 'false').") in Chinese year {$year}");
        }

        if ($day < 1 || $day > $candidate->length()) {
            throw new \RuntimeException("Day {$day} out of range for Chinese month {$month} (length {$candidate->length()}) in year {$year}");
        }

        return $candidate->startDate->addDays($day - 1);
    }

    /**
     * Determine solar term index (0-23) for a given date.
     */
    public function solarTermIndexFor(CarbonInterface $date): int
    {
        $beijingTime = CarbonImmutable::createFromInterface($date)->setTimezone('Asia/Shanghai');
        $jdUtc = $this->astronomy->julianDayFromCarbon($beijingTime);
        $approxYear = 2000.0 + ($jdUtc - 2451545.0) / 365.2425;
        $jde = $jdUtc + ($this->astronomy->deltaTSeconds($approxYear) / AstronomyCalculator::SECONDS_PER_DAY);
        $longitude = $this->astronomy->sunApparentLongitude($jde);
        $index = (int) floor($longitude / 15.0) % 24;

        return $index < 0 ? ($index + 24) : $index;
    }

    /**
     * Retrieve all lunar months that belong to a Chinese year.
     *
     * @return array<int,LunarMonth>
     */
    public function monthsForChineseYear(int $year): array
    {
        if (! isset($this->yearMonthCache[$year])) {
            $this->yearMonthCache[$year] = $this->buildMonthsForChineseYear($year);
        }

        return $this->yearMonthCache[$year];
    }

    /**
     * Locate the lunar month covering the provided Beijing datetime.
     */
    private function locateMonthForDate(CarbonImmutable $beijingTime): ?LunarMonth
    {
        $sui = $this->getSuiCovering($beijingTime);
        $month = $this->findMonthInSui($sui['months'], $beijingTime);

        if ($month) {
            return $month;
        }

        // If not found, check adjacent suì boundaries.
        $forward = $this->getSuiCovering($beijingTime->addDay());
        $month = $this->findMonthInSui($forward['months'], $beijingTime);

        if ($month) {
            return $month;
        }

        $backward = $this->getSuiCovering($beijingTime->subDay());

        return $this->findMonthInSui($backward['months'], $beijingTime);
    }

    /**
     * Find suì (between winter solstices) covering a Beijing datetime.
     *
     * @return array{start: float, end: float, months: array<int,LunarMonth>}
     */
    private function getSuiCovering(CarbonImmutable $beijingTime): array
    {
        $gregorianYear = $beijingTime->year;
        $currentSolsticeJde = $this->astronomy->winterSolstice($gregorianYear);
        $currentSolstice = $this->astronomy->jdeToCarbon($currentSolsticeJde, 'Asia/Shanghai');

        if ($beijingTime->lessThan($currentSolstice)) {
            $startYear = $gregorianYear - 1;
            $startJde = $this->astronomy->winterSolstice($startYear);
            $endJde = $currentSolsticeJde;
        } else {
            $startYear = $gregorianYear;
            $startJde = $currentSolsticeJde;
            $endJde = $this->astronomy->winterSolstice($gregorianYear + 1);
        }

        $cacheKey = sprintf('%d-%d', $startYear, $startYear + 1);

        if (! isset($this->suiCache[$cacheKey])) {
            $this->suiCache[$cacheKey] = $this->buildSui($startJde, $endJde);
        }

        return $this->suiCache[$cacheKey];
    }

    /**
     * Build all lunar months that fall within a winter-solstice bounded suì.
     *
     * @return array{start: float, end: float, months: array<int,LunarMonth>}
     */
    private function buildSui(float $startJde, float $endJde): array
    {
        $newMoons = $this->astronomy->newMoonsInRange($startJde - 60.0, $endJde + 60.0);

        if (count($newMoons) < 3) {
            throw new \RuntimeException('Insufficient new moon data to build lunations.');
        }

        $startIndex = $this->findLunationIndex($newMoons, $startJde);
        $endIndex = $this->findLunationIndex($newMoons, $endJde);

        $months = [];

        for ($i = $startIndex; $i < $endIndex; $i++) {
            $start = $newMoons[$i];
            $end = $newMoons[$i + 1];

            $startDate = $this->astronomy->jdeToCarbon($start, 'Asia/Shanghai')->setTime(0, 0, 0);
            $endDate = $this->astronomy->jdeToCarbon($end, 'Asia/Shanghai')->setTime(0, 0, 0);

            $months[] = new LunarMonth($startDate, $endDate, $start, $end);
        }

        $termStart = $newMoons[$startIndex];
        $termEnd = $newMoons[$endIndex];
        $terms = $this->astronomy->majorSolarTermsBetween($termStart, $termEnd);

        foreach ($months as $month) {
            foreach ($terms as $termJde) {
                if ($termJde >= $month->startJde && $termJde < $month->endJde) {
                    $month->hasMajorSolarTerm = true;
                    break;
                }
            }
        }

        $this->labelMonths($months);

        return [
            'start' => $startJde,
            'end' => $endJde,
            'months' => $months,
        ];
    }

    /**
     * Assign month numbers, leap flags, and Chinese year labels.
     *
     * @param  array<int,LunarMonth>  $months
     */
    private function labelMonths(array $months): void
    {
        $count = count($months);

        if ($count !== 12 && $count !== 13) {
            throw new \RuntimeException("Unexpected lunation count {$count} for Chinese suì");
        }

        if ($count === 13) {
            foreach ($months as $index => $month) {
                if ($index === 0) {
                    continue;
                }
                if (! $month->hasMajorSolarTerm) {
                    $month->isLeap = true;
                    break;
                }
            }
        }

        $currentNumber = 11;

        foreach ($months as $index => $month) {
            if ($index === 0) {
                $month->number = 11;
                continue;
            }

            if (! $month->isLeap) {
                $currentNumber = $currentNumber === 12 ? 1 : $currentNumber + 1;
            }

            $month->number = $currentNumber;
        }

        $monthOneIndex = null;

        foreach ($months as $index => $month) {
            if (! $month->isLeap && $month->number === 1) {
                $monthOneIndex = $index;
                break;
            }
        }

        if ($monthOneIndex === null) {
            throw new \RuntimeException('Failed to locate month 1 in lunation sequence.');
        }

        $chineseYear = $months[$monthOneIndex]->startDate->year;

        foreach ($months as $index => $month) {
            $month->chineseYear = $index >= $monthOneIndex ? $chineseYear : $chineseYear - 1;
        }
    }

    /**
     * Build the complete set of lunar months for a Chinese calendar year.
     *
     * @return array<int,LunarMonth>
     */
    private function buildMonthsForChineseYear(int $year): array
    {
        $before = $this->getSuiByBounds($year - 1, $year);
        $after = $this->getSuiByBounds($year, $year + 1);

        $months = [];

        foreach ([$before, $after] as $sui) {
            foreach ($sui['months'] as $month) {
                if ($month->chineseYear === $year) {
                    $months[] = $month;
                }
            }
        }

        usort(
            $months,
            static function (LunarMonth $a, LunarMonth $b): int {
                if ($a->startDate->equalTo($b->startDate)) {
                    return 0;
                }

                return $a->startDate->lessThan($b->startDate) ? -1 : 1;
            }
        );

        return $months;
    }

    /**
     * Retrieve or build suì data for a pair of Gregorian years.
     */
    private function getSuiByBounds(int $startYear, int $endYear): array
    {
        $key = sprintf('%d-%d', $startYear, $endYear);

        if (! isset($this->suiCache[$key])) {
            $startJde = $this->astronomy->winterSolstice($startYear);
            $endJde = $this->astronomy->winterSolstice($endYear);

            $this->suiCache[$key] = $this->buildSui($startJde, $endJde);
        }

        return $this->suiCache[$key];
    }

    /**
     * @param  array<int,LunarMonth>  $months
     */
    private function findMonthInSui(array $months, CarbonImmutable $date): ?LunarMonth
    {
        foreach ($months as $month) {
            if ($month->contains($date)) {
                return $month;
            }
        }

        return null;
    }

    /**
     * Determine index of the lunation whose interval contains the target JD.
     *
     * @param  array<int,float>  $newMoons
     */
    private function findLunationIndex(array $newMoons, float $targetJd): int
    {
        $count = count($newMoons);

        for ($i = 0; $i < $count - 1; $i++) {
            if ($newMoons[$i] <= $targetJd && $targetJd < $newMoons[$i + 1]) {
                return $i;
            }
        }

        return $count - 2;
    }
}
