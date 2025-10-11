<?php

namespace Tests\Unit\Services;

use App\Services\CopticCalendarService;
use Carbon\Carbon;
use Tests\TestCase;

class CopticCalendarServiceTest extends TestCase
{
    private CopticCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CopticCalendarService;
    }

    public function test_converts_gregorian_to_coptic()
    {
        $gregorianDate = Carbon::create(2024, 8, 30);
        $copticDate = $this->service->fromGregorian($gregorianDate);

        $this->assertEquals(1740, $copticDate['year']);
        $this->assertEquals(12, $copticDate['month']);
        $this->assertEquals('Mesori', $copticDate['month_name']);
        $this->assertEquals(24, $copticDate['day']);
    }

    public function test_converts_coptic_to_gregorian()
    {
        $copticDate = ['year' => 1740, 'month' => 12, 'day' => 24];
        $gregorianDate = $this->service->toGregorian($copticDate);

        $this->assertEquals('2024-08-30', $gregorianDate->format('Y-m-d'));
    }

    public function test_validates_coptic_dates()
    {
        // Valid date in a common year (month 13 limited to 5 days)
        $this->assertTrue($this->service->validateDate(1738, 13, 5));
        $this->assertFalse($this->service->validateDate(1738, 13, 6));

        // Leap year adds an extra epagomenal day when (year % 4) === 3
        $this->assertTrue($this->service->validateDate(1739, 13, 6));
        $this->assertFalse($this->service->validateDate(1739, 13, 7));

        // Guard against invalid month/day combinations
        $this->assertFalse($this->service->validateDate(1740, 14, 1));
        $this->assertFalse($this->service->validateDate(1740, 12, 31));
    }
}
