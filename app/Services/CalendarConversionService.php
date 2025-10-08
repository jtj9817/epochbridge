<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

abstract class CalendarConversionService
{
    /**
     * Convert a Gregorian date to this calendar system
     * 
     * @param Carbon $date The Gregorian date to convert
     * @return array The converted date in this calendar system
     * @throws InvalidArgumentException When date conversion fails
     */
    abstract public function fromGregorian(Carbon $date): array;

    /**
     * Convert this calendar system to Gregorian
     * 
     * @param array $dateData The date data in this calendar system
     * @return Carbon The converted Gregorian date
     * @throws InvalidArgumentException When date is invalid
     */
    abstract public function toGregorian(array $dateData): Carbon;

    /**
     * Get the calendar system key
     */
    abstract public function getKey(): string;

    /**
     * Get the calendar system display name
     */
    abstract public function getName(): string;

    /**
     * Validate date components for this calendar system
     */
    abstract public function validateDate(int $year, int $month, int $day): bool;

    /**
     * Get days in month for this calendar system
     */
    abstract public function getDaysInMonth(int $year, int $month): int;

    /**
     * Common error handling for invalid dates
     */
    protected function handleInvalidDate(string $message): never
    {
        throw new InvalidArgumentException($message);
    }

    /**
     * Ensure timezone is set for calendar-specific calculations
     */
    protected function ensureTimezone(Carbon $date, string $timezone = 'UTC'): Carbon
    {
        return $date->copy()->setTimezone($timezone);
    }
}
