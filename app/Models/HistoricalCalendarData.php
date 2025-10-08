<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricalCalendarData extends Model
{
    use HasFactory;

    protected $table = 'historical_calendar_data';

    protected $fillable = [
        'calendar_system',
        'data_type',
        'key_name',
        'value',
        'cultural_context',
        'source_reference',
    ];
}
