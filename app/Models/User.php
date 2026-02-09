<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'organization_id', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isOrgAdmin(): bool { return $this->role === 'org_admin'; }
    public function isAdmin(): bool { return in_array($this->role, ['super_admin', 'org_admin']); }
    public function isHrManager(): bool { return $this->role === 'hr_manager'; }
    public function isHiringManager(): bool { return $this->role === 'hiring_manager'; }
    public function isResourceManager(): bool { return $this->role === 'resource_manager'; }
    public function isEmployee(): bool { return $this->role === 'employee'; }
}
