<?php

namespace Database\Factories;

use App\Models\SavedConversion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SavedConversion>
 */
class SavedConversionFactory extends Factory
{
    protected $model = SavedConversion::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_input' => $this->faker->date('Y-m-d'),
            'gregorian_date' => $this->faker->date('Y-m-d'),
            'jalali_date' => $this->faker->date('Y/m/d'),
            'unix_timestamp' => $this->faker->unixTime(),
            'iso8601_string' => $this->faker->iso8601(),
        ];
    }
}
