<?php

namespace App\Services;

use Carbon\Carbon;
use Fisharebest\ExtCalendar\ArabicCalendar;

class IslamicCalendarService extends CalendarConversionService
{
    private const ISLAMIC_MONTH_NAMES = [
        1 => 'Muharram',
        2 => 'Safar',
        3 => 'Rabi\' al-Awwal',
        4 => 'Rabi\' al-Thani',
        5 => 'Jumada al-Awwal',
        6 => 'Jumada al-Thani',
        7 => 'Rajab',
        8 => 'Sha\'ban',
        9 => 'Ramadan',
        10 => 'Shawwal',
        11 => 'Dhu al-Qi\'dah',
        12 => 'Dhu al-Hijjah',
    ];

    private const ISLAMIC_DAY_NAMES = [
        0 => 'Yawm al-Ahad',    // Sunday
        1 => 'Yawm al-Ithnayn', // Monday
        2 => 'Yawm al-Thulatha', // Tuesday
        3 => 'Yawm al-Arbia',   // Wednesday
        4 => 'Yawm al-Khamis',  // Thursday
        5 => 'Yawm al-Jumah',   // Friday
        6 => 'Yawm al-Sabt',    // Saturday
    ];

    private ArabicCalendar $calendar;

    public function __construct()
    {
        $this->calendar = new ArabicCalendar;
    }

    public function fromGregorian(Carbon $date): array
    {
        $date = $this->ensureTimezone($date, 'Asia/Riyadh');

        $jd = gregoriantojd($date->month, $date->day, $date->year);
        [$year, $month, $day] = $this->calendar->jdToYmd($jd);

        $dayOfWeek = jddayofweek($jd, 0);
        $monthName = self::ISLAMIC_MONTH_NAMES[$month] ?? 'Unknown';
        $dayName = self::ISLAMIC_DAY_NAMES[$dayOfWeek] ?? 'Unknown';

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $monthName,
            'day' => $day,
            'day_of_week' => $dayName,
            'full_date' => "{$year}/{$month}/{$day}",
            'display' => "{$day} {$monthName} {$year} AH",
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $year = $dateData['year'] ?? $dateData['islamic_year'] ?? null;
        $month = $dateData['month'] ?? $dateData['islamic_month'] ?? null;
        $day = $dateData['day'] ?? $dateData['islamic_day'] ?? null;

        if (! $year || ! $month || ! $day) {
            $this->handleValidationError('Invalid Islamic date components');
        }

        $jd = $this->calendar->ymdToJd($year, $month, $day);
        $gregorian = jdtogregorian($jd);

        if ($gregorian === '0/0/0') {
            $this->handleInvalidDate('Invalid Islamic date');
        }

        [$gMonth, $gDay, $gYear] = explode('/', $gregorian);

        return Carbon::create($gYear, $gMonth, $gDay, 0, 0, 0, 'Asia/Riyadh');
    }

    public function getKey(): string
    {
        return 'islamic';
    }

    public function getName(): string
    {
        return 'Islamic Calendar';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        if ($year < 1 || $month < 1 || $month > 12 || $day < 1) {
            return false;
        }

        $maxDays = $this->calendar->daysInMonth($year, $month);

        return $day <= $maxDays;
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        return $this->calendar->daysInMonth($year, $month);
    }

    public function getTags(): array
    {
        return ['modern', 'lunar'];
    }
}
