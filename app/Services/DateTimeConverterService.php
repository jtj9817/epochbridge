<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Morilog\Jalali\Jalalian;

class DateTimeConverterService
{
    /**
     * Jalali month names transliterated to English.
     */
    private const JALALI_MONTHS_EN = [
        1 => 'Farvardin',
        2 => 'Ordibehesht',
        3 => 'Khordad',
        4 => 'Tir',
        5 => 'Mordad',
        6 => 'Shahrivar',
        7 => 'Mehr',
        8 => 'Aban',
        9 => 'Azar',
        10 => 'Dey',
        11 => 'Bahman',
        12 => 'Esfand',
    ];

    /**
     * Jalali day names transliterated to English.
     */
    private const JALALI_DAYS_EN = [
        0 => 'Shanbeh',      // Saturday
        1 => 'Yekshanbeh',   // Sunday  
        2 => 'Doshanbeh',    // Monday
        3 => 'Seshhanbeh',   // Tuesday
        4 => 'Chaharshanbeh', // Wednesday
        5 => 'Panjshanbeh',  // Thursday
        6 => 'Jomeh',        // Friday
    ];

    /**
     * Converts a date string into various formats.
     *
     * @param  string  $dateString The date string to convert.
     * @return array<string, mixed> An associative array with converted dates.
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
            'gregorian' => [
                'year'        => $carbonDate->year,
                'month_name'  => $carbonDate->format('F'),
                'day'         => $carbonDate->day,
                'day_of_week' => $carbonDate->format('l'),
                'full_date'   => $carbonDate->format('Y-m-d'),
            ],
            'jalali'    => [
                'year'           => $jalaliDate->getYear(),
                'month_name'     => $jalaliDate->format('%B'),
                'month_name_en'  => self::JALALI_MONTHS_EN[$jalaliDate->getMonth()],
                'day'            => $jalaliDate->getDay(),
                'day_of_week'    => $jalaliDate->format('%A'),
                'day_of_week_en' => self::JALALI_DAYS_EN[$jalaliDate->getDayOfWeek()],
                'full_date'      => $jalaliDate->format('Y-m-d'),
            ],
            'unix'      => $carbonDate->unix(),
            'iso8601'   => $carbonDate->clone()->utc()->toIso8601String(),
        ];
    }
}
