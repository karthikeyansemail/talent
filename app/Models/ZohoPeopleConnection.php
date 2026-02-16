<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZohoPeopleConnection extends Model
{
    protected $fillable = [
        'organization_id',
        'portal_name',
        'auth_token',
        'sync_config',
        'is_active',
        'last_synced_at',
    ];

    protected $hidden = ['auth_token'];

    protected function casts(): array
    {
        return [
            'auth_token' => 'encrypted',
            'sync_config' => 'array',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
