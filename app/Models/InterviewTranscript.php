<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewTranscript extends Model
{
    protected $fillable = [
        'interview_session_id', 'speaker', 'text', 'offset_seconds', 'confidence',
    ];

    public function session() { return $this->belongsTo(InterviewSession::class, 'interview_session_id'); }
}
