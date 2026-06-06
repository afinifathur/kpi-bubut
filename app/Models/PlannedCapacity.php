<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDepartmentScope;

class PlannedCapacity extends Model
{
    use HasDepartmentScope, \App\Traits\LoggableTrait;

    protected $table = 'planned_capacities';

    protected $fillable = [
        'department_code',
        'work_date',
        'machine_code',
        'shift_1_hours',
        'shift_2_hours',
        'shift_3_hours',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'work_date' => 'date',
        'shift_1_hours' => 'decimal:2',
        'shift_2_hours' => 'decimal:2',
        'shift_3_hours' => 'decimal:2',
    ];

    /**
     * Get the user who created this override.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this override.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
