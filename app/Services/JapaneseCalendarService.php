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

        if (! $era) {
            $this->handleRangeError('No Japanese era found for the given date');
        }

        $eraYear = $era->getEraYear($date);
        $monthName = $date->format('F');
        $fullDate = sprintf(
            '%s %d/%02d/%02d',
            $era->name_romanized,
            $eraYear,
            $date->month,
            $date->day
        );

        return [
            'year' => $eraYear,
            'year_in_era' => $eraYear,
            'month' => $date->month,
            'month_name' => $monthName,
            'day' => $date->day,
            'era_name_japanese' => $era->name_japanese,
            'era_name_romanized' => $era->name_romanized,
            'era_name_english' => $era->name_english,
            'imperial_year' => $eraYear,
            'is_current_era' => $era->isActive(),
            'historical_context' => $era->historical_context,
            'full_date' => $fullDate,
            'display' => "{$era->name_romanized} {$eraYear}年 {$date->month}月 {$date->day}日",
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $eraName = $dateData['era'] ?? $dateData['era_romanized'] ?? null;
        $year = $dateData['year'] ?? $dateData['era_year'] ?? null;
        $month = $dateData['month'] ?? null;
        $day = $dateData['day'] ?? null;

        if (! $eraName || ! $year || ! $month || ! $day) {
            $this->handleValidationError('Invalid Japanese date components');
        }

        $era = $this->findEraByName($eraName);

        if (! $era) {
            $this->handleValidationError("Japanese era '{$eraName}' not found");
        }

        $eraYear = (int) $year;
        $month = (int) $month;
        $day = (int) $day;
        $gregorianYear = $this->calculateGregorianYear($era, $eraYear);

        if (! $this->validateDate($eraYear, $month, $day)) {
            $this->handleValidationError("Invalid Japanese date: {$eraName} {$eraYear}/{$month}/{$day}");
        }

        $candidateDate = Carbon::createFromFormat(
            'Y-n-j H:i:s',
            "{$gregorianYear}-{$month}-{$day} 00:00:00",
            'Asia/Tokyo'
        );

        if ($candidateDate === false) {
            $this->handleValidationError("Invalid Japanese date: {$eraName} {$eraYear}/{$month}/{$day}");
        }

        // Validate that the date is within the era's range
        if ($candidateDate->lt($era->start_date) ||
            ($era->end_date && $candidateDate->gt($era->end_date))) {
            $this->handleRangeError("Date {$eraName}{$year}/{$month}/{$day} is not within the era's valid range");
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
        if ($year < 1 || $month < 1 || $month > 12 || $day < 1) {
            return false;
        }

        $maxDays = $this->getDaysInMonth($year, $month);

        return $day <= $maxDays;
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        return match ($month) {
            2 => 29, // Leap-safe default; actual validation occurs against era metadata
            4, 6, 9, 11 => 30,
            default => 31,
        };
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

    public function getTags(): array
    {
        return ['east-asian', 'solar'];
    }

    private function calculateGregorianYear(JapaneseEra $era, int $eraYear): int
    {
        return $era->start_date->year + $eraYear - 1;
    }

    private function findEraByName(string $eraName): ?JapaneseEra
    {
        return JapaneseEra::where('name_romanized', $eraName)
            ->orWhere('name_japanese', $eraName)
            ->orWhere('name_english', $eraName)
            ->first();
    }
}
