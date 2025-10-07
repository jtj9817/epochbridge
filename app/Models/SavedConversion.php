<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedConversion extends Model
{
    protected $fillable = [
        'user_id',
        'original_input',
        'gregorian_date',
        'jalali_date',
        'unix_timestamp',
        'iso8601_string',
    ];
}
