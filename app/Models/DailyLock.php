<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'is_locked',
        'unlocked_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_locked' => 'boolean',
    ];
}
