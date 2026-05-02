<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_number',
        'report_date',
        'category',
        'title',
        'operator_name',
        'description',
        'data_link',
        'root_cause',
        'corrective_action',
        'target_completion_date',
        'monitoring_result',
        'evidence_files',
        'additional_notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'target_completion_date' => 'date',
        'evidence_files' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
