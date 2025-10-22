<?php

namespace App\Services;

use App\Exceptions\CalendarCalculationException;
use App\Services\Chinese\ChineseLunisolarCalendar;
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

    /**
     * Ordered by apparent solar longitude bins (0° inclusive, 15° per segment).
     */
    private const SOLAR_TERMS = [
        '春分', '清明', '谷雨', '立夏', '小满', '芒种',
        '夏至', '小暑', '大暑', '立秋', '处暑', '白露',
        '秋分', '寒露', '霜降', '立冬', '小雪', '大雪',
        '冬至', '小寒', '大寒', '立春', '雨水', '惊蛰',
    ];

    private const ZODIAC_ANIMALS = [
        'Rat', 'Ox', 'Tiger', 'Rabbit', 'Dragon', 'Snake',
        'Horse', 'Goat', 'Monkey', 'Rooster', 'Dog', 'Pig',
    ];

    private const HEAVENLY_STEMS = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

    private const EARTHLY_BRANCHES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    private ChineseLunisolarCalendar $calendar;

    public function __construct(?ChineseLunisolarCalendar $calendar = null)
    {
        $this->calendar = $calendar ?? new ChineseLunisolarCalendar();
    }

    public function fromGregorian(Carbon $date): array
    {
        $beijing = $this->ensureTimezone($date, 'Asia/Shanghai');

        try {
            $components = $this->calendar->chineseDateFor($beijing);
            $zodiac = $this->calculateZodiac($components['year']);
            $solarTermIndex = $this->calendar->solarTermIndexFor($beijing);
            $solarTerm = self::SOLAR_TERMS[$solarTermIndex] ?? '未知节气';

            return [
                'year' => $components['year'],
                'month' => $components['month'],
                'month_name' => $this->formatMonthName($components['month'], $components['is_leap']),
                'day' => $components['day'],
                'day_name' => self::CHINESE_DAY_NAMES[$components['day']] ?? '未知',
                'is_leap_month' => $components['is_leap'],
                'zodiac_animal' => $zodiac['animal'],
                'zodiac_element' => $zodiac['element'],
                'heavenly_stem' => $zodiac['heavenly_stem'],
                'earthly_branch' => $zodiac['earthly_branch'],
                'solar_term' => $solarTerm,
                'full_date' => $this->formatChineseDateString(
                    $components['year'],
                    $components['month'],
                    $components['day'],
                    $components['is_leap']
                ),
                'display' => $this->buildDisplayString($components, $zodiac),
            ];
        } catch (CalendarCalculationException $e) {
            throw $e;
        } catch (\Throwable $e) {
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
        $isLeapMonth = (bool) ($dateData['is_leap_month'] ?? false);

        if ($year === null || $month === null || $day === null) {
            $this->handleValidationError('Invalid Chinese date components');
        }

        if (! $this->validateDate((int) $year, (int) $month, (int) $day)) {
            $this->handleValidationError("Invalid Chinese date: {$year}/{$month}/{$day}");
        }

        try {
            $gregorian = $this->calendar->gregorianDateFor((int) $year, (int) $month, (int) $day, $isLeapMonth);

            return Carbon::createFromImmutable($gregorian);
        } catch (\RuntimeException $e) {
            $this->handleValidationError($e->getMessage());
        } catch (\Throwable $e) {
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
        if ($year < 1900 || $year > 2100 || $month < 1 || $month > 12 || $day < 1) {
            return false;
        }

        try {
            $months = $this->calendar->monthsForChineseYear($year);
        } catch (\Throwable $e) {
            return false;
        }

        foreach ($months as $lunation) {
            if ($lunation->chineseYear === $year && $lunation->number === $month) {
                return $day <= $lunation->length();
            }
        }

        return false;
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        try {
            $months = $this->calendar->monthsForChineseYear($year);
        } catch (\Throwable $e) {
            return 0;
        }

        foreach ($months as $lunation) {
            if ($lunation->chineseYear === $year && $lunation->number === $month && ! $lunation->isLeap) {
                return $lunation->length();
            }
        }

        foreach ($months as $lunation) {
            if ($lunation->chineseYear === $year && $lunation->number === $month) {
                return $lunation->length();
            }
        }

        return 0;
    }

    public function getTags(): array
    {
        return ['east-asian', 'lunisolar'];
    }

    /**
     * Calculate Chinese zodiac information for the given Chinese year.
     */
    private function calculateZodiac(int $chineseYear): array
    {
        $zodiacIndex = ($chineseYear - 4) % 12;
        if ($zodiacIndex < 0) {
            $zodiacIndex += 12;
        }

        $stemIndex = ($chineseYear - 4) % 10;
        if ($stemIndex < 0) {
            $stemIndex += 10;
        }

        $branchIndex = ($chineseYear - 4) % 12;
        if ($branchIndex < 0) {
            $branchIndex += 12;
        }

        $stem = self::HEAVENLY_STEMS[$stemIndex] ?? '甲';
        $branch = self::EARTHLY_BRANCHES[$branchIndex] ?? '子';

        return [
            'animal' => self::ZODIAC_ANIMALS[$zodiacIndex] ?? 'Unknown',
            'element' => $this->getElementFromStem($stem),
            'heavenly_stem' => $stem,
            'earthly_branch' => $branch,
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

    private function formatMonthName(int $month, bool $isLeap): string
    {
        $base = self::CHINESE_MONTH_NAMES[$month] ?? "{$month}月";

        return $isLeap ? '闰'.$base : $base;
    }

    private function formatChineseDateString(int $year, int $month, int $day, bool $isLeap): string
    {
        $leapIndicator = $isLeap ? '闰' : '';

        return sprintf('%d/%s%d/%d', $year, $leapIndicator, $month, $day);
    }

    /**
     * Build human-readable display string.
     *
     * @param  array{year:int,month:int,day:int,is_leap:bool,month_length:int,month_start:\Carbon\CarbonImmutable,month_end:\Carbon\CarbonImmutable}  $components
     * @param  array{animal:string,element:string,heavenly_stem:string,earthly_branch:string}  $zodiac
     */
    private function buildDisplayString(array $components, array $zodiac): string
    {
        $leapPrefix = $components['is_leap'] ? '闰' : '';
        $monthName = self::CHINESE_MONTH_NAMES[$components['month']] ?? "{$components['month']}月";
        $dayName = self::CHINESE_DAY_NAMES[$components['day']] ?? "{$components['day']}日";

        return sprintf(
            '%d年 %s%s %s (%s%s年)',
            $components['year'],
            $leapPrefix,
            $monthName,
            $dayName,
            $zodiac['heavenly_stem'],
            $zodiac['earthly_branch']
        );
    }
}
