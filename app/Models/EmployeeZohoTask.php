<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeZohoTask extends Model
{
    protected $fillable = [
        'employee_id',
        'zoho_connection_id',
        'task_key',
        'project_name',
        'summary',
        'description',
        'status',
        'priority',
        'tags',
        'completed_at',
        'created_in_zoho_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'completed_at' => 'datetime',
            'created_in_zoho_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function zohoConnection(): BelongsTo
    {
        return $this->belongsTo(ZohoProjectsConnection::class, 'zoho_connection_id');
    }
}
