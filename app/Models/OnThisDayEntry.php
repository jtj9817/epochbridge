<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnThisDayEntry extends Model
{
    use HasFactory;

    protected $table = 'file';

    protected $fillable = [
        'language',
        'type',
        'month',
        'day',
        'day_info',
        'fetched_at',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
    ];
}
