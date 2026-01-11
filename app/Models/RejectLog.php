<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasDepartmentScope;

class RejectLog extends Model
{
    use HasDepartmentScope;

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
}
