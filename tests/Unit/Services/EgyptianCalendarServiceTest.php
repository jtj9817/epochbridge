<?php

namespace Tests\Unit\Services;

use App\Services\EgyptianCalendarService;
use Carbon\Carbon;
use Tests\TestCase;

class EgyptianCalendarServiceTest extends TestCase
{
    private EgyptianCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EgyptianCalendarService;
    }

    public function test_converts_gregorian_to_egyptian()
    {
        $gregorianDate = Carbon::create(2024, 8, 30);
        $egyptianDate = $this->service->fromGregorian($gregorianDate);

        // Year relative to 747 BCE
        $this->assertEquals(2771, $egyptianDate['year']);
        $this->assertEquals('Thoth', $egyptianDate['month_name']);
    }

    public function test_converts_egyptian_to_gregorian()
    {
        $egyptianDate = ['year' => 2771, 'month' => 1, 'day' => 23];
        $gregorianDate = $this->service->toGregorian($egyptianDate);

        // Note: This is an approximation. Reverse conversion is complex.
        $this->assertEquals('2024-02-17', $gregorianDate->format('Y-m-d'));
    }

    public function test_validates_egyptian_dates()
    {
        // Valid date
        $this->assertTrue($this->service->validateDate(2770, 12, 30));
        // Valid date in epagomenal days
        $this->assertTrue($this->service->validateDate(2770, 13, 5));

        // Invalid day
        $this->assertFalse($this->service->validateDate(2770, 1, 31));
        // Invalid epagomenal day
        $this->assertFalse($this->service->validateDate(2770, 13, 6));
        // Invalid month
        $this->assertFalse($this->service->validateDate(2770, 14, 1));
    }
}
