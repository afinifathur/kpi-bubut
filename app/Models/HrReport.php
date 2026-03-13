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
        'description',
        'data_link',
        'root_cause',
        'corrective_action',
        'status',
        'created_by',
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
