<?php

namespace Database\Seeders;

use App\Models\JapaneseEra;
use Illuminate\Database\Seeder;

class JapaneseEraSeeder extends Seeder
{
    public function run(): void
    {
        $eras = [
            [
                'name_japanese' => '令和',
                'name_romanized' => 'Reiwa',
                'name_english' => 'Reiwa',
                'start_date' => '2019-05-01',
                'end_date' => null,
                'historical_context' => 'Era of Beautiful Harmony - Current era starting with Emperor Naruhito',
            ],
            [
                'name_japanese' => '平成',
                'name_romanized' => 'Heisei', 
                'name_english' => 'Heisei',
                'start_date' => '1989-01-08',
                'end_date' => '2019-04-30',
                'historical_context' => 'Era of Achieving Peace - Reign of Emperor Akihito',
            ],
            [
                'name_japanese' => '昭和',
                'name_romanized' => 'Shōwa',
                'name_english' => 'Showa', 
                'start_date' => '1926-12-25',
                'end_date' => '1989-01-07',
                'historical_context' => 'Era of Enlightened Peace - Reign of Emperor Hirohito',
            ],
            [
                'name_japanese' => '大正',
                'name_romanized' => 'Taishō',
                'name_english' => 'Taisho',
                'start_date' => '1912-07-30',
                'end_date' => '1926-12-24',
                'historical_context' => 'Era of Great Righteousness - Reign of Emperor Yoshihito',
            ],
            [
                'name_japanese' => '明治',
                'name_romanized' => 'Meiji',
                'name_english' => 'Meiji',
                'start_date' => '1868-01-25',
                'end_date' => '1912-07-29',
                'historical_context' => 'Era of Enlightened Rule - Reign of Emperor Meiji',
            ],
        ];

        foreach ($eras as $era) {
            JapaneseEra::create($era);
        }
    }
}
