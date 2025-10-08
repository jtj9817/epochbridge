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
     * Converts a date string from a given format into various other formats.
     *
     * @param  string  $dateString The date string to convert.
     * @param  string  $inputType The format of the input date string.
     * @return array<string, mixed> An associative array with converted dates.
     *
     * @throws \InvalidArgumentException If the date string is invalid or input type is unsupported.
     */
    public function convert(string $dateString, string $inputType = 'gregorian'): array
    {
        if (empty(trim($dateString))) {
            throw new InvalidArgumentException('Input date cannot be empty.');
        }

        try {
            $carbonDate = match ($inputType) {
                'gregorian' => Carbon::parse($dateString)->startOfDay(),
                'jalali' => Jalalian::fromFormat('Y/m/d', $dateString)->toCarbon()->startOfDay(),
                'unix' => Carbon::createFromTimestamp((int) $dateString),
                'iso8601' => Carbon::parse($dateString),
                'jd' => Carbon::createFromJulianDay((float) $dateString),
                'mjd' => Carbon::createFromJulianDay((float) $dateString + 2400000.5),
                default => throw new InvalidArgumentException('Invalid input type provided.'),
            };
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Invalid date format provided for the selected input type.');
        }

        $jalaliDate = Jalalian::fromCarbon($carbonDate);
        $julianDay = $carbonDate->getJulianDay();

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
                'full_date'      => $jalaliDate->format('Y/m/d'),
            ],
            'unix'      => $carbonDate->unix(),
            'iso8601'   => $carbonDate->clone()->utc()->toIso8601String(),
            'jd'        => $julianDay,
            'mjd'       => $julianDay - 2400000.5,
        ];
    }
}
