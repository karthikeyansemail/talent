<?php

namespace App\Jobs;

use App\Models\TestAnswer;
use App\Models\TestAttempt;
use App\Services\AiServiceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Grades a submitted test attempt:
 *  - MCQ: instant — compare selected_option to question's correct_option
 *  - Descriptive: call AI grading endpoint (returns marks_awarded,
 *    understanding_score, rubric_coverage, ai_feedback)
 *
 * Aggregates per-question marks → score_obtained → score_pct → passed.
 * Updates attempt.grading_status to 'complete' (or 'failed' on error).
 */
class GradeTestAttemptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(public int $attemptId) {}

    public function handle(): void
    {
        $attempt = TestAttempt::with(['test.questions', 'answers'])->find($this->attemptId);
        if (!$attempt) {
            Log::warning('GradeTestAttemptJob: attempt not found', ['attempt_id' => $this->attemptId]);
            return;
        }

        $totalAvailable = (float) $attempt->total_marks_available;
        $totalAwarded   = 0.0;
        $client         = new AiServiceClient();

        foreach ($attempt->test->questions as $question) {
            // Find the student's answer (or create empty one for unanswered questions)
            $answer = $attempt->answers->firstWhere('test_question_id', $question->id)
                ?? TestAnswer::create([
                    'test_attempt_id'  => $attempt->id,
                    'test_question_id' => $question->id,
                ]);

            if ($question->type === 'mcq') {
                $this->gradeMcq($answer, $question);
            } else {
                $this->gradeDescriptive($answer, $question, $attempt, $client);
            }

            $totalAwarded += (float) $answer->marks_awarded;
        }

        $scorePct = $totalAvailable > 0 ? round(($totalAwarded / $totalAvailable) * 100, 2) : 0.0;
        $passed   = $scorePct >= (float) $attempt->test->passing_score_pct;

        $attempt->update([
            'score_obtained' => $totalAwarded,
            'score_pct'      => $scorePct,
            'passed'         => $passed,
            'grading_status' => 'complete',
        ]);

        Log::info("Test attempt graded", [
            'attempt_id' => $attempt->id,
            'score_pct'  => $scorePct,
            'passed'     => $passed,
        ]);
    }

    private function gradeMcq(TestAnswer $answer, $question): void
    {
        $isCorrect = ($answer->selected_option !== null
            && $question->correct_option !== null
            && (int) $answer->selected_option === (int) $question->correct_option);

        $answer->update([
            'is_correct'    => $isCorrect,
            'marks_awarded' => $isCorrect ? (float) $question->marks : 0,
        ]);
    }

    private function gradeDescriptive(TestAnswer $answer, $question, TestAttempt $attempt, AiServiceClient $client): void
    {
        $studentText = trim($answer->answer_text ?? '');
        if (strlen($studentText) < 5) {
            // Empty / negligible answer — skip the AI call
            $answer->update([
                'marks_awarded'        => 0,
                'understanding_score'  => 0,
                'ai_feedback'          => 'No answer submitted.',
                'rubric_coverage'      => array_fill(0, count($question->rubric_points ?? []), false),
            ]);
            return;
        }

        try {
            $result = $client->gradeDescriptiveAnswer([
                'question_text'  => $question->question_text,
                'context'        => $question->context ?? '',
                'ideal_answer'   => $question->ideal_answer ?? '',
                'rubric_points'  => $question->rubric_points ?? [],
                'student_answer' => $studentText,
                'max_marks'      => (float) $question->marks,
            ], $attempt->organization_id);

            if (isset($result['error'])) {
                Log::warning('AI grading returned error', ['attempt_id' => $attempt->id, 'q_id' => $question->id, 'error' => $result['error']]);
                // Award 50% as a safe fallback so we don't block the student
                $answer->update([
                    'marks_awarded'        => (float) $question->marks * 0.5,
                    'understanding_score'  => 50,
                    'ai_feedback'          => 'AI grading unavailable; partial marks awarded for review.',
                ]);
                return;
            }

            $answer->update([
                'marks_awarded'        => (float) ($result['marks_awarded'] ?? 0),
                'understanding_score'  => (float) ($result['understanding_score'] ?? 0),
                'rubric_coverage'      => $result['rubric_coverage'] ?? [],
                'ai_feedback'          => $result['ai_feedback'] ?? '',
            ]);
        } catch (\Throwable $e) {
            Log::error('Descriptive grading exception', ['attempt_id' => $attempt->id, 'q_id' => $question->id, 'msg' => $e->getMessage()]);
            $answer->update([
                'marks_awarded'        => (float) $question->marks * 0.5,
                'understanding_score'  => 50,
                'ai_feedback'          => 'Grading failed; partial credit awarded — please contact placement office.',
            ]);
        }
    }
}
