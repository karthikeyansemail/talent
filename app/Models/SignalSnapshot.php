<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignalSnapshot extends Model
{
    protected $fillable = [
        'employee_id',
        'organization_id',
        'period',
        'consistency_index',
        'recovery_signal',
        'workload_pressure',
        'context_switching_index',
        'collaboration_density',
        'raw_signals',
        'ai_analysis',
        'ai_summary',
    ];

    protected function casts(): array
    {
        return [
            'consistency_index' => 'decimal:2',
            'recovery_signal' => 'decimal:2',
            'workload_pressure' => 'decimal:2',
            'context_switching_index' => 'decimal:2',
            'collaboration_density' => 'decimal:2',
            'raw_signals' => 'array',
            'ai_analysis' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
