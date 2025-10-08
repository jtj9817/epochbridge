<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Morilog\Jalali\Jalalian;

class DateTimeConverterService
{
    private array $calendarServices;

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

    public function __construct()
    {
        $this->calendarServices = [
            'julian' => new JulianDayService(),
            'hebrew' => new HebrewCalendarService(),
            'islamic' => new IslamicCalendarService(),
            'french_republican' => new FrenchRepublicanService(),
        ];
    }

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
                'hebrew', 'islamic', 'french_republican' => $this->convertFromCalendar($inputType, $dateString),
                default => throw new InvalidArgumentException('Invalid input type provided.'),
            };
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Invalid date format provided for the selected input type.');
        }

        $jalaliDate = Jalalian::fromCarbon($carbonDate);
        $julianDay = GregorianToJD($carbonDate->month, $carbonDate->day, $carbonDate->year) 
             + ($carbonDate->hour / 24) 
             + ($carbonDate->minute / 1440) 
             + ($carbonDate->second / 86400);

        $result = [
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

        // Add calendar conversions
        foreach ($this->calendarServices as $key => $service) {
            try {
                $result[$key] = $service->fromGregorian($carbonDate);
            } catch (\Exception $e) {
                $result[$key] = ['error' => $e->getMessage()];
            }
        }

        return $result;
    }

    private function convertFromCalendar(string $calendarType, string $dateString): Carbon
    {
        if (!isset($this->calendarServices[$calendarType])) {
            throw new InvalidArgumentException("Unsupported calendar type: {$calendarType}");
        }

        // Parse date string (expecting YYYY/MM/DD format)
        if (!preg_match('/^(\d+)\/(\d+)\/(\d+)$/', $dateString, $matches)) {
            throw new InvalidArgumentException("Invalid date format for {$calendarType}. Use YYYY/MM/DD.");
        }

        $year = (int)$matches[1];
        $month = (int)$matches[2];
        $day = (int)$matches[3];

        $service = $this->calendarServices[$calendarType];
        
        if (!$service->validateDate($year, $month, $day)) {
            throw new InvalidArgumentException("Invalid {$calendarType} date: {$dateString}");
        }

        return $service->toGregorian([
            'year' => $year,
            'month' => $month,
            'day' => $day,
        ]);
    }

    /**
     * Convert between specific calendar systems
     */
    public function convertBetweenCalendars(array $sourceDate, string $sourceCalendar, string $targetCalendar): array
    {
        if (!isset($this->calendarServices[$sourceCalendar])) {
            throw new InvalidArgumentException("Unsupported source calendar: {$sourceCalendar}");
        }

        if (!isset($this->calendarServices[$targetCalendar])) {
            throw new InvalidArgumentException("Unsupported target calendar: {$targetCalendar}");
        }

        $sourceService = $this->calendarServices[$sourceCalendar];
        $targetService = $this->calendarServices[$targetCalendar];

        // Convert source to Gregorian
        $gregorian = $sourceService->toGregorian($sourceDate);
        
        // Convert Gregorian to target
        return $targetService->fromGregorian($gregorian);
    }
}
