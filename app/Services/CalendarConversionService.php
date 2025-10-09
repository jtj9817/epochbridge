<?php

namespace App\Services;

use App\Exceptions\CalendarCalculationException;
use App\Exceptions\CalendarMalformedDateException;
use App\Exceptions\CalendarRangeException;
use App\Exceptions\CalendarValidationException;
use Carbon\Carbon;

abstract class CalendarConversionService
{
    /**
     * Convert a Gregorian date to this calendar system
     * 
     * @param Carbon $date The Gregorian date to convert
     * @return array The converted date in this calendar system
     * @throws CalendarCalculationException When date conversion fails
     */
    abstract public function fromGregorian(Carbon $date): array;

    /**
     * Convert this calendar system to Gregorian
     * 
     * @param array $dateData The date data in this calendar system
     * @return Carbon The converted Gregorian date
     * @throws CalendarValidationException|CalendarMalformedDateException|CalendarCalculationException
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
     * Get tags for filtering
     *
     * @return array<string>
     */
    abstract public function getTags(): array;

    /**
     * Validate date components for this calendar system
     */
    abstract public function validateDate(int $year, int $month, int $day): bool;

    /**
     * Get days in month for this calendar system
     */
    abstract public function getDaysInMonth(int $year, int $month): int;

    /**
     * @throws CalendarMalformedDateException
     */
    protected function handleInvalidDate(string $message): never
    {
        throw new CalendarMalformedDateException($message, $this->getKey());
    }

    /**
     * @throws CalendarRangeException
     */
    protected function handleRangeError(string $message): never
    {
        throw new CalendarRangeException($message, $this->getKey());
    }

    /**
     * @throws CalendarCalculationException
     */
    protected function handleCalculationError(string $message, ?\Throwable $previous = null): never
    {
        throw new CalendarCalculationException($message, $this->getKey(), null, 0, $previous);
    }

    /**
     * @throws CalendarValidationException
     */
    protected function handleValidationError(string $message): never
    {
        throw new CalendarValidationException($message, $this->getKey());
    }

    /**
     * Ensure timezone is set for calendar-specific calculations
     */
    protected function ensureTimezone(Carbon $date, string $timezone = 'UTC'): Carbon
    {
        return $date->copy()->setTimezone($timezone);
    }
}
