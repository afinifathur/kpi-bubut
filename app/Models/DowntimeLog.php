<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasDepartmentScope;

class DowntimeLog extends Model
{
    use HasDepartmentScope, \App\Traits\LoggableTrait;

    protected $table = 'downtime_logs';

    protected $fillable = [
        'department_code',
        'downtime_date',
        'operator_code',
        'machine_code',
        'duration_minutes',
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
