<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringOptimizationRun extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'version_before', 'version_after',
        'applications_analyzed', 'correlation_before', 'correlation_after',
        'mae_before', 'mae_after', 'weight_deltas', 'status',
        'error_message', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'weight_deltas' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
