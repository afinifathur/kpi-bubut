<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionLog extends Model
{
    protected $fillable = [
        'production_date',
        'shift',
        'operator_code',
        'machine_code',
        'item_code',
        'heat_number',
        'time_start',
        'time_end',
        'work_hours',
        'cycle_time_used_sec',
        'target_qty',
        'actual_qty',
        'achievement_percent',
        'note',
    ];
}
