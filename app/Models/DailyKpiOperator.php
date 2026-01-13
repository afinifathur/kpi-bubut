<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDepartmentScope;

class DailyKpiOperator extends Model
{
    use HasDepartmentScope;

    protected $table = 'daily_kpi_operator';

    protected $fillable = [
        'kpi_date',
        'department_code',
        'operator_code',
        'total_work_hours',
        'total_target_qty',
        'total_actual_qty',
        'kpi_percent',
    ];
}

