<?php

namespace App\Services\Wikimedia;

use App\Models\OnThisDayEntry;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;

class OnThisDayCacheService
{
    private const DEFAULT_LANGUAGE = 'en';
    private const DEFAULT_TYPE = 'all';

    /**
     * @return array{data: array|null, cached: bool, error: string|null}
     */
    public function getForDate(string|\DateTimeInterface $gregorianDate, string $language = self::DEFAULT_LANGUAGE, string $type = self::DEFAULT_TYPE): array
    {
        $date = $this->normalizeDate($gregorianDate);
        $month = (int) $date->format('n');
        $day = (int) $date->format('j');

        $queryStartedAt = microtime(true);
        $entry = OnThisDayEntry::query()
            ->where('language', $language)
            ->where('type', $type)
            ->where('month', $month)
            ->where('day', $day)
            ->first();
        $queryDurationMs = (int) round((microtime(true) - $queryStartedAt) * 1000);

        if (! $entry) {
            Log::info('On This Day cache timing.', [
                'date' => $date->toDateString(),
                'language' => $language,
                'type' => $type,
                'query_ms' => $queryDurationMs,
                'decode_ms' => null,
                'cache_hit' => false,
            ]);

            return [
                'data' => [],
                'cached' => false,
                'error' => null,
            ];
        }

        if (! is_string($entry->day_info) || $entry->day_info === '') {
            Log::warning('On This Day cache entry missing day_info.', [
                'entry_id' => $entry->id,
                'language' => $language,
                'type' => $type,
                'month' => $month,
                'day' => $day,
            ]);

            return [
                'data' => [],
                'cached' => true,
                'error' => null,
            ];
        }

        $decodeStartedAt = microtime(true);
        $decoded = json_decode($entry->day_info, true);
        $decodeDurationMs = (int) round((microtime(true) - $decodeStartedAt) * 1000);

        if (! is_array($decoded)) {
            Log::warning('On This Day cache entry has invalid JSON.', [
                'entry_id' => $entry->id,
                'language' => $language,
                'type' => $type,
                'month' => $month,
                'day' => $day,
            ]);

            Log::info('On This Day cache timing.', [
                'date' => $date->toDateString(),
                'language' => $language,
                'type' => $type,
                'entry_id' => $entry->id,
                'query_ms' => $queryDurationMs,
                'decode_ms' => $decodeDurationMs,
                'cache_hit' => true,
            ]);

            return [
                'data' => null,
                'cached' => true,
                'error' => 'On This Day data is unavailable right now.',
            ];
        }

        Log::info('On This Day cache timing.', [
            'date' => $date->toDateString(),
            'language' => $language,
            'type' => $type,
            'entry_id' => $entry->id,
            'query_ms' => $queryDurationMs,
            'decode_ms' => $decodeDurationMs,
            'cache_hit' => true,
        ]);

        return [
            'data' => $decoded,
            'cached' => true,
            'error' => null,
        ];
    }

    private function normalizeDate(string|\DateTimeInterface $gregorianDate): CarbonImmutable
    {
        if ($gregorianDate instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($gregorianDate)->startOfDay();
        }

        $parsed = CarbonImmutable::createFromFormat('Y-m-d', (string) $gregorianDate);

        if (! $parsed) {
            throw new InvalidArgumentException('Invalid Gregorian date provided.');
        }

        return $parsed->startOfDay();
    }
}
