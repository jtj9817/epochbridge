<?php

namespace App\Services\Wikimedia;

use App\Models\OnThisDayEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OnThisDayService
{
    private const DEFAULT_LANGUAGE = 'en';

    private const DEFAULT_TYPE = 'all';

    private const EXPECTED_KEYS = ['selected', 'events', 'births', 'deaths', 'holidays'];

    /**
     * @return array{data: array|null, cached: bool, error: string|null}
     */
    public function getForDate(string|\DateTimeInterface $gregorianDate, string $language = self::DEFAULT_LANGUAGE, string $type = self::DEFAULT_TYPE): array
    {
        $date = $this->normalizeDate($gregorianDate);
        $month = (int) $date->format('n');
        $day = (int) $date->format('j');
        $verbose = $this->verboseEnabled();

        $this->logVerbose('on_this_day.request.start', [
            'language' => $language,
            'type' => $type,
            'month' => $month,
            'day' => $day,
        ], $verbose);

        $this->logVerbose('on_this_day.cache.lookup', [
            'language' => $language,
            'type' => $type,
            'month' => $month,
            'day' => $day,
        ], $verbose);

        $cached = OnThisDayEntry::query()
            ->where('language', $language)
            ->where('type', $type)
            ->where('month', $month)
            ->where('day', $day)
            ->first();

        if ($cached) {
            $this->logVerbose('on_this_day.cache.hit', [
                'entry_id' => $cached->id,
                'day_info_bytes' => is_string($cached->day_info) ? strlen($cached->day_info) : 0,
                'fetched_at' => $cached->fetched_at?->toISOString(),
                'cache_age_seconds' => $cached->fetched_at ? now()->diffInSeconds($cached->fetched_at) : null,
            ], $verbose);
        } else {
            $this->logVerbose('on_this_day.cache.miss', [
                'language' => $language,
                'type' => $type,
                'month' => $month,
                'day' => $day,
            ], $verbose);
        }

        if ($cached && is_string($cached->day_info) && $cached->day_info !== '') {
            $decoded = json_decode($cached->day_info, true);

            if (is_array($decoded)) {
                if ($verbose) {
                    $sectionCounts = $this->sectionCounts($decoded);

                    $this->logVerbose('on_this_day.cache.return', [
                        'section_counts' => $sectionCounts,
                    ], $verbose);
                    $this->logVerbose('on_this_day.request.finish', [
                        'cached' => true,
                        'section_counts' => $sectionCounts,
                    ], $verbose);
                }

                return [
                    'data' => $decoded,
                    'cached' => true,
                    'error' => null,
                ];
            }

            Log::warning('On This Day cache entry has invalid JSON.', [
                'language' => $language,
                'type' => $type,
                'month' => $month,
                'day' => $day,
                'entry_id' => $cached->id,
            ]);
        }

        $token = config('services.wikimedia.token');
        $userAgent = config('services.wikimedia.user_agent')
            ?: sprintf('%s (%s)', config('app.name'), config('app.url'));
        $baseUrl = rtrim((string) config('services.wikimedia.base_url', 'https://api.wikimedia.org'), '/');

        if (! $token) {
            $this->logVerbose('on_this_day.config.missing_token', [
                'language' => $language,
                'type' => $type,
                'month' => $month,
                'day' => $day,
            ], $verbose);

            return [
                'data' => null,
                'cached' => false,
                'error' => 'Wikimedia API token is not configured.',
            ];
        }

        $path = sprintf(
            '/feed/v1/wikipedia/%s/onthisday/%s/%02d/%02d',
            $language,
            $type,
            $month,
            $day
        );

        $this->logVerbose('on_this_day.api.request', [
            'base_url' => $baseUrl,
            'path' => $path,
            'timeout_seconds' => 10,
        ], $verbose);

        $startedAt = microtime(true);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'User-Agent' => $userAgent,
                'Api-User-Agent' => $userAgent,
            ])
                ->baseUrl($baseUrl)
                ->acceptJson()
                ->timeout(10)
                ->retry(3, 1000)
                ->get($path);
        } catch (\Throwable $exception) {
            $this->logVerbose('on_this_day.api.exception', [
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'message' => $exception->getMessage(),
            ], $verbose);

            Log::error('On This Day API request failed.', [
                'language' => $language,
                'type' => $type,
                'month' => $month,
                'day' => $day,
                'error' => $exception->getMessage(),
            ]);

            return [
                'data' => null,
                'cached' => false,
                'error' => 'Unable to reach Wikimedia API right now.',
            ];
        }

        $responseDurationMs = (int) round((microtime(true) - $startedAt) * 1000);

        $this->logVerbose('on_this_day.api.response', [
            'status' => $response->status(),
            'duration_ms' => $responseDurationMs,
            'response_bytes' => strlen((string) $response->body()),
        ], $verbose);

        if (! $response->successful()) {
            Log::warning('On This Day API responded with an error.', [
                'language' => $language,
                'type' => $type,
                'month' => $month,
                'day' => $day,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'data' => null,
                'cached' => false,
                'error' => 'Wikimedia API request failed.',
            ];
        }

        $payload = $response->json();

        if (! is_array($payload) || ! $this->hasExpectedKeys($payload)) {
            if ($verbose) {
                $missingKeys = is_array($payload)
                    ? array_values(array_diff(self::EXPECTED_KEYS, array_keys($payload)))
                    : self::EXPECTED_KEYS;

                $this->logVerbose('on_this_day.api.payload.invalid', [
                    'payload_type' => gettype($payload),
                    'missing_keys' => $missingKeys,
                ], $verbose);
            }

            Log::warning('On This Day API returned unexpected payload.', [
                'language' => $language,
                'type' => $type,
                'month' => $month,
                'day' => $day,
                'payload_type' => gettype($payload),
            ]);

            return [
                'data' => null,
                'cached' => false,
                'error' => 'Wikimedia API response was incomplete.',
            ];
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            Log::warning('On This Day API payload could not be serialized.', [
                'language' => $language,
                'type' => $type,
                'month' => $month,
                'day' => $day,
            ]);
        } else {
            $entry = OnThisDayEntry::updateOrCreate(
                [
                    'language' => $language,
                    'type' => $type,
                    'month' => $month,
                    'day' => $day,
                ],
                [
                    'day_info' => $encoded,
                    'fetched_at' => now(),
                ]
            );

            $this->logVerbose('on_this_day.cache.write', [
                'entry_id' => $entry->id,
                'action' => $entry->wasRecentlyCreated ? 'created' : 'updated',
                'payload_bytes' => strlen($encoded),
                'fetched_at' => $entry->fetched_at?->toISOString(),
            ], $verbose);
        }

        if ($verbose) {
            $this->logVerbose('on_this_day.request.finish', [
                'cached' => false,
                'section_counts' => $this->sectionCounts($payload),
            ], $verbose);
        }

        return [
            'data' => $payload,
            'cached' => false,
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

    private function logVerbose(string $message, array $context = [], ?bool $enabled = null): void
    {
        $enabled = $enabled ?? $this->verboseEnabled();

        if (! $enabled) {
            return;
        }

        Log::debug($message, $context);
    }

    private function verboseEnabled(): bool
    {
        static $enabled;

        if ($enabled === null) {
            $enabled = (bool) config('services.wikimedia.verbose_logs', false);
        }

        return $enabled;
    }

    private function sectionCounts(array $payload): array
    {
        $counts = [];

        foreach (self::EXPECTED_KEYS as $key) {
            $value = $payload[$key] ?? null;
            $counts[$key] = is_array($value) ? count($value) : 0;
        }

        return $counts;
    }

    private function hasExpectedKeys(array $payload): bool
    {
        foreach (self::EXPECTED_KEYS as $key) {
            if (! array_key_exists($key, $payload)) {
                return false;
            }
        }

        return true;
    }
}
