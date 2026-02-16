<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'organization_id', 'name', 'description', 'required_skills',
        'required_technologies', 'complexity_level', 'domain_context',
        'start_date', 'end_date', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
            'required_technologies' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function organization() { return $this->belongsTo(Organization::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function resourceMatches() { return $this->hasMany(ProjectResourceMatch::class); }
    public function sprintSheets() { return $this->hasMany(ProjectSprintSheet::class); }
}
