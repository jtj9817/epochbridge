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

    public function test_converts_gregorian_to_chinese()
    {
        // Test Chinese New Year 2023 (Jan 22, 2023)
        $date = Carbon::create(2023, 1, 22);
        $result = $this->service->fromGregorian($date);

        $this->assertArrayHasKey('year', $result);
        $this->assertArrayHasKey('month', $result);
        $this->assertArrayHasKey('day', $result);
        $this->assertArrayHasKey('zodiac_animal', $result);
        $this->assertArrayHasKey('zodiac_element', $result);
    }

    public function test_calculates_zodiac_animals_correctly()
    {
        // 2023 is Year of the Rabbit
        $date = Carbon::create(2023, 6, 15);
        $result = $this->service->fromGregorian($date);

        $this->assertEquals('Rabbit', $result['zodiac_animal']);
    }

    public function test_handles_leap_months()
    {
        // Test a known leap month year
        $date = Carbon::create(2023, 7, 1);
        $result = $this->service->fromGregorian($date);

        $this->assertArrayHasKey('is_leap_month', $result);
    }

    public function test_validates_chinese_dates()
    {
        $this->assertTrue($this->service->validateDate(2023, 1, 15));
        $this->assertFalse($this->service->validateDate(2023, 13, 15)); // Invalid month
        $this->assertFalse($this->service->validateDate(2023, 1, 32)); // Invalid day
    }
}
