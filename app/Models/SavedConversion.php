<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedConversion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
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
        return $query->select(['id', 'user_id', 'original_input', 'conversion_data', 'created_at', 'updated_at']);
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
        return $this->conversion_data['gregorian']['full_date'] ?? $value;
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
}
