<?php

namespace Tests\Unit\Services;

use App\Services\ChineseCalendarService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChineseCalendarServiceTest extends TestCase
{
    private ChineseCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChineseCalendarService;
    }

    /**
     * @return array<string, array{0:\Illuminate\Support\Carbon,1:array<string, mixed>}>
     */
    public static function provideKnownNewYearDates(): array
    {
        return [
            '2020-metal-rat' => [
                Carbon::create(2020, 1, 25, 0, 0, 0, 'Asia/Shanghai'),
                [
                    'year' => 2020,
                    'month' => 1,
                    'day' => 1,
                    'is_leap_month' => false,
                    'zodiac_animal' => 'Rat',
                    'zodiac_element' => 'Metal',
                    'heavenly_stem' => '庚',
                    'earthly_branch' => '子',
                    'solar_term' => '大寒',
                ],
            ],
            '2021-metal-ox' => [
                Carbon::create(2021, 2, 12, 0, 0, 0, 'Asia/Shanghai'),
                [
                    'year' => 2021,
                    'month' => 1,
                    'day' => 1,
                    'is_leap_month' => false,
                    'zodiac_animal' => 'Ox',
                    'zodiac_element' => 'Metal',
                    'heavenly_stem' => '辛',
                    'earthly_branch' => '丑',
                    'solar_term' => '立春',
                ],
            ],
            '2022-water-tiger' => [
                Carbon::create(2022, 2, 1, 0, 0, 0, 'Asia/Shanghai'),
                [
                    'year' => 2022,
                    'month' => 1,
                    'day' => 1,
                    'is_leap_month' => false,
                    'zodiac_animal' => 'Tiger',
                    'zodiac_element' => 'Water',
                    'heavenly_stem' => '壬',
                    'earthly_branch' => '寅',
                    'solar_term' => '大寒',
                ],
            ],
            '2024-wood-dragon' => [
                Carbon::create(2024, 2, 10, 9, 0, 0, 'Asia/Shanghai'),
                [
                    'year' => 2024,
                    'month' => 1,
                    'day' => 1,
                    'is_leap_month' => false,
                    'zodiac_animal' => 'Dragon',
                    'zodiac_element' => 'Wood',
                    'heavenly_stem' => '甲',
                    'earthly_branch' => '辰',
                    'solar_term' => '立春',
                ],
            ],
        ];
    }

    #[DataProvider('provideKnownNewYearDates')]
    public function test_from_gregorian_matches_known_new_years(Carbon $date, array $expected): void
    {
        $result = $this->service->fromGregorian($date);

        foreach ($expected as $key => $value) {
            $this->assertSame($value, $result[$key], sprintf('Mismatched %s for %s', $key, $date->toDateString()));
        }
    }

    public function test_from_gregorian_detects_leap_month_and_solar_term(): void
    {
        $date = Carbon::create(2023, 4, 5, 12, 0, 0, 'Asia/Shanghai');

        $result = $this->service->fromGregorian($date);

        $this->assertSame(2023, $result['year']);
        $this->assertSame(2, $result['month']);
        $this->assertTrue($result['is_leap_month'], 'Expected leap month for April 5, 2023');
        $this->assertSame(15, $result['day']);
        $this->assertSame('清明', $result['solar_term']);
    }

    public function test_from_gregorian_respects_year_boundary_before_new_year(): void
    {
        $date = Carbon::create(2024, 2, 9, 12, 0, 0, 'Asia/Shanghai');

        $result = $this->service->fromGregorian($date);

        $this->assertSame(2023, $result['year']);
        $this->assertSame(12, $result['month']);
        $this->assertSame(30, $result['day']);
        $this->assertFalse($result['is_leap_month']);
        $this->assertSame('立春', $result['solar_term']);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function provideChineseToGregorianMappings(): array
    {
        return [
            '2020-new-year' => [
                ['year' => 2020, 'month' => 1, 'day' => 1, 'is_leap_month' => false],
                '2020-01-25',
            ],
            '2021-new-year' => [
                ['year' => 2021, 'month' => 1, 'day' => 1, 'is_leap_month' => false],
                '2021-02-12',
            ],
            '2022-new-year' => [
                ['year' => 2022, 'month' => 1, 'day' => 1, 'is_leap_month' => false],
                '2022-02-01',
            ],
            '2024-new-year' => [
                ['year' => 2024, 'month' => 1, 'day' => 1, 'is_leap_month' => false],
                '2024-02-10',
            ],
        ];
    }

    #[DataProvider('provideChineseToGregorianMappings')]
    public function test_to_gregorian_matches_known_new_year_dates(array $input, string $expectedDate): void
    {
        $gregorian = $this->service->toGregorian($input);

        $this->assertSame($expectedDate, $gregorian->setTimezone('Asia/Shanghai')->toDateString());
    }

    public function test_to_gregorian_handles_leap_month_conversion(): void
    {
        $gregorian = $this->service->toGregorian([
            'year' => 2023,
            'month' => 2,
            'day' => 10,
            'is_leap_month' => true,
        ]);

        $this->assertSame('2023-03-31', $gregorian->setTimezone('Asia/Shanghai')->toDateString());
    }

    public function test_validate_date_and_month_lengths(): void
    {
        $this->assertTrue($this->service->validateDate(2023, 2, 20));
        $this->assertFalse($this->service->validateDate(2023, 2, 31));
        $this->assertFalse($this->service->validateDate(2023, 13, 5));

        $this->assertSame(30, $this->service->getDaysInMonth(2023, 2));
        $this->assertSame(29, $this->service->getDaysInMonth(2023, 1));
    }
}
