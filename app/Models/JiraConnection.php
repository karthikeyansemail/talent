<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JiraConnection extends Model
{
    protected $fillable = [
        'organization_id', 'jira_base_url', 'jira_email',
        'jira_api_token', 'is_active', 'last_synced_at',
    ];

    protected $hidden = ['jira_api_token'];

    protected function casts(): array
    {
        return [
            'jira_api_token' => 'encrypted',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function organization() { return $this->belongsTo(Organization::class); }
    public function tasks() { return $this->hasMany(EmployeeJiraTask::class); }
}
