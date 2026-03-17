<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasDepartmentScope;

class RejectLog extends Model
{
    use HasDepartmentScope, \App\Traits\LoggableTrait;

    protected $fillable = [
        'department_code',
        'reject_date',
        'operator_code',
        'machine_code',
        'item_code',
        'reject_qty',
        'reject_reason',
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
