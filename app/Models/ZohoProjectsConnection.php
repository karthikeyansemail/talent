<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZohoProjectsConnection extends Model
{
    protected $fillable = [
        'organization_id',
        'portal_name',
        'auth_token',
        'is_active',
        'last_synced_at',
    ];

    protected $hidden = ['auth_token'];

    protected function casts(): array
    {
        return [
            'auth_token' => 'encrypted',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(EmployeeZohoTask::class, 'zoho_connection_id');
    }
}
