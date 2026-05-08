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
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_note',
        'created_by',
        'submitted_by',
        'submitted_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'target_completion_date' => 'date',
        'evidence_files' => 'array',
        'approved_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
