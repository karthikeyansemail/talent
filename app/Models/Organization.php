<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = ['name', 'slug', 'domain', 'logo_path', 'settings', 'is_active'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'is_active' => 'boolean'];
    }

    public function users() { return $this->hasMany(User::class); }
    public function departments() { return $this->hasMany(Department::class); }
    public function jobPostings() { return $this->hasMany(JobPosting::class); }
    public function candidates() { return $this->hasMany(Candidate::class); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function projects() { return $this->hasMany(Project::class); }
    public function jiraConnections() { return $this->hasMany(JiraConnection::class); }
}
