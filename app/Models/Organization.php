<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name', 'slug', 'domain', 'logo_path', 'settings', 'llm_config', 'is_active',
        'is_premium', 'premium_expires_at', 'premium_features',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'llm_config' => 'array',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
            'premium_expires_at' => 'datetime',
            'premium_features' => 'array',
        ];
    }

    public function users() { return $this->hasMany(User::class); }
    public function departments() { return $this->hasMany(Department::class); }
    public function jobPostings() { return $this->hasMany(JobPosting::class); }
    public function candidates() { return $this->hasMany(Candidate::class); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function projects() { return $this->hasMany(Project::class); }
    public function jiraConnections() { return $this->hasMany(JiraConnection::class); }
    public function zohoProjectsConnections() { return $this->hasMany(ZohoProjectsConnection::class); }
    public function zohoPeopleConnections() { return $this->hasMany(ZohoPeopleConnection::class); }
    public function scoringRules() { return $this->hasMany(ScoringRule::class); }
    public function scoringRuleVersions() { return $this->hasMany(ScoringRuleVersion::class); }
}
