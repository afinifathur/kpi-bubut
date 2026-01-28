<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasDepartmentScope;

class ProductionLog extends Model
{
    use HasDepartmentScope, \App\Traits\LoggableTrait;

    protected $fillable = [
        'department_code',
        'production_date',
        'shift',
        'operator_code',
        'machine_code',
        'item_code',
        'heat_number',
        'size',
        'customer',
        'line',
        'time_start',
        'time_end',
        'work_hours',
        'cycle_time_used_sec',
        'target_qty',
        'actual_qty',
        'achievement_percent',
        'remark',
        'note',
    ];

    public function getMachineCodeAttribute($value)
    {
        return strtoupper($value);
    }

    public function getItemCodeAttribute($value)
    {
        return strtoupper($value);
    }
    public function getOperatorCodeAttribute($value)
    {
        return strtoupper($value);
    }

    public function machine()
    {
        return $this->belongsTo(MdMachineMirror::class, 'machine_code', 'code');
    }

    public function item()
    {
        return $this->belongsTo(MdItemMirror::class, 'item_code', 'code');
    }

    public function operator()
    {
        return $this->belongsTo(MdOperatorMirror::class, 'operator_code', 'code');
    }
}
