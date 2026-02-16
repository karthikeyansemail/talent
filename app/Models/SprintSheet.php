<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SprintSheet extends Model
{
    protected $fillable = [
        'organization_id',
        'sprint_name',
        'start_date',
        'end_date',
        'employee_id',
        'planned_points',
        'completed_points',
        'tasks_planned',
        'tasks_completed',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
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
