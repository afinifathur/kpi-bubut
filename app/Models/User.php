<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $connection = 'master';
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'scope',
        'department_code',
        'tim',
        'allowed_apps',
        'additional_department_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'allowed_apps' => 'array',
            'additional_department_codes' => 'array',
        ];
    }

    public function canManageHrReports(): bool
    {
        return in_array($this->email, ['adminhr@peroniks.com', 'managerhr@peroniks.com']) || in_array($this->role, ['hr_admin', 'hr_manager']);
    }

    public function isHrAdmin(): bool
    {
        return $this->email === 'adminhr@peroniks.com' || $this->role === 'hr_admin';
    }

    public function isHrManager(): bool
    {
        return $this->email === 'managerhr@peroniks.com' || $this->role === 'hr_manager';
    }

    public function isReadOnly(): bool
    {
        if ($this->isHrAdmin() || $this->isHrManager()) {
            return false;
        }
        return in_array($this->role, ['auditor', 'guest']);
    }

    public function signature()
    {
        return $this->hasOne(UserSignature::class, 'user_id', 'id');
    }

    public function getSignaturePathAttribute()
    {
        return $this->signature ? $this->signature->signature_path : null;
    }
}
