<?php

namespace App\Http\Controllers\Placement;

use App\Http\Controllers\Controller;
use App\Jobs\GradeTestAttemptJob;
use App\Models\AptitudeTest;
use App\Models\Candidate;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Public, no-login test-taking flow for students.
 *
 * URL pattern:
 *   /placement/test/{token}                — landing page (test info + start form)
 *   POST /placement/test/{token}/start     — creates attempt, redirects to take
 *   /placement/test/attempt/{attempt}/take — the actual test
 *   POST /placement/test/attempt/{attempt}/save  — autosave a single answer (AJAX)
 *   POST /placement/test/attempt/{attempt}/submit — finalize submission
 *   /placement/test/attempt/{attempt}/result      — student sees their score
 *   /placement/test/attempt/{attempt}/result/status — polling endpoint while grading
 */
class PublicTestController extends Controller
{
    public function landing(string $token)
    {
        $test = AptitudeTest::where('public_token', $token)
            ->where('status', 'published')
            ->with(['drive', 'questions'])
            ->firstOrFail();

        return view('placement.public.landing', compact('test'));
    }

    public function start(Request $request, string $token)
    {
        $test = AptitudeTest::where('public_token', $token)
            ->where('status', 'published')
            ->firstOrFail();

        $validated = $request->validate([
            'student_name'       => 'required|string|max:255',
            'student_email'      => 'required|email|max:255',
            'student_enrollment' => 'nullable|string|max:64',
        ]);

        // Single attempt per email per test (anti-cheat)
        $existing = TestAttempt::where('aptitude_test_id', $test->id)
            ->where('student_email', $validated['student_email'])
            ->first();

        if ($existing) {
            if ($existing->submitted_at) {
                return redirect()->route('placement.public.result', $existing);
            }
            // Resume in-progress attempt
            return redirect()->route('placement.public.take', $existing);
        }

        // Match to a candidate (student) record by email if exists, for analytics linkage
        $candidate = Candidate::where('organization_id', $test->organization_id)
            ->where('email', $validated['student_email'])
            ->first();

        $totalMarks = $test->questions()->sum('marks');

        $attempt = TestAttempt::create([
            'aptitude_test_id'      => $test->id,
            'placement_drive_id'    => $test->placement_drive_id,
            'organization_id'       => $test->organization_id,
            'candidate_id'          => $candidate?->id,
            'student_email'         => $validated['student_email'],
            'student_name'          => $validated['student_name'],
            'student_enrollment'    => $validated['student_enrollment'] ?? null,
            'ip_address'            => $request->ip(),
            'started_at'            => now(),
            'total_marks_available' => $totalMarks,
            'grading_status'        => 'pending',
        ]);

        return redirect()->route('placement.public.take', $attempt);
    }

    public function take(TestAttempt $attempt)
    {
        if ($attempt->submitted_at) {
            return redirect()->route('placement.public.result', $attempt);
        }

        $test = $attempt->test()->with('questions')->first();

        // Auto-submit if time is up
        $deadline = $attempt->started_at->copy()->addMinutes($test->time_limit_minutes);
        if (now()->gte($deadline)) {
            return $this->forceSubmit($attempt);
        }

        // Pre-load existing answers (in case student resumed)
        $existingAnswers = $attempt->answers()->get()->keyBy('test_question_id');
        $secondsLeft = (int) max(0, $deadline->diffInSeconds(now(), false) * -1);

        return view('placement.public.take', compact('test', 'attempt', 'existingAnswers', 'secondsLeft'));
    }

    /**
     * AJAX autosave of a single answer (called as student picks options or types).
     */
    public function saveAnswer(Request $request, TestAttempt $attempt)
    {
        if ($attempt->submitted_at) {
            return response()->json(['error' => 'Already submitted'], 422);
        }

        $validated = $request->validate([
            'test_question_id' => 'required|exists:test_questions,id',
            'selected_option'  => 'nullable|integer|min:0',
            'answer_text'      => 'nullable|string|max:10000',
        ]);

        $question = TestQuestion::findOrFail($validated['test_question_id']);
        if ($question->aptitude_test_id !== $attempt->aptitude_test_id) {
            abort(403);
        }

        TestAnswer::updateOrCreate(
            ['test_attempt_id' => $attempt->id, 'test_question_id' => $question->id],
            [
                'selected_option' => $validated['selected_option'] ?? null,
                'answer_text'     => $validated['answer_text'] ?? null,
            ]
        );

        return response()->json(['ok' => true, 'saved_at' => now()->toIso8601String()]);
    }

    public function submit(Request $request, TestAttempt $attempt)
    {
        if ($attempt->submitted_at) {
            return redirect()->route('placement.public.result', $attempt);
        }

        // Save any final answers from the form (in case JS autosave missed something)
        if ($request->has('answers') && is_array($request->input('answers'))) {
            foreach ($request->input('answers') as $qid => $payload) {
                $question = TestQuestion::find((int) $qid);
                if (!$question || $question->aptitude_test_id !== $attempt->aptitude_test_id) continue;

                TestAnswer::updateOrCreate(
                    ['test_attempt_id' => $attempt->id, 'test_question_id' => (int) $qid],
                    [
                        'selected_option' => isset($payload['selected_option']) && $payload['selected_option'] !== ''
                            ? (int) $payload['selected_option'] : null,
                        'answer_text'     => $payload['answer_text'] ?? null,
                    ]
                );
            }
        }

        return $this->finalizeSubmission($attempt);
    }

    private function forceSubmit(TestAttempt $attempt)
    {
        return $this->finalizeSubmission($attempt);
    }

    private function finalizeSubmission(TestAttempt $attempt)
    {
        $attempt->update([
            'submitted_at'       => now(),
            'time_taken_seconds' => $attempt->started_at->diffInSeconds(now()),
            'grading_status'     => 'grading',
        ]);

        // Dispatch async grading (MCQ auto + descriptive via AI)
        GradeTestAttemptJob::dispatch($attempt->id);

        return redirect()->route('placement.public.result', $attempt);
    }

    public function result(TestAttempt $attempt)
    {
        $attempt->load(['test', 'drive']);
        return view('placement.public.result', compact('attempt'));
    }

    /**
     * Polling endpoint — frontend hits this every 2-3s while grading_status='grading'.
     */
    public function resultStatus(TestAttempt $attempt)
    {
        $attempt->refresh();
        return response()->json([
            'status'      => $attempt->grading_status,
            'score_pct'   => $attempt->score_pct,
            'score'       => $attempt->score_obtained,
            'total'       => $attempt->total_marks_available,
            'passed'      => $attempt->passed,
        ]);
    }
}
