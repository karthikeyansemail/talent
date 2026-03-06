<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewSession extends Model
{
    protected $fillable = [
        'organization_id', 'job_application_id', 'candidate_id', 'interviewer_id',
        'assigned_by', 'status', 'outcome', 'interview_type', 'scheduled_at', 'started_at',
        'ended_at', 'duration_seconds', 'summary', 'settings', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'summary' => 'array',
            'settings' => 'array',
        ];
    }

    public function organization() { return $this->belongsTo(Organization::class); }
    public function application() { return $this->belongsTo(JobApplication::class, 'job_application_id'); }
    public function candidate() { return $this->belongsTo(Candidate::class); }
    public function interviewer() { return $this->belongsTo(User::class, 'interviewer_id'); }
    public function assignedBy() { return $this->belongsTo(User::class, 'assigned_by'); }
    public function transcripts() { return $this->hasMany(InterviewTranscript::class)->orderBy('offset_seconds'); }
    public function questions() { return $this->hasMany(InterviewQuestion::class); }
    public function feedback() { return $this->hasOne(InterviewFeedback::class); }
}
