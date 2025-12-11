<?php

namespace App\Services;

use App\Exceptions\CalendarConversionException;
use App\Exceptions\CalendarMalformedDateException;
use App\Exceptions\CalendarValidationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Morilog\Jalali\Jalalian;

class DateTimeConverterService
{
    private array $calendarServices;

    private array $fastCalendars = ['hebrew', 'islamic', 'french_republican', 'coptic', 'ethiopian'];

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
            'julian' => app(JulianCalendarService::class),
            'julian_day' => app(JulianDayService::class),
            'hebrew' => app(HebrewCalendarService::class),
            'islamic' => app(IslamicCalendarService::class),
            'french_republican' => app(FrenchRepublicanService::class),
            'chinese' => app(ChineseCalendarService::class),
            'japanese' => app(JapaneseCalendarService::class),
            'babylonian' => app(BabylonianCalendarService::class),
            'assyrian' => app(AssyrianCalendarService::class),
            'macedonian' => app(AncientMacedonianService::class),
            'coptic' => app(CopticCalendarService::class),
            'egyptian' => app(EgyptianCalendarService::class),
            'ethiopian' => app(EthiopianCalendarService::class),
            'mayan' => app(MayanCalendarService::class),
        ];
    }

    public function convert(string $dateString, string $inputType = 'gregorian'): array
    {
        $cacheKey = 'conversion_fast_'.md5($dateString.$inputType);

        return Cache::remember($cacheKey, 3600, function () use ($dateString, $inputType) {
            if (empty(trim($dateString))) {
                throw new InvalidArgumentException('Input date cannot be empty.');
            }

            $carbonDate = $this->parseInputDate($dateString, $inputType);

            $jalaliDate = Jalalian::fromCarbon($carbonDate);
            $julianDayNumber = gregoriantojd($carbonDate->month, $carbonDate->day, $carbonDate->year);
            $julianDateTime = $julianDayNumber
                 + ($carbonDate->hour / 24)
                 + ($carbonDate->minute / 1440)
                 + ($carbonDate->second / 86400);

            $result = [
                'gregorian' => [
                    'year' => $carbonDate->year,
                    'month_name' => $carbonDate->format('F'),
                    'day' => $carbonDate->day,
                    'day_of_week' => $carbonDate->format('l'),
                    'full_date' => $carbonDate->format('Y-m-d'),
                    'tags' => ['modern', 'solar'],
                    'name' => 'Gregorian',
                ],
                'jalali' => [
                    'year' => $jalaliDate->getYear(),
                    'month_name' => $jalaliDate->format('%B'),
                    'month_name_en' => self::JALALI_MONTHS_EN[$jalaliDate->getMonth()],
                    'day' => $jalaliDate->getDay(),
                    'day_of_week' => $jalaliDate->format('%A'),
                    'day_of_week_en' => self::JALALI_DAYS_EN[$jalaliDate->getDayOfWeek()],
                    'full_date' => $jalaliDate->format('Y/m/d'),
                    'tags' => ['modern', 'solar'],
                    'name' => 'Jalali',
                ],
                'unix' => $carbonDate->unix(),
                'iso8601' => $carbonDate->clone()->utc()->toIso8601String(),
                'julian-day' => $julianDayNumber,
                'modified-julian-day' => $julianDateTime - 2400000.5,
            ];

            // Add fast calendar conversions
            foreach ($this->fastCalendars as $key) {
                if (isset($this->calendarServices[$key])) {
                    $service = $this->calendarServices[$key];
                    try {
                        $result[$key] = $this->formatCalendarResult(
                            $service,
                            $service->fromGregorian($carbonDate)
                        );
                    } catch (\Throwable $e) {
                        $result[$key] = $this->formatCalendarResult(
                            $service,
                            ['error' => $e->getMessage()]
                        );
                    }
                }
            }

            return $result;
        });
    }

    public function convertAdditional(string $dateString, string $inputType = 'gregorian'): array
    {
        $cacheKey = 'conversion_additional_'.md5($dateString.$inputType);

        return Cache::remember($cacheKey, 3600, function () use ($dateString, $inputType) {
            $carbonDate = $this->parseInputDate($dateString, $inputType);
            $result = [];

            $additionalCalendars = array_diff(array_keys($this->calendarServices), $this->fastCalendars);

            foreach ($additionalCalendars as $key) {
                if ($key === 'julian_day') {
                    continue;
                }
                $service = $this->calendarServices[$key];
                try {
                    $result[$key] = $this->formatCalendarResult(
                        $service,
                        $service->fromGregorian($carbonDate)
                    );
                } catch (\Throwable $e) {
                    $result[$key] = $this->formatCalendarResult(
                        $service,
                        ['error' => $e->getMessage()]
                    );
                }
            }

            return $result;
        });
    }

    private function parseInputDate(string $dateString, string $inputType): Carbon
    {
        try {
            return match ($inputType) {
                'gregorian' => Carbon::parse($dateString)->startOfDay(),
                'jalali' => Carbon::instance(Jalalian::fromFormat('Y/m/d', $dateString)->toCarbon())->startOfDay(),
                'unix' => Carbon::createFromTimestamp((int) $dateString),
                'iso8601' => Carbon::parse($dateString),
                'jd' => Carbon::createFromJulianDay((float) $dateString),
                'mjd' => Carbon::createFromJulianDay((float) $dateString + 2400000.5),
                'hebrew', 'islamic', 'french_republican', 'chinese', 'japanese', 'babylonian', 'assyrian', 'macedonian', 'coptic', 'egyptian', 'ethiopian', 'mayan', 'julian' => $this->convertFromCalendar($inputType, $dateString),
                default => throw new InvalidArgumentException('Invalid input type provided.'),
            };
        } catch (CalendarMalformedDateException $e) {
            throw $e;
        } catch (CalendarConversionException $e) {
            throw $e; // Re-throw calendar-specific exceptions
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Invalid date format provided for the selected input type.');
        }
    }

    private function convertFromCalendar(string $calendarType, string $dateString): Carbon
    {
        if (! isset($this->calendarServices[$calendarType])) {
            throw new InvalidArgumentException("Unsupported calendar type: {$calendarType}");
        }

        if ($calendarType === 'japanese') {
            return $this->convertFromJapaneseEra($dateString);
        }

        // Parse date string (expecting YYYY/MM/DD format)
        if (! preg_match('/^(\d+)\/(\d+)\/(\d+)$/', $dateString, $matches)) {
            throw new CalendarMalformedDateException("Invalid date format for {$calendarType}. Use YYYY/MM/DD.", $calendarType);
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];

        $service = $this->calendarServices[$calendarType];

        if (! $service->validateDate($year, $month, $day)) {
            throw new CalendarValidationException("Invalid {$calendarType} date: {$dateString}", $calendarType);
        }

        return $service->toGregorian([
            'year' => $year,
            'month' => $month,
            'day' => $day,
        ]);
    }

    /**
     * Get full conversion results including both primary and additional calendar systems
     */
    public function getFullConversionResults(string $dateString, string $inputType = 'gregorian'): array
    {
        $primary = $this->convert($dateString, $inputType);
        $additional = $this->convertAdditional($dateString, $inputType);

        return array_merge($primary, $additional);
    }

    /**
     * Convert between specific calendar systems
     */
    public function convertBetweenCalendars(array $sourceDate, string $sourceCalendar, string $targetCalendar): array
    {
        if (! isset($this->calendarServices[$sourceCalendar])) {
            throw new InvalidArgumentException("Unsupported source calendar: {$sourceCalendar}");
        }

        if (! isset($this->calendarServices[$targetCalendar])) {
            throw new InvalidArgumentException("Unsupported target calendar: {$targetCalendar}");
        }

        $sourceService = $this->calendarServices[$sourceCalendar];
        $targetService = $this->calendarServices[$targetCalendar];

        // Convert source to Gregorian
        $gregorian = $sourceService->toGregorian($sourceDate);

        // Convert Gregorian to target
        return $this->formatCalendarResult(
            $targetService,
            $targetService->fromGregorian($gregorian)
        );
    }

    private function convertFromJapaneseEra(string $dateString): Carbon
    {
        $service = $this->calendarServices['japanese'];
        $trimmed = trim($dateString);

        if (! preg_match('/^(.+?)\s+(\d+)\/(\d+)\/(\d+)$/u', $trimmed, $matches)) {
            throw new CalendarMalformedDateException(
                'Invalid date format for japanese. Use "<Era Name> <Year>/<Month>/<Day>".',
                'japanese'
            );
        }

        $eraName = trim($matches[1]);
        $eraYear = (int) $matches[2];
        $month = (int) $matches[3];
        $day = (int) $matches[4];

        if (! $service->validateDate($eraYear, $month, $day)) {
            throw new CalendarValidationException(
                "Invalid japanese date: {$eraName} {$eraYear}/{$month}/{$day}",
                'japanese'
            );
        }

        return Carbon::instance($service->toGregorian([
            'era' => $eraName,
            'year' => $eraYear,
            'month' => $month,
            'day' => $day,
        ]));
    }

    /**
     * Ensure calendar results always include metadata required for filtering.
     */
    private function formatCalendarResult(CalendarConversionService $service, array $payload): array
    {
        $payload['name'] = $payload['name'] ?? $service->getName();
        $payload['tags'] = $payload['tags'] ?? $service->getTags();

        return $payload;
    }
}
