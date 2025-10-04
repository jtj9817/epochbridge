<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Morilog\Jalali\Jalalian;

class DateTimeConverterService
{
    /**
     * Converts a date string into various formats.
     *
     * @param  string  $dateString The date string to convert.
     * @return array<string, string|int> An associative array with converted dates.
     *
     * @throws \InvalidArgumentException If the date string is invalid.
     */
    public function convert(string $dateString): array
    {
        try {
            // Parse date. This will use the application's default timezone.
            // Set time to the beginning of the day for consistency.
            $carbonDate = Carbon::parse($dateString)->startOfDay();
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Invalid date format provided.');
        }

        $jalaliDate = Jalalian::fromCarbon($carbonDate);

        return [
            'gregorian' => $carbonDate->format('Y-m-d'),
            'jalali'    => $jalaliDate->format('Y-m-d'),
            'unix'      => $carbonDate->unix(),
            'iso8601'   => $carbonDate->clone()->utc()->toIso8601String(),
        ];
    }
}
