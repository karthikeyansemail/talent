<?php

namespace App\Http\Controllers\Placement;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\TestAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentProgressController extends Controller
{
    /**
     * Cohort overview: every student + their roll-up stats. Filterable by
     * batch and course. Includes a sparkline of recent attempt scores per
     * student so the placement officer can spot trends at a glance.
     */
    public function index(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();

        $query = Candidate::where('organization_id', $orgId)->with('department');

        if ($batch = $request->input('batch')) {
            $query->where('batch_year', $batch);
        }
        if ($course = $request->input('course')) {
            $query->where('course', $course);
        }
        if ($deptId = $request->input('department')) {
            $query->where('department_id', $deptId);
        }
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('enrollment_number', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('first_name')->paginate(50)->withQueryString();

        // Aggregate attempts per student
        $attemptStats = TestAttempt::where('organization_id', $orgId)
            ->whereNotNull('score_pct')
            ->selectRaw('candidate_id, COUNT(*) as attempts, AVG(score_pct) as avg_score, MAX(submitted_at) as last_attempt, SUM(passed) as cleared_count')
            ->groupBy('candidate_id')
            ->get()
            ->keyBy('candidate_id');

        // Sparkline data: last 6 attempt scores per student, ordered chronologically
        $studentIds = $students->pluck('id');
        $recentAttempts = TestAttempt::where('organization_id', $orgId)
            ->whereIn('candidate_id', $studentIds)
            ->whereNotNull('score_pct')
            ->orderBy('submitted_at')
            ->get(['candidate_id', 'score_pct'])
            ->groupBy('candidate_id')
            ->map(fn($atts) => $atts->take(-6)->pluck('score_pct')->toArray());

        $batches     = Candidate::where('organization_id', $orgId)->whereNotNull('batch_year')->distinct()->orderByDesc('batch_year')->pluck('batch_year');
        $courses     = Candidate::where('organization_id', $orgId)->whereNotNull('course')->distinct()->orderBy('course')->pluck('course');
        $departments = Department::where('organization_id', $orgId)->orderBy('name')->get();

        return view('placement.progress.index', compact('students', 'attemptStats', 'recentAttempts', 'batches', 'courses', 'departments'));
    }

    /**
     * Per-student detail page: profile + all attempts + improvement chart +
     * skill heatmap (per-topic average from descriptive understanding scores +
     * MCQ correctness aggregated by question topic).
     */
    public function show(Candidate $student)
    {
        if ($student->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        // All attempts in chronological order
        $attempts = TestAttempt::where('organization_id', $student->organization_id)
            ->where('candidate_id', $student->id)
            ->with(['drive', 'test', 'answers.question'])
            ->orderBy('submitted_at')
            ->get();

        // Per-topic aggregation across all answers
        // For MCQ: 1.0 if is_correct, 0.0 otherwise
        // For descriptive: understanding_score / 100
        $topicScores = []; // [topic => ['scores' => [...], 'count' => N]]
        foreach ($attempts as $attempt) {
            foreach ($attempt->answers as $ans) {
                $topic = $ans->question->topic ?: 'General';
                $score = null;
                if ($ans->question->type === 'mcq' && !is_null($ans->is_correct)) {
                    $score = $ans->is_correct ? 100 : 0;
                } elseif ($ans->question->type === 'descriptive' && !is_null($ans->understanding_score)) {
                    $score = (float) $ans->understanding_score;
                }
                if ($score !== null) {
                    $topicScores[$topic][] = $score;
                }
            }
        }
        $topicHeatmap = collect($topicScores)
            ->map(fn($scores, $topic) => [
                'topic'  => $topic,
                'avg'    => round(array_sum($scores) / count($scores), 1),
                'count'  => count($scores),
            ])
            ->sortByDesc('avg')
            ->values()
            ->all();

        // Improvement chart points: (date, score_pct) for each completed attempt
        $chartPoints = $attempts
            ->filter(fn($a) => $a->score_pct !== null)
            ->map(fn($a) => [
                'date'  => $a->submitted_at?->format('d M'),
                'score' => (float) $a->score_pct,
                'drive' => $a->drive->company_name,
            ])
            ->values()
            ->all();

        // Stats for the header
        $totalAttempts  = $attempts->count();
        $completedCount = $attempts->whereNotNull('score_pct')->count();
        $avgScore       = $completedCount > 0
            ? round($attempts->whereNotNull('score_pct')->avg('score_pct'), 1)
            : null;
        $clearedCount   = $attempts->where('passed', true)->count();
        $latestAvg      = $attempts->whereNotNull('score_pct')->take(-3)->avg('score_pct');
        $earliestAvg    = $attempts->whereNotNull('score_pct')->take(3)->avg('score_pct');
        $improvementDelta = ($latestAvg !== null && $earliestAvg !== null && $completedCount >= 4)
            ? round($latestAvg - $earliestAvg, 1)
            : null;

        return view('placement.progress.show', compact(
            'student', 'attempts', 'topicHeatmap', 'chartPoints',
            'totalAttempts', 'completedCount', 'avgScore', 'clearedCount', 'improvementDelta'
        ));
    }
}
