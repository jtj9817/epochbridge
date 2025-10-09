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
}
