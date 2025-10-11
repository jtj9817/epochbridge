<?php

namespace Tests\Unit\Services;

use App\Models\JapaneseEra;
use App\Services\JapaneseCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class JapaneseCalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    private JapaneseCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new JapaneseCalendarService;

        // Seed test eras
        $this->seedJapaneseEras();
    }

    private function seedJapaneseEras(): void
    {
        JapaneseEra::create([
            'name_japanese' => '平成',
            'name_romanized' => 'Heisei',
            'name_english' => 'Heisei',
            'start_date' => '1989-01-08',
            'end_date' => '2019-04-30',
            'historical_context' => 'Pre-Reiwa era',
        ]);

        JapaneseEra::create([
            'name_japanese' => '令和',
            'name_romanized' => 'Reiwa',
            'name_english' => 'Reiwa',
            'start_date' => '2019-05-01',
            'end_date' => null,
            'historical_context' => 'Test era',
        ]);
    }

    public function test_converts_gregorian_to_japanese_era()
    {
        $date = Carbon::create(2023, 6, 15);
        $result = $this->service->fromGregorian($date);

        $this->assertEquals('Reiwa', $result['era_romanized']);
        $this->assertEquals(5, $result['year']); // 2023 - 2018 = 5
        $this->assertTrue($result['is_current_era']);
    }

    public function test_converts_japanese_era_to_gregorian()
    {
        $dateData = [
            'era' => 'Reiwa',
            'year' => 5,
            'month' => 6,
            'day' => 15,
        ];

        $result = $this->service->toGregorian($dateData);

        $this->assertEquals(2023, $result->year);
        $this->assertEquals(6, $result->month);
        $this->assertEquals(15, $result->day);
    }

    public function test_handles_era_transitions()
    {
        // Test date just before Reiwa era
        $date = Carbon::create(2019, 4, 30);
        $result = $this->service->fromGregorian($date);

        $this->assertEquals('Heisei', $result['era_romanized']);
    }

    public function test_validates_japanese_dates()
    {
        $this->assertTrue($this->service->validateDate(5, 6, 15));
        $this->assertFalse($this->service->validateDate(5, 13, 15)); // Invalid month
    }
}
