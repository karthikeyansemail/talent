<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestQuestion extends Model
{
    protected $fillable = [
        'aptitude_test_id', 'order', 'type', 'question_text', 'context',
        'topic', 'difficulty', 'marks', 'options', 'correct_option',
        'ideal_answer', 'rubric_points', 'expected_word_count',
    ];

    protected function casts(): array
    {
        return [
            'options'       => 'array',
            'rubric_points' => 'array',
        ];
    }

    public function test(): BelongsTo { return $this->belongsTo(AptitudeTest::class, 'aptitude_test_id'); }
}
