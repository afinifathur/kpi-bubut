<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasDepartmentScope;

class DowntimeLog extends Model
{
    use HasDepartmentScope;

    protected $table = 'downtime_logs';

    protected $fillable = [
        'department_code',
        'downtime_date',
        'operator_code',
        'machine_code',
        'duration_minutes',
        'note',
    ];
}
