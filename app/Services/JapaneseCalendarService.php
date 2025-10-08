<?php

namespace App\Services;

use App\Models\JapaneseEra;
use Carbon\Carbon;

class JapaneseCalendarService extends CalendarConversionService
{
    public function fromGregorian(Carbon $date): array
    {
        $date = $this->ensureTimezone($date, 'Asia/Tokyo');
        
        $era = JapaneseEra::findEraForDate($date);
        
        if (!$era) {
            $this->handleRangeError('No Japanese era found for the given date');
        }

        $eraYear = $era->getEraYear($date);
        $imperialYear = $this->calculateImperialYear($date);

        return [
            'year' => $eraYear,
            'month' => $date->month,
            'day' => $date->day,
            'era_japanese' => $era->name_japanese,
            'era_romanized' => $era->name_romanized,
            'era_english' => $era->name_english,
            'imperial_year' => $imperialYear,
            'is_current_era' => $era->isActive(),
            'historical_context' => $era->historical_context,
            'full_date' => "{$era->name_romanized}{$eraYear}/{$date->month}/{$date->day}",
            'display' => "{$era->name_romanized} {$eraYear}年 {$date->month}月 {$date->day}日",
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $eraName = $dateData['era'] ?? $dateData['era_romanized'] ?? null;
        $year = $dateData['year'] ?? $dateData['era_year'] ?? null;
        $month = $dateData['month'] ?? null;
        $day = $dateData['day'] ?? null;

        if (!$eraName || !$year || !$month || !$day) {
            $this->handleValidationError('Invalid Japanese date components');
        }

        $era = JapaneseEra::where('name_romanized', $eraName)
            ->orWhere('name_japanese', $eraName)
            ->orWhere('name_english', $eraName)
            ->first();

        if (!$era) {
            $this->handleValidationError("Japanese era '{$eraName}' not found");
        }

        $gregorianYear = $era->start_date->year + $year - 1;
        $candidateDate = Carbon::create($gregorianYear, $month, $day, 0, 0, 0, 'Asia/Tokyo');

        // Validate that the date is within the era's range
        if ($candidateDate->lt($era->start_date) || 
            ($era->end_date && $candidateDate->gt($era->end_date))) {
            $this->handleRangeError("Date {$eraName}{$year}/{$month}/{$day} is not within the era's valid range");
        }

        if (!$this->validateDate($year, $month, $day)) {
            $this->handleValidationError("Invalid Japanese date: {$eraName}{$year}/{$month}/{$day}");
        }

        return $candidateDate;
    }

    public function getKey(): string
    {
        return 'japanese';
    }

    public function getName(): string
    {
        return 'Japanese Calendar';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        // Japanese calendar uses same solar months as Gregorian
        if ($year < 1 || $month < 1 || $month > 12 || $day < 1) {
            return false;
        }

        return $day <= $this->getDaysInMonth($year, $month);
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        // Same as Gregorian calendar
        return cal_days_in_month(CAL_GREGORIAN, $month, $year + 1867); // Approximate base year
    }

    /**
     * Calculate imperial year (year of emperor's reign)
     */
    private function calculateImperialYear(Carbon $date): int
    {
        // Imperial year starts from the year of emperor's accession
        // For current era (Reiwa), it's year since 2019
        $era = JapaneseEra::findEraForDate($date);
        return $era ? $date->year - $era->start_date->year + 1 : $date->year;
    }

    /**
     * Get all available Japanese eras
     */
    public function getAvailableEras(): array
    {
        return JapaneseEra::orderBy('start_date', 'desc')
            ->get()
            ->map(function ($era) {
                return [
                    'japanese' => $era->name_japanese,
                    'romanized' => $era->name_romanized,
                    'english' => $era->name_english,
                    'start_date' => $era->start_date->format('Y-m-d'),
                    'end_date' => $era->end_date?->format('Y-m-d'),
                    'is_active' => $era->isActive(),
                ];
            })
            ->toArray();
    }
}
