<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'user_id', 'organization_id', 'first_name', 'last_name', 'email',
        'department_id', 'designation', 'resume_id', 'skills_from_resume',
        'skills_from_jira', 'combined_skill_profile', 'is_active',
        'import_source', 'external_id', 'work_data_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'skills_from_resume'   => 'array',
            'skills_from_jira'     => 'array',
            'combined_skill_profile' => 'array',
            'is_active'            => 'boolean',
            'work_data_synced_at'  => 'datetime',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function resume() { return $this->belongsTo(Resume::class); }
    public function tasks() { return $this->hasMany(EmployeeTask::class); }
    // Source-scoped aliases for backwards compatibility
    public function jiraTasks() { return $this->hasMany(EmployeeTask::class)->where('source_type', 'jira'); }
    public function zohoTasks() { return $this->hasMany(EmployeeTask::class)->where('source_type', 'zoho_projects'); }
    public function resourceMatches() { return $this->hasMany(ProjectResourceMatch::class); }
    public function signals() { return $this->hasMany(EmployeeSignal::class); }
    public function signalSnapshots() { return $this->hasMany(SignalSnapshot::class); }
    public function latestSignalSnapshot() { return $this->hasOne(SignalSnapshot::class)->latestOfMany(); }
    public function sprintSheets() { return $this->hasMany(SprintSheet::class); }
    public function aiInsight() { return $this->hasOne(EmployeeAiInsight::class)->latestOfMany(); }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
