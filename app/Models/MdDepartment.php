<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdDepartment extends Model
{
    protected $connection = 'master';
    protected $table = 'md_departments';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'status',
    ];
}
