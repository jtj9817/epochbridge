<?php

namespace App\Http\Resources\Frontend;

use App\Models\SavedConversion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Normalizes saved conversion records for React consumption.
 *
 * Handles both modern records (with full conversion_data) and legacy records
 * (with only the compatibility fields) transparently.
 *
 * @property SavedConversion $resource
 */
class SavedConversionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_input' => $this->original_input,
            'note' => $this->note ?? '',
            'conversion_data' => $this->getNormalizedConversionData(),
            'uses_legacy_fallback' => $this->usesLegacyFallback(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Get normalized conversion data, using legacy fallback if needed.
     *
     * @return array<string, mixed>
     */
    private function getNormalizedConversionData(): array
    {
        // If we have full conversion_data, normalize and return it
        if (! empty($this->conversion_data) && is_array($this->conversion_data)) {
            return $this->normalizeConversionData($this->conversion_data);
        }

        // Fall back to legacy fields
        return $this->buildFromLegacyFields();
    }

    /**
     * Determine if this record uses legacy fallback.
     */
    private function usesLegacyFallback(): bool
    {
        return empty($this->conversion_data) || ! is_array($this->conversion_data);
    }

    /**
     * Normalize conversion data keys to use underscores consistently.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeConversionData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            $normalizedKey = str_replace('-', '_', $key);

            if (is_array($value)) {
                $normalized[$normalizedKey] = array_merge(
                    ['key' => $normalizedKey],
                    $value
                );
            } else {
                // Scalar values (unix, iso8601, etc.)
                $normalized[$normalizedKey] = $this->normalizeScalarValue($normalizedKey, $value);
            }
        }

        return $normalized;
    }

    /**
     * Build conversion data from legacy compatibility fields.
     *
     * @return array<string, mixed>
     */
    private function buildFromLegacyFields(): array
    {
        $legacy = $this->resource->legacyValues();
        $data = [];

        if ($legacy['gregorian']) {
            $data['gregorian'] = [
                'key' => 'gregorian',
                'name' => 'Gregorian',
                'full_date' => $legacy['gregorian'],
                'tags' => ['modern', 'solar'],
            ];
        }

        if ($legacy['jalali']) {
            $data['jalali'] = [
                'key' => 'jalali',
                'name' => 'Jalali',
                'full_date' => $legacy['jalali'],
                'tags' => ['modern', 'solar'],
            ];
        }

        if ($legacy['unix']) {
            $data['unix'] = [
                'key' => 'unix',
                'name' => 'Unix Timestamp',
                'value' => (int) $legacy['unix'],
                'full_date' => $legacy['unix'],
                'tags' => ['modern', 'numeric'],
            ];
        }

        if ($legacy['iso8601']) {
            $data['iso8601'] = [
                'key' => 'iso8601',
                'name' => 'ISO 8601',
                'value' => $legacy['iso8601'],
                'full_date' => $legacy['iso8601'],
                'tags' => ['modern', 'standard'],
            ];
        }

        return $data;
    }

    /**
     * Normalize scalar values into structured format.
     *
     * @return array<string, mixed>
     */
    private function normalizeScalarValue(string $key, mixed $value): array
    {
        return match ($key) {
            'unix' => [
                'key' => $key,
                'name' => 'Unix Timestamp',
                'value' => $value,
                'full_date' => (string) $value,
                'tags' => ['modern', 'numeric'],
            ],
            'iso8601' => [
                'key' => $key,
                'name' => 'ISO 8601',
                'value' => $value,
                'full_date' => $value,
                'tags' => ['modern', 'standard'],
            ],
            'julian_day' => [
                'key' => $key,
                'name' => 'Julian Day',
                'value' => $value,
                'day_number' => $value,
                'full_date' => (string) $value,
                'tags' => ['astronomical', 'numeric'],
            ],
            'modified_julian_day' => [
                'key' => $key,
                'name' => 'Modified Julian Day',
                'value' => $value,
                'day_number' => $value,
                'full_date' => (string) round($value, 5),
                'tags' => ['astronomical', 'numeric'],
            ],
            default => [
                'key' => $key,
                'name' => ucfirst(str_replace('_', ' ', $key)),
                'value' => $value,
                'full_date' => (string) $value,
                'tags' => [],
            ],
        };
    }
}
