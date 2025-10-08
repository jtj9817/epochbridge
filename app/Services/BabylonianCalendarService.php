<?php

namespace App\Services;

use Carbon\Carbon;

class BabylonianCalendarService extends CalendarConversionService
{
    private const BABYLONIAN_MONTH_NAMES = [
        1 => 'Nisannu',
        2 => 'Ayyaru',
        3 => 'Simanu',
        4 => 'Du\'uzu',
        5 => 'Abu',
        6 => 'Ululu',
        7 => 'Tashritu',
        8 => 'Arakhsamna',
        9 => 'Kislimu',
        10 => 'Tebetu',
        11 => 'Shabatu',
        12 => 'Adaru',
        13 => 'Adaru II', // Intercalary month
    ];

    public function fromGregorian(Carbon $date): array
    {
        // NOTE: This is a simplified approximation for demonstration.
        // Accurate conversion requires complex astronomical tables.
        $jd = GregorianToJD($date->month, $date->day, $date->year);
        
        // Find the start of the Babylonian year (first new moon after spring equinox)
        $springEquinox = (new AncientAstronomyService())->getSpringEquinox($date->year);
        if ($jd < $springEquinox) {
            $springEquinox = (new AncientAstronomyService())->getSpringEquinox($date->year - 1);
        }

        $newYearJd = (new AncientAstronomyService())->getNearestNewMoon($springEquinox);
        
        $daysIntoYear = $jd - $newYearJd;
        
        // Approximate months as 29.5 days
        $month = floor($daysIntoYear / 29.5) + 1;
        $day = ($daysIntoYear % 29.5) + 1;
        
        // Simplified year calculation based on Gregorian
        $year = $date->year + 539; // Approximate conversion from Anno Domini to Babylonian Era

        return [
            'year' => $year,
            'month' => (int)$month,
            'month_name' => self::BABYLONIAN_MONTH_NAMES[(int)$month] ?? 'Unknown',
            'day' => (int)$day,
            'full_date' => "{$year}/" . (int)$month . "/" . (int)$day,
            'display' => (int)$day . " " . (self::BABYLONIAN_MONTH_NAMES[(int)$month] ?? 'Unknown') . " {$year}",
            'disclaimer' => 'This is a scholarly approximation. Exact dates are subject to interpretation of cuneiform tablets.',
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $this->handleInvalidDate('Conversion from Babylonian is not supported due to historical complexities.');
        // @codeCoverageIgnoreStart
        return Carbon::now(); // Should not be reached
        // @codeCoverageIgnoreEnd
    }

    public function getKey(): string
    {
        return 'babylonian';
    }

    public function getName(): string
    {
        return 'Babylonian Calendar';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        return $month >= 1 && $month <= 13 && $day >= 1 && $day <= 30;
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        // Lunar months alternate between 29 and 30 days
        return ($month % 2 === 1) ? 30 : 29;
    }
}
