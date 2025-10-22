<?php

namespace Database\Factories;

use App\Models\SavedConversion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Morilog\Jalali\Jalalian;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SavedConversion>
 */
class SavedConversionFactory extends Factory
{
    protected $model = SavedConversion::class;

    public function definition(): array
    {
        $date = Carbon::createFromTimestamp($this->faker->unixTime());
        $jalaliDate = Jalalian::fromDateTime($date)->format('Y/m/d');

        $conversionData = [
            'gregorian' => [
                'full_date' => $date->format('Y-m-d'),
                'day' => (int) $date->format('d'),
                'month_name' => $date->format('F'),
                'day_of_week' => $date->format('l'),
                'name' => 'Gregorian',
                'tags' => ['modern', 'solar'],
            ],
            'jalali' => [
                'full_date' => $jalaliDate,
                'name' => 'Jalali',
                'tags' => ['modern', 'solar'],
            ],
            'unix' => $date->getTimestamp(),
            'iso8601' => $date->clone()->utc()->toIso8601String(),
        ];

        return [
            'user_id' => User::factory(),
            'original_input' => $date->format('Y-m-d'),
            'conversion_data' => $conversionData,
            'gregorian_date' => $conversionData['gregorian']['full_date'],
            'jalali_date' => $conversionData['jalali']['full_date'],
            'unix_timestamp' => $conversionData['unix'],
            'iso8601_string' => $conversionData['iso8601'],
        ];
    }
}
