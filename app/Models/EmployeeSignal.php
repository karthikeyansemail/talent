<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSignal extends Model
{
    protected $fillable = [
        'employee_id',
        'organization_id',
        'source_type',
        'metric_key',
        'metric_value',
        'metric_unit',
        'period',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metric_value' => 'decimal:4',
            'metadata' => 'array',
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
