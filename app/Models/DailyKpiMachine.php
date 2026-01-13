<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDepartmentScope;

class DailyKpiMachine extends Model
{
    use HasDepartmentScope;

    protected $table = 'daily_kpi_machine';

    protected $fillable = [
        'kpi_date',
        'department_code',
        'machine_code',
        'total_work_hours',
        'total_target_qty',
        'total_actual_qty',
        'kpi_percent',
    ];
}

