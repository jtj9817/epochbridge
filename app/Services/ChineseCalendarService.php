<?php

namespace App\Services;

use App\Exceptions\CalendarCalculationException;
use App\Exceptions\CalendarValidationException;
use Carbon\Carbon;

class ChineseCalendarService extends CalendarConversionService
{
    private const CHINESE_MONTH_NAMES = [
        1 => '正月', 2 => '二月', 3 => '三月', 4 => '四月', 5 => '五月', 6 => '六月',
        7 => '七月', 8 => '八月', 9 => '九月', 10 => '十月', 11 => '冬月', 12 => '腊月',
    ];

    private const CHINESE_DAY_NAMES = [
        1 => '初一', 2 => '初二', 3 => '初三', 4 => '初四', 5 => '初五', 6 => '初六',
        7 => '初七', 8 => '初八', 9 => '初九', 10 => '初十', 11 => '十一', 12 => '十二',
        13 => '十三', 14 => '十四', 15 => '十五', 16 => '十六', 17 => '十七', 18 => '十八',
        19 => '十九', 20 => '二十', 21 => '廿一', 22 => '廿二', 23 => '廿三', 24 => '廿四',
        25 => '廿五', 26 => '廿六', 27 => '廿七', 28 => '廿八', 29 => '廿九', 30 => '三十',
    ];

    private const SOLAR_TERMS = [
        '小寒', '大寒', '立春', '雨水', '惊蛰', '春分',
        '清明', '谷雨', '立夏', '小满', '芒种', '夏至',
        '小暑', '大暑', '立秋', '处暑', '白露', '秋分',
        '寒露', '霜降', '立冬', '小雪', '大雪', '冬至',
    ];

    private const ZODIAC_ANIMALS = [
        'Rat', 'Ox', 'Tiger', 'Rabbit', 'Dragon', 'Snake',
        'Horse', 'Goat', 'Monkey', 'Rooster', 'Dog', 'Pig',
    ];

    private const HEAVENLY_STEMS = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

    private const EARTHLY_BRANCHES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    public function fromGregorian(Carbon $date): array
    {
        $date = $this->ensureTimezone($date, 'Asia/Shanghai');

        try {
            $chineseDate = $this->calculateChineseDate($date);
            $zodiac = $this->calculateZodiac($chineseDate['year']);
            $solarTerm = $this->getCurrentSolarTerm($date);

            return [
                'year' => $chineseDate['year'],
                'month' => $chineseDate['month'],
                'month_name' => $chineseDate['month_name'],
                'day' => $chineseDate['day'],
                'day_name' => $chineseDate['day_name'],
                'is_leap_month' => $chineseDate['is_leap_month'],
                'zodiac_animal' => $zodiac['animal'],
                'zodiac_element' => $zodiac['element'],
                'solar_term' => $solarTerm,
                'full_date' => $this->formatChineseDate($chineseDate),
                'display' => $this->getDisplayDate($chineseDate, $zodiac),
            ];
        } catch (CalendarCalculationException $e) {
            throw $e; // Re-throw specific exceptions
        } catch (\Exception $e) {
            $this->handleCalculationError(
                'Unable to calculate Chinese calendar date: '.$e->getMessage(),
                $e
            );
        }
    }

    public function toGregorian(array $dateData): Carbon
    {
        $year = $dateData['year'] ?? $dateData['chinese_year'] ?? null;
        $month = $dateData['month'] ?? $dateData['chinese_month'] ?? null;
        $day = $dateData['day'] ?? $dateData['chinese_day'] ?? null;
        $isLeapMonth = $dateData['is_leap_month'] ?? false;

        if (! $year || ! $month || ! $day) {
            $this->handleValidationError('Invalid Chinese date components');
        }

        if (! $this->validateDate($year, $month, $day)) {
            $this->handleValidationError("Invalid Chinese date: {$year}/{$month}/{$day}");
        }

        try {
            return $this->calculateGregorianFromChinese($year, $month, $day, $isLeapMonth);
        } catch (\Exception $e) {
            $this->handleCalculationError(
                'Unable to convert Chinese date to Gregorian: '.$e->getMessage(),
                $e
            );
        }
    }

    public function getKey(): string
    {
        return 'chinese';
    }

    public function getName(): string
    {
        return 'Chinese Calendar';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        if ($year < 1900 || $year > 2100 || $month < 1 || $month > 12 || $day < 1 || $day > 30) {
            return false;
        }

        // Simplified validation - in production this would check actual month lengths
        return $day <= $this->getDaysInMonth($year, $month);
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        // Simplified - in production this would calculate actual lunar month length
        return $month == 2 ? 29 : 30;
    }

    /**
     * Calculate Chinese date from Gregorian using astronomical algorithms
     */
    private function calculateChineseDate(Carbon $date): array
    {
        // Simplified calculation - in production would use precise astronomical algorithms
        $year = $date->year;
        $month = $date->month;
        $day = $date->day;

        // Approximate conversion (simplified for implementation)
        // In production, this would use complex astronomical calculations
        $chineseYear = $this->calculateChineseYear($date);
        $chineseMonth = $this->calculateChineseMonth($date, $chineseYear);
        $chineseDay = $this->calculateChineseDay($date);

        $isLeapMonth = $this->isLeapMonth($chineseYear, $chineseMonth);

        return [
            'year' => $chineseYear,
            'month' => $chineseMonth,
            'month_name' => self::CHINESE_MONTH_NAMES[$chineseMonth] ?? '未知',
            'day' => $chineseDay,
            'day_name' => self::CHINESE_DAY_NAMES[$chineseDay] ?? '未知',
            'is_leap_month' => $isLeapMonth,
        ];
    }

