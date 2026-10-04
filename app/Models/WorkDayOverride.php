<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkDayOverride extends Model
{
    protected $fillable = [
        'month',
        'year',
        'work_days',
    ];
}
