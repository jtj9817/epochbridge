<?php

namespace Tests\Unit\Services;

use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
use Tests\TestCase;

class EthiopianCalendarServiceTest extends TestCase
{
    private EthiopianCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EthiopianCalendarService;
    }

    public function test_converts_gregorian_to_ethiopian()
    {
        // Ethiopian New Year is usually Sep 11 (or 12 in leap year)
        $gregorianDate = Carbon::create(2024, 9, 12);
        $ethiopianDate = $this->service->fromGregorian($gregorianDate);

        $this->assertEquals(2017, $ethiopianDate['year']);
        $this->assertEquals(1, $ethiopianDate['month']);
        $this->assertEquals('Mäskäräm', $ethiopianDate['month_name']);
        $this->assertEquals(2, $ethiopianDate['day']);
    }

    public function test_round_trips_between_calendars()
    {
        $gregorianDate = Carbon::create(2024, 9, 12);
        $ethiopianDate = $this->service->fromGregorian($gregorianDate);
        $roundTrip = $this->service->toGregorian($ethiopianDate);

        $this->assertEquals($gregorianDate->format('Y-m-d'), $roundTrip->format('Y-m-d'));
    }

    public function test_validates_ethiopian_dates()
    {
        // Valid date in a common year (2016 is common)
        $this->assertTrue($this->service->validateDate(2016, 13, 5));
        $this->assertFalse($this->service->validateDate(2016, 13, 6));

        // Valid date in a leap year (2015 is a leap year)
        // Ethiopian leap year is when (year % 4) === 3. 2015 % 4 = 3
        $this->assertTrue($this->service->validateDate(2015, 13, 6));

        // Invalid dates
        $this->assertFalse($this->service->validateDate(2016, 14, 1));
        $this->assertFalse($this->service->validateDate(2016, 12, 31));
    }
}