    /**
     * Calculate Chinese year from Gregorian date
     */
    private function calculateChineseYear(Carbon $date): int
    {
        // Chinese New Year typically falls between Jan 21 and Feb 20
        $year = $date->year;
        $chineseNewYear = $this->calculateChineseNewYear($year);

        // If date is before Chinese New Year, it belongs to previous Chinese year
        if ($date->lt($chineseNewYear)) {
            return $year - 1;
        }

        return $year;
    }

    /**
     * Calculate Chinese New Year date for a given Gregorian year
     */
    private function calculateChineseNewYear(int $gregorianYear): Carbon
    {
        // Simplified calculation - in production would use precise astronomical new moon
        // Chinese New Year is the second new moon after winter solstice
        $winterSolstice = Carbon::create($gregorianYear - 1, 12, 21, 0, 0, 0, 'Asia/Shanghai');

        // Approximate: 1st new moon ~29.5 days after winter solstice
        $firstNewMoon = $winterSolstice->copy()->addDays(29);
        // 2nd new moon ~59 days after winter solstice (Chinese New Year)
        $chineseNewYear = $winterSolstice->copy()->addDays(59);

        return $chineseNewYear;
    }

    /**
     * Calculate Chinese month from Gregorian date
     */
    private function calculateChineseMonth(Carbon $date, int $chineseYear): int
    {
        $chineseNewYear = $this->calculateChineseNewYear($chineseYear + 1);
        $monthsPassed = $date->diffInMonths($chineseNewYear->copy()->subYear());

        return 12 - ($monthsPassed % 12);
    }

    /**
     * Calculate Chinese day from Gregorian date (simplified)
     */
    private function calculateChineseDay(Carbon $date): int
    {
        // Simplified - in production would use precise lunar calculations
        return $date->day;
    }

    /**
     * Check if a month is a leap month in the Chinese calendar
     */
    private function isLeapMonth(int $chineseYear, int $month): bool
    {
        // Simplified leap month calculation
        // In production, this would use the 19-year Metonic cycle
        $metonicCycle = $chineseYear % 19;
        $leapMonths = [0, 3, 6, 9, 11, 14, 17]; // Common leap months in Metonic cycle

        return in_array($metonicCycle, $leapMonths) && $month == 7; // Example: 7th month is leap
    }

    /**
     * Calculate Chinese zodiac animal and element
     */
    private function calculateZodiac(int $chineseYear): array
    {
        $zodiacIndex = ($chineseYear - 4) % 12;
        $stemIndex = ($chineseYear - 4) % 10;
        $branchIndex = ($chineseYear - 4) % 12;

        return [
            'animal' => self::ZODIAC_ANIMALS[$zodiacIndex] ?? 'Unknown',
            'element' => $this->getElementFromStem(self::HEAVENLY_STEMS[$stemIndex] ?? '甲'),
            'heavenly_stem' => self::HEAVENLY_STEMS[$stemIndex] ?? '甲',
            'earthly_branch' => self::EARTHLY_BRANCHES[$branchIndex] ?? '子',
        ];
    }

    private function getElementFromStem(string $stem): string
    {
        $elements = [
            '甲' => 'Wood', '乙' => 'Wood', '丙' => 'Fire', '丁' => 'Fire',
            '戊' => 'Earth', '己' => 'Earth', '庚' => 'Metal', '辛' => 'Metal',
            '壬' => 'Water', '癸' => 'Water',
        ];

        return $elements[$stem] ?? 'Unknown';
    }

    /**
     * Get current solar term for a date
     */
    private function getCurrentSolarTerm(Carbon $date): string
    {
        $dayOfYear = $date->dayOfYear;
        $terms = [
            5 => '小寒', 20 => '大寒', 34 => '立春', 50 => '雨水',
            64 => '惊蛰', 79 => '春分', 94 => '清明', 109 => '谷雨',
            119 => '立夏', 134 => '小满', 149 => '芒种', 164 => '夏至',
            174 => '小暑', 189 => '大暑', 204 => '立秋', 219 => '处暑',
            234 => '白露', 249 => '秋分', 264 => '寒露', 279 => '霜降',
            294 => '立冬', 309 => '小雪', 324 => '大雪', 339 => '冬至',
        ];

        foreach ($terms as $day => $term) {
            if ($dayOfYear >= $day && $dayOfYear < $day + 15) {
                return $term;
            }
        }

        return '冬至'; // Default
    }

    private function formatChineseDate(array $chineseDate): string
    {
        $leapIndicator = $chineseDate['is_leap_month'] ? '闰' : '';

        return "{$chineseDate['year']}/{$leapIndicator}{$chineseDate['month']}/{$chineseDate['day']}";
    }

    private function getDisplayDate(array $chineseDate, array $zodiac): string
    {
        $leapIndicator = $chineseDate['is_leap_month'] ? '闰' : '';

        return "{$chineseDate['year']}年 {$leapIndicator}{$chineseDate['month_name']} {$chineseDate['day_name']} ({$zodiac['animal']}年)";
    }

    /**
     * Calculate Gregorian date from Chinese date (simplified)
     */
    private function calculateGregorianFromChinese(int $year, int $month, int $day, bool $isLeapMonth): Carbon
    {
        // Simplified reverse calculation
        $chineseNewYear = $this->calculateChineseNewYear($year);

        // Approximate: each Chinese month is ~29.5 days
        $daysFromNewYear = (($month - 1) * 29.5) + ($day - 1);

        return $chineseNewYear->copy()->addDays($daysFromNewYear);
    }

    public function getTags(): array
    {
        return ['east-asian', 'lunisolar'];
    }
}
