<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewQuestion extends Model
{
    protected $fillable = [
        'interview_session_id', 'question_text', 'question_type', 'difficulty',
        'skill_area', 'answer_text', 'evaluation', 'status',
        'suggested_at_offset', 'asked_at_offset',
    ];

    protected function casts(): array
    {
        return ['evaluation' => 'array'];
    }

    public function session() { return $this->belongsTo(InterviewSession::class, 'interview_session_id'); }
}
