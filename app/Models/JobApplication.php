<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = [
        'job_posting_id', 'candidate_id', 'resume_id', 'stage', 'stage_notes',
        'applied_at', 'ai_score', 'ai_analysis', 'ai_signals', 'ai_score_version',
        'ai_analyzed_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'ai_analysis' => 'array',
            'ai_signals' => 'array',
            'ai_score' => 'decimal:2',
            'applied_at' => 'datetime',
            'ai_analyzed_at' => 'datetime',
        ];
    }

    public function jobPosting() { return $this->belongsTo(JobPosting::class, 'job_posting_id'); }
    public function candidate() { return $this->belongsTo(Candidate::class); }
    public function resume() { return $this->belongsTo(Resume::class); }
    public function feedback() { return $this->hasMany(InterviewFeedback::class, 'job_application_id'); }
}
