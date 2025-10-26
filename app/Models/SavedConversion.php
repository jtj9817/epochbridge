<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class SavedConversion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'original_input',
        'conversion_data',
        'gregorian_date',
        'jalali_date',
        'unix_timestamp',
        'iso8601_string',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversion_data' => 'array',
            'gregorian_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the saved conversion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include conversions for a specific user with optimized ordering.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId)->latest('created_at');
    }

    /**
     * Scope a query to include conversion data efficiently.
     */
    public function scopeWithConversionData($query)
    {
        return $query->select([
            'id',
            'user_id',
            'original_input',
            'conversion_data',
            'gregorian_date',
            'jalali_date',
            'unix_timestamp',
            'iso8601_string',
            'created_at',
            'updated_at',
        ]);
    }

    /**
     * Get paginated conversions for a user with optimized queries.
     */
    public static function getPaginatedForUser($userId, $perPage = 10)
    {
        return static::forUser($userId)
            ->withConversionData()
            ->paginate($perPage);
    }

    /**
     * Get paginated conversions with performance monitoring.
     */
    public static function getPaginatedForUserWithMonitoring($userId, $perPage = 10)
    {
        if (app()->environment('local', 'testing')) {
            // Use monitoring in development/testing
            $result = \App\Services\PerformanceMonitoringService::monitorSavedConversionsQuery($userId, $perPage);

            return $result['conversions'];
        }

        // Production: use optimized query without monitoring overhead
        return static::getPaginatedForUser($userId, $perPage);
    }

    /**
     * Get the gregorian date attribute with backward compatibility.
     */
    public function getGregorianDateAttribute($value)
    {
        if (isset($this->conversion_data['gregorian']['full_date'])) {
            return $this->conversion_data['gregorian']['full_date'];
        }

        if ($value instanceof Carbon || $value === null) {
            return $value;
        }

        return Carbon::parse($value)->startOfDay();
    }

    /**
     * Get the jalali date attribute with backward compatibility.
     */
    public function getJalaliDateAttribute($value)
    {
        return $this->conversion_data['jalali']['full_date'] ?? $value;
    }

    /**
     * Get the unix timestamp attribute with backward compatibility.
     */
    public function getUnixTimestampAttribute($value)
    {
        return $this->conversion_data['unix'] ?? $value;
    }

    /**
     * Get the iso8601 string attribute with backward compatibility.
     */
    public function getIso8601StringAttribute($value)
    {
        return $this->conversion_data['iso8601'] ?? $value;
    }

    /**
     * Get Julian Day Number with backward compatibility.
     */
    public function getJulianDayAttribute($value)
    {
        return data_get($this->conversion_data, 'julian-day.full_date')
            ?? data_get($this->conversion_data, 'julian-day.day_number')
            ?? data_get($this->conversion_data, 'julian_day')
            ?? $value;
    }

    /**
     * Get Modified Julian Day with backward compatibility.
     */
    public function getModifiedJulianDayAttribute($value)
    {
        return data_get($this->conversion_data, 'modified-julian-day.full_date')
            ?? data_get($this->conversion_data, 'modified-julian-day.day_number')
            ?? data_get($this->conversion_data, 'modified_julian_day')
            ?? $value;
    }

    /**
     * Get normalized legacy values for display when conversion_data is unavailable.
     */
    public function legacyValues(): array
    {
        return [
            'gregorian' => $this->formatLegacyDate($this->gregorian_date),
            'jalali' => $this->normalizeString($this->jalali_date),
            'unix' => $this->normalizeNumeric($this->unix_timestamp),
            'iso8601' => $this->normalizeString($this->iso8601_string),
        ];
    }

    /**
     * Determine if any legacy values are present.
     */
    public function hasLegacyValues(): bool
    {
        return collect($this->legacyValues())
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->isNotEmpty();
    }

    protected function formatLegacyDate(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return trim($value);
            }
        }

        return null;
    }

    protected function normalizeString(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? null : $trimmed;
        }

        return null;
    }

    protected function normalizeNumeric(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_numeric($value) ? (string) $value : null;
    }
}
