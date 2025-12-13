<?php

namespace App\Services;

use Carbon\Carbon;

class AncientMacedonianService extends CalendarConversionService
{
    private const MACEDONIAN_MONTH_NAMES = [
        1 => 'Dios',
        2 => 'Apellaios',
        3 => 'Audynaios',
        4 => 'Peritios',
        5 => 'Dystros',
        6 => 'Xanthikos',
        7 => 'Artemisios',
        8 => 'Daisios',
        9 => 'Panemos',
        10 => 'Loos',
        11 => 'Gorpiaios',
        12 => 'Hyperberetaios',
    ];

    public function fromGregorian(Carbon $date): array
    {
        // NOTE: This is a highly simplified approximation. The start of the
        // Macedonian year (Dios) was in autumn.
        $jd = gregoriantojd($date->month, $date->day, $date->year);

        // Approximate start of year to autumn equinox (Sep 22)
        $yearStartJd = gregoriantojd(9, 22, $date->year);
        if ($jd < $yearStartJd) {
            $yearStartJd = gregoriantojd(9, 22, $date->year - 1);
        }

        $daysIntoYear = $jd - $yearStartJd;

        // Approximate months as 29.5 days
        $month = floor($daysIntoYear / 29.5) + 1;
        $day = ($daysIntoYear % 29.5) + 1;

        // Era is often Seleucid Era (starting 312 BC)
        $year = $date->year + 311;

        return [
            'year' => $year,
            'month' => (int) $month,
            'month_name' => self::MACEDONIAN_MONTH_NAMES[(int) $month] ?? 'Unknown',
            'day' => (int) $day,
            'full_date' => "{$year}/".(int) $month.'/'.(int) $day,
            'display' => (int) $day.' '.(self::MACEDONIAN_MONTH_NAMES[(int) $month] ?? 'Unknown')." {$year} SE",
            'disclaimer' => 'This is a scholarly approximation. The calendar varied by region and era.',
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $year = $dateData['year'] ?? null;
        $month = $dateData['month'] ?? null;
        $day = $dateData['day'] ?? null;

        if ($year === null || $month === null || $day === null) {
            $this->handleValidationError('Invalid Ancient Macedonian date components');
        }

        $year = (int) $year;
        $month = (int) $month;
        $day = (int) $day;

        if (! $this->validateDate($year, $month, $day)) {
            $this->handleValidationError("Invalid Ancient Macedonian date: {$year}/{$month}/{$day}");
        }

        // Reverse the approximation used in fromGregorian().
        $gregorianYear = $year - 311;

        try {
            $yearStartJd = gregoriantojd(9, 22, $gregorianYear);
            $daysIntoYear = (int) round((($month - 1) * 29.5) + ($day - 1));
            $jd = $yearStartJd + $daysIntoYear;

            $gregorian = jdtogregorian($jd);
            if ($gregorian === '0/0/0') {
                $this->handleRangeError('Unable to convert Ancient Macedonian date to Gregorian');
            }

            [$gMonth, $gDay, $gYear] = array_map('intval', explode('/', $gregorian));

            return Carbon::create($gYear, $gMonth, $gDay, 0, 0, 0, 'UTC');
        } catch (\Throwable $e) {
            $this->handleCalculationError('Unable to convert Ancient Macedonian date to Gregorian', $e);
        }
    }

    public function getKey(): string
    {
        return 'macedonian';
    }

    public function getName(): string
    {
        return 'Ancient Macedonian';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        return $month >= 1 && $month <= 12 && $day >= 1 && $day <= 30;
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        // Lunar months alternate between 29 and 30 days
        return ($month % 2 === 1) ? 30 : 29;
    }

    public function getTags(): array
    {
        return ['ancient', 'lunisolar'];
    }
}
