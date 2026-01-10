<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdMachineMirror extends Model
{
    protected $table = 'md_machines_mirror';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'department_code',
        'line_code',
        'status',
        'source_updated_at',
        'last_sync_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'source_updated_at' => 'datetime',
    ];
}
