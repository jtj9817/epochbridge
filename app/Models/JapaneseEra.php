<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class JapaneseEra extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_japanese',
        'name_romanized', 
        'name_english',
        'start_date',
        'end_date',
        'historical_context',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Find the Japanese era for a given Gregorian date
     */
    public static function findEraForDate(Carbon $date): ?self
    {
        return static::where('start_date', '<=', $date->format('Y-m-d'))
            ->where(function ($query) use ($date) {
                $query->where('end_date', '>=', $date->format('Y-m-d'))
                      ->orWhereNull('end_date');
            })
            ->orderBy('start_date', 'desc')
            ->first();
    }

    /**
     * Calculate the era year for a given Gregorian date
     */
    public function getEraYear(Carbon $date): int
    {
        return $date->year - $this->start_date->year + 1;
    }

    /**
     * Check if this era is currently active
     */
    public function isActive(): bool
    {
        return is_null($this->end_date) || $this->end_date->gte(now());
    }

    /**
     * Get all active eras (for current date)
     */
    public static function getActiveEras(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('start_date', '<=', now()->format('Y-m-d'))
            ->where(function ($query) {
                $query->where('end_date', '>=', now()->format('Y-m-d'))
                      ->orWhereNull('end_date');
            })
            ->get();
    }
}
