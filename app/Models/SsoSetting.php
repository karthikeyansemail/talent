<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SsoSetting extends Model
{
    protected $fillable = [
        'provider',
        'is_enabled',
        'client_id',
        'client_secret',
        'extra_config',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled'    => 'boolean',
            'client_id'     => 'encrypted',
            'client_secret' => 'encrypted',
            'extra_config'  => 'encrypted:array',
        ];
    }

    public static function enabledProviders(): Collection
    {
        return static::where('is_enabled', true)->get();
    }

    public function getProviderLabelAttribute(): string
    {
        return match ($this->provider) {
            'google'    => 'Google',
            'microsoft' => 'Microsoft',
            'okta'      => 'Okta',
            default     => ucfirst($this->provider),
        };
    }
}
