<?php

namespace App\Services\Chinese;

use Carbon\CarbonImmutable;

/**
 * Represents a single lunation in the Chinese calendar.
 */
class LunarMonth
{
    public CarbonImmutable $startDate;
    public CarbonImmutable $endDate;
    public float $startJde;
    public float $endJde;
    public int $number = 0;
    public bool $isLeap = false;
    public bool $hasMajorSolarTerm = false;
    public int $chineseYear = 0;

    public function __construct(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        float $startJde,
        float $endJde
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->startJde = $startJde;
        $this->endJde = $endJde;
    }

    /**
     * Number of days in this lunar month.
     */
    public function length(): int
    {
        return $this->startDate->diffInDays($this->endDate);
    }

    /**
     * Determine if a given Beijing date falls within the month interval.
     */
    public function contains(CarbonImmutable $date): bool
    {
        return $date->greaterThanOrEqualTo($this->startDate)
            && $date->lessThan($this->endDate);
    }
}
