<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAiInsight extends Model
{
    protected $fillable = [
        'employee_id',
        'organization_id',
        'analyzed_at',
        'management_narrative',
        'task_summary',
        'dimensions',
        'data_context',
    ];

    protected function casts(): array
    {
        return [
            'dimensions'   => 'array',
            'data_context' => 'array',
            'analyzed_at'  => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
