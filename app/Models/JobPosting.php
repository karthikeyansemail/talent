<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobPosting extends Model
{
    protected $fillable = [
        'organization_id', 'department_id', 'title', 'description', 'requirements',
        'key_responsibilities', 'expectations',
        'min_experience', 'max_experience', 'required_skills', 'nice_to_have_skills',
        'skill_experience_details', 'notes',
        'employment_type', 'location', 'salary_min', 'salary_max', 'status',
        'created_by', 'closed_at',
        'jd_file_path', 'jd_file_name', 'jd_file_type', 'jd_extracted_text',
    ];

    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
            'nice_to_have_skills' => 'array',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }

    public function organization() { return $this->belongsTo(Organization::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function applications() { return $this->hasMany(JobApplication::class, 'job_posting_id'); }
}
