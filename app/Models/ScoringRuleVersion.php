<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringRuleVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'version', 'weights_snapshot', 'trigger',
        'triggered_by', 'metrics_at_snapshot', 'notes', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'weights_snapshot' => 'array',
            'metrics_at_snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
