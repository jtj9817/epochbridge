<?php

namespace Database\Seeders;

use App\Models\JapaneseEra;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class JapaneseEraSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('japanese_eras.json');

        if (! File::exists($path)) {
            throw new \RuntimeException('japanese_eras.json not found at project root.');
        }

        $raw = File::get($path);
        $eras = json_decode($raw, true);

        if (! is_array($eras) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Unable to decode japanese_eras.json.');
        }

        $overrides = [
            'Meiji' => ['start_date' => '1868-01-25', 'end_date' => '1912-07-29'],
            'Taishō' => ['start_date' => '1912-07-30', 'end_date' => '1926-12-24'],
            'Shōwa' => ['start_date' => '1926-12-25', 'end_date' => '1989-01-07'],
            'Heisei' => ['start_date' => '1989-01-08', 'end_date' => '2019-04-30'],
            'Reiwa' => ['start_date' => '2019-05-01', 'end_date' => null],
        ];

        JapaneseEra::query()->delete();

        $now = now();
        $records = [];

        foreach ($eras as $index => $era) {
            $name = $era['name'] ?? null;
            $kanji = $era['kanji'] ?? null;
            $year = $era['year'] ?? null;

            if (! $name || ! $kanji || ! $year) {
                continue;
            }

            $startDate = Carbon::createFromDate((int) $year, 1, 1, 'Asia/Tokyo');
            $nextEra = $eras[$index + 1] ?? null;
            $endDate = null;

            if ($nextEra && isset($nextEra['year'])) {
                $endDate = Carbon::createFromDate((int) $nextEra['year'], 1, 1, 'Asia/Tokyo')->subDay();
            }

            if (isset($overrides[$name]['start_date'])) {
                $startDate = Carbon::parse($overrides[$name]['start_date'], 'Asia/Tokyo');
            }

            if (array_key_exists($name, $overrides) && array_key_exists('end_date', $overrides[$name])) {
                $overrideEnd = $overrides[$name]['end_date'];
                $endDate = $overrideEnd ? Carbon::parse($overrideEnd, 'Asia/Tokyo') : null;
            }

            $records[] = [
                'name_japanese' => $kanji,
                'name_romanized' => $name,
                'name_english' => Str::ascii($name) ?: $name,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate?->toDateString(),
                'historical_context' => isset($era['faction']) ? 'Faction: '.$era['faction'] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        JapaneseEra::insert($records);
    }
}
