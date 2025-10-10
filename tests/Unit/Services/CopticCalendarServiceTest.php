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
        $this->assertEquals(13, $copticDate['month']);
        $this->assertEquals('Epagomenai', $copticDate['month_name']);
        $this->assertEquals(2, $copticDate['day']);
    }

    public function test_converts_coptic_to_gregorian()
    {
        $copticDate = ['year' => 1740, 'month' => 13, 'day' => 2];
        $gregorianDate = $this->service->toGregorian($copticDate);

        $this->assertEquals('2024-08-30', $gregorianDate->format('Y-m-d'));
    }

    public function test_validates_coptic_dates()
    {
        // Valid date in a common year
        $this->assertTrue($this->service->validateDate(1739, 13, 5));
        // Invalid date in a common year
        $this->assertFalse($this->service->validateDate(1739, 13, 6));

        // Valid date in a leap year (1739 AM is a leap year)
        $this->assertTrue($this->service->validateDate(1739, 12, 30));

        // Coptic leap year is when (year % 4) === 3. 1739 % 4 = 3
        $this->assertTrue($this->service->validateDate(1739, 13, 6));
        $this->assertFalse($this->service->validateDate(1738, 13, 6));

        $this->assertFalse($this->service->validateDate(1740, 14, 1));
        $this->assertFalse($this->service->validateDate(1740, 12, 31));
    }
}
