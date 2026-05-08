<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSignature extends Model
{
    use HasFactory;
    
    protected $connection = 'mysql';

    protected $fillable = ['user_id', 'signature_path'];
}
