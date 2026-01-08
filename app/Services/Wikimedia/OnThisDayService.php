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

        $cached = OnThisDayEntry::query()
            ->where('language', $language)
            ->where('type', $type)
            ->where('month', $month)
            ->where('day', $day)
            ->first();

        if ($cached && is_string($cached->day_info) && $cached->day_info !== '') {
            $decoded = json_decode($cached->day_info, true);

            if (is_array($decoded)) {
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

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'User-Agent' => $userAgent,
                'Api-User-Agent' => $userAgent,
            ])
                ->baseUrl($baseUrl)
                ->acceptJson()
                ->timeout(10)
                ->get($path);
        } catch (\Throwable $exception) {
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
            OnThisDayEntry::updateOrCreate(
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
