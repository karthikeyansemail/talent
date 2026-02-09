<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeJiraTask extends Model
{
    protected $fillable = [
        'employee_id', 'jira_connection_id', 'jira_task_key', 'summary',
        'description', 'task_type', 'status', 'priority', 'labels',
        'components', 'story_points', 'resolution', 'resolved_at', 'created_in_jira_at',
    ];

    protected function casts(): array
    {
        return [
            'labels' => 'array',
            'components' => 'array',
            'story_points' => 'decimal:1',
            'resolved_at' => 'datetime',
            'created_in_jira_at' => 'datetime',
        ];
    }

    public function employee() { return $this->belongsTo(Employee::class); }
    public function jiraConnection() { return $this->belongsTo(JiraConnection::class); }
}
