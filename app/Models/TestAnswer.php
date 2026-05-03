<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestAnswer extends Model
{
    protected $fillable = [
        'test_attempt_id', 'test_question_id', 'selected_option', 'answer_text',
        'marks_awarded', 'is_correct', 'understanding_score', 'ai_feedback',
        'rubric_coverage',
    ];

    protected function casts(): array
    {
        return [
            'marks_awarded'       => 'decimal:2',
            'is_correct'          => 'boolean',
            'understanding_score' => 'decimal:2',
            'rubric_coverage'     => 'array',
        ];
    }

    public function attempt(): BelongsTo  { return $this->belongsTo(TestAttempt::class, 'test_attempt_id'); }
    public function question(): BelongsTo { return $this->belongsTo(TestQuestion::class, 'test_question_id'); }
}
