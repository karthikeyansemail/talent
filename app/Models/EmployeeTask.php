<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class EmployeeTask extends Model
{
    protected $fillable = [
        'employee_id', 'organization_id', 'connection_id',
        'source_type', 'external_id', 'title', 'description',
        'task_type', 'status', 'priority', 'story_points',
        'assignee_email', 'labels', 'completed_at', 'source_created_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'labels'            => 'array',
            'metadata'          => 'array',
            'story_points'      => 'decimal:1',
            'completed_at'      => 'datetime',
            'source_created_at' => 'datetime',
        ];
    }

    // ----- Scopes -----

    public function scopeFromSource(Builder $query, string $source): Builder
    {
        return $query->where('source_type', $source);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'Done')
            ->orWhereNotNull('completed_at');
    }

    // ----- Relationships -----

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnection::class);
    }
}
