<?php

namespace App\Http\Resources\Frontend;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Normalizes conversion results from DateTimeConverterService for React consumption.
 *
 * @property array $resource The raw conversion results
 */
class ConversionResultResource extends JsonResource
{
    private string $inputDate;

    private string $inputType;

    public function __construct(array $resource, string $inputDate, string $inputType)
    {
        parent::__construct($resource);
        $this->inputDate = $inputDate;
        $this->inputType = $inputType;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'input' => [
                'date' => $this->inputDate,
                'type' => $this->inputType,
            ],
            'results' => $this->normalizeResults($this->resource),
            'meta' => [
                'on_this_day_date' => data_get($this->resource, 'gregorian.full_date'),
            ],
        ];
    }

    /**
     * Normalize all calendar results into a consistent structure.
     *
     * @return array<string, array<string, mixed>>
     */
    private function normalizeResults(array $results): array
    {
        $normalized = [];

        foreach ($results as $key => $value) {
            $normalized[$this->normalizeKey($key)] = $this->normalizeCalendarResult($key, $value);
        }

        return $normalized;
    }

    /**
     * Normalize a calendar key to use underscores consistently.
     */
    private function normalizeKey(string $key): string
    {
        return str_replace('-', '_', $key);
    }

    /**
     * Normalize an individual calendar result.
     *
     * @return array<string, mixed>
     */
    private function normalizeCalendarResult(string $key, mixed $value): array
    {
        // Scalar values (unix, iso8601, julian-day, modified-julian-day)
        if (is_scalar($value)) {
            return $this->normalizeScalarResult($key, $value);
        }

        // Already structured calendar results
        if (is_array($value)) {
            return $this->normalizeStructuredResult($key, $value);
        }

        return [
            'key' => $this->normalizeKey($key),
            'value' => null,
            'error' => 'Unknown result format',
        ];
    }

    /**
     * Normalize scalar values (unix timestamp, iso8601, julian day numbers) into objects.
     *
     * @return array<string, mixed>
     */
    private function normalizeScalarResult(string $key, int|float|string $value): array
    {
        $normalizedKey = $this->normalizeKey($key);

        return match ($key) {
            'unix' => [
                'key' => $normalizedKey,
                'name' => 'Unix Timestamp',
                'value' => $value,
                'full_date' => (string) $value,
                'tags' => ['modern', 'numeric'],
            ],
            'iso8601' => [
                'key' => $normalizedKey,
                'name' => 'ISO 8601',
                'value' => $value,
                'full_date' => $value,
                'tags' => ['modern', 'standard'],
            ],
            'julian-day', 'julian_day' => [
                'key' => 'julian_day',
                'name' => 'Julian Day',
                'value' => $value,
                'day_number' => $value,
                'full_date' => (string) $value,
                'tags' => ['astronomical', 'numeric'],
            ],
            'modified-julian-day', 'modified_julian_day' => [
                'key' => 'modified_julian_day',
                'name' => 'Modified Julian Day',
                'value' => $value,
                'day_number' => $value,
                'full_date' => (string) round($value, 5),
                'tags' => ['astronomical', 'numeric'],
            ],
            default => [
                'key' => $normalizedKey,
                'name' => ucfirst(str_replace(['_', '-'], ' ', $key)),
                'value' => $value,
                'full_date' => (string) $value,
                'tags' => [],
            ],
        };
    }

    /**
     * Normalize structured calendar results.
     *
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function normalizeStructuredResult(string $key, array $value): array
    {
        $normalizedKey = $this->normalizeKey($key);

        // Handle error results
        if (isset($value['error'])) {
            return [
                'key' => $normalizedKey,
                'name' => $value['name'] ?? ucfirst(str_replace(['_', '-'], ' ', $key)),
                'error' => $value['error'],
                'tags' => $value['tags'] ?? [],
            ];
        }

        // Return structured result with normalized key
        return array_merge(
            ['key' => $normalizedKey],
            $value
        );
    }
}
