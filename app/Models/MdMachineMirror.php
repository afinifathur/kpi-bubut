<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdMachineMirror extends Model
{
    protected $table = 'md_machines_mirror';

    protected $fillable = [
        'code',
        'name',
        'department_code',
        'line_code',
        'status',
        'runtime_status',
        'last_seen_at',
        'last_active_module',
        'last_sync_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];
}
