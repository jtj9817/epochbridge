<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MayanCalendarData extends Model
{
    use HasFactory;

    protected $table = 'mayan_calendar_data';

    protected $fillable = [
        'calendar_type',
        'position',
        'name_maya',
        'name_english',
        'cultural_significance',
    ];
}
