<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectResourceMatch extends Model
{
    protected $fillable = [
        'project_id', 'employee_id', 'match_score', 'strength_areas',
        'skill_gaps', 'explanation', 'is_assigned', 'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'strength_areas' => 'array',
            'skill_gaps' => 'array',
            'match_score' => 'decimal:2',
            'is_assigned' => 'boolean',
            'assigned_at' => 'datetime',
        ];
    }

    public function project() { return $this->belongsTo(Project::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
}
