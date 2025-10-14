<?php

namespace Tests\Unit\Services;

use App\Exceptions\CalendarRangeException;
use App\Services\FrenchRepublicanService;
use Carbon\Carbon;
use Tests\TestCase;

class FrenchRepublicanServiceTest extends TestCase
{
    private FrenchRepublicanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FrenchRepublicanService::class);
    }

    public function test_converts_gregorian_to_french_republican(): void
    {
        $gregorianDate = Carbon::create(1799, 11, 9, 0, 0, 0, 'UTC');

        $result = $this->service->fromGregorian($gregorianDate);

        $this->assertSame(8, $result['year']);
        $this->assertSame(2, $result['month']);
        $this->assertSame(18, $result['day']);
        $this->assertSame('Brumaire', $result['month_name']);
        $this->assertSame('Octidi', $result['day_of_week']);
        $this->assertSame(8, $result['decade_day']);
        $this->assertFalse($result['is_leap_year']);
    }

    public function test_converts_gregorian_to_complementary_day(): void
    {
        $gregorianDate = Carbon::create(1799, 9, 22, 0, 0, 0, 'UTC');

        $result = $this->service->fromGregorian($gregorianDate);

        $this->assertSame(7, $result['year']);
        $this->assertSame(13, $result['month']);
        $this->assertSame(6, $result['day']);
        $this->assertSame('Sansculottides', $result['month_name']);
        $this->assertSame('Jour de la Révolution', $result['day_of_week']);
        $this->assertNull($result['decade_day']);
        $this->assertTrue($result['is_leap_year']);
    }

    public function test_converts_french_republican_to_gregorian(): void
    {
        $frenchDate = [
            'year' => 8,
            'month' => 2,
            'day' => 18,
        ];

        $gregorian = $this->service->toGregorian($frenchDate);

        $this->assertEquals('1799-11-09', $gregorian->format('Y-m-d'));
    }

    public function test_rejects_gregorian_date_outside_supported_range(): void
    {
        $this->expectException(CalendarRangeException::class);

        $gregorianDate = Carbon::create(1806, 1, 1, 0, 0, 0, 'UTC');
        $this->service->fromGregorian($gregorianDate);
    }

    public function test_rejects_french_date_outside_supported_range(): void
    {
        $this->expectException(CalendarRangeException::class);

        $this->service->toGregorian([
            'year' => 15,
            'month' => 1,
            'day' => 1,
        ]);
    }

    public function test_validates_complementary_days(): void
    {
        $this->assertTrue($this->service->validateDate(7, 13, 6));
        $this->assertFalse($this->service->validateDate(7, 13, 7));
    }
}
