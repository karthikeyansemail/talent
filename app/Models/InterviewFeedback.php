<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewFeedback extends Model
{
    protected $table = 'interview_feedback';

    protected $fillable = [
        'job_application_id', 'interviewer_id', 'interview_session_id', 'stage', 'rating',
        'strengths', 'weaknesses', 'recommendation', 'notes',
    ];

    public function application() { return $this->belongsTo(JobApplication::class, 'job_application_id'); }
    public function interviewer() { return $this->belongsTo(User::class, 'interviewer_id'); }
    public function session() { return $this->belongsTo(InterviewSession::class, 'interview_session_id'); }
}
