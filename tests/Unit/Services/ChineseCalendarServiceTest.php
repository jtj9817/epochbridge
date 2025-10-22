<?php

namespace Tests\Unit\Services;

use App\Services\ChineseCalendarService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChineseCalendarServiceTest extends TestCase
{
    private ChineseCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChineseCalendarService;
    }

    public function test_from_gregorian_returns_expected_components_for_new_year_2024(): void
    {
        $date = Carbon::create(2024, 2, 10, 9, 0, 0, 'Asia/Shanghai');

        $result = $this->service->fromGregorian($date);

        $this->assertSame(2024, $result['year']);
        $this->assertSame(1, $result['month']);
        $this->assertSame(1, $result['day']);
        $this->assertFalse($result['is_leap_month']);
        $this->assertSame('Dragon', $result['zodiac_animal']);
        $this->assertSame('Wood', $result['zodiac_element']);
        $this->assertSame('甲', $result['heavenly_stem']);
        $this->assertSame('辰', $result['earthly_branch']);
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

    public function test_to_gregorian_converts_known_new_year_date(): void
    {
        $gregorian = $this->service->toGregorian([
            'year' => 2024,
            'month' => 1,
            'day' => 1,
            'is_leap_month' => false,
        ]);

        $this->assertSame('2024-02-10', $gregorian->setTimezone('Asia/Shanghai')->toDateString());
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
