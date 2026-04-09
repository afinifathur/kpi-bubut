<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasDepartmentScope;

class DowntimeLog extends Model
{
    use HasDepartmentScope, \App\Traits\LoggableTrait;

    protected $table = 'downtime_logs';

    protected $fillable = [
        'entry_type',
        'department_code',
        'downtime_date',
        'shift',
        'tim',
        'operator_code',
        'machine_code',
        'start_time',
        'end_time',
        'duration_minutes',
        'reason',
        'size_category',
        'check_cekam',
        'check_air_ozo',
        'check_eretan',
        'check_pisau',
        'check_kebersihan',
        'check_oli',
        'rpm_value',
        'rpm_id_value',
        'feeding_value',
        'feeding_id_value',
        'rpm_feeding_mode',
        'note',
    ];

    public function getMachineCodeAttribute($value)
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

    public function operator()
    {
        return $this->belongsTo(MdOperatorMirror::class, 'operator_code', 'code');
    }
}
