<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Jobs\ComputeEmployeeSignalsJob;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SignalDashboardController extends Controller
{
    public function index(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();

        // Period filter
        $period = $request->input('period', '4w');
        $weeks  = match($period) {
            '2w'    => 2,
            '4w'    => 4,
            '1m'    => 4,
            '3m'    => 13,
            '6m'    => 26,
            default => 4,
        };
        $cutoff = Carbon::now()->subWeeks($weeks);

        // Department filter
        $deptId      = $request->input('department_id');
        $departments = Department::where('organization_id', $orgId)->orderBy('name')->get();

        $query = Employee::where('organization_id', $orgId)->where('is_active', true);
        if ($deptId) {
            $query->where('department_id', $deptId);
        }

        $employees = $query
            ->with(['department', 'aiInsight', 'tasks' => function ($q) use ($cutoff) {
                $q->where(function ($q2) use ($cutoff) {
                    $q2->where('source_created_at', '>=', $cutoff)
                       ->orWhere('completed_at', '>=', $cutoff);
                });
            }, 'signals'])
            ->get()
            ->map(function ($employee) {
                $tasks    = $employee->tasks;
                $total    = $tasks->count();
                $done     = $tasks->where('status', 'Done')->count();
                $inProg   = $tasks->whereIn('status', ['In Progress','In Review','In Development','Review','Active'])->count();
                $spDone   = $tasks->where('status', 'Done')->sum('story_points');
                $bugCount = $tasks->where('task_type', 'Bug')->count();
                $rate     = $total > 0 ? round($done / $total * 100) : null;

                // Avg cycle time for Done tasks with both dates
                $withDates = $tasks->where('status', 'Done')
                    ->filter(fn($t) => $t->completed_at && $t->source_created_at);
                $cycleTime = $withDates->count() > 0
                    ? round($withDates->avg(fn($t) => $t->source_created_at->diffInDays($t->completed_at)))
                    : null;

                // Quick engagement level from task + Slack signals
                $signals      = $employee->signals;
                $slackSigs    = $signals->where('source_type', 'slack');
                $activeDays   = (float) ($slackSigs->where('metric_key', 'active_days_count')
                                    ->sortByDesc('period')->first()?->metric_value ?? 0);
                $collaborators= (float) ($slackSigs->where('metric_key', 'unique_collaborators_count')
                                    ->sortByDesc('period')->first()?->metric_value ?? 0);
                $afterHours   = (float) ($slackSigs->where('metric_key', 'after_hours_message_pct')
                                    ->sortByDesc('period')->first()?->metric_value ?? 0);
                $sentiment    = (float) ($slackSigs->where('metric_key', 'message_sentiment_score')
                                    ->sortByDesc('period')->first()?->metric_value ?? 0);

                // Weighted engagement score (simplified, same weights as full computation)
                $weights = [];
                if ($rate !== null)         $weights[] = ['v' => $rate,                              'w' => 0.40];
                if ($activeDays > 0)        $weights[] = ['v' => min(100, $activeDays / 5 * 100),    'w' => 0.20];
                if ($collaborators > 0)     $weights[] = ['v' => min(100, $collaborators / 10 * 100),'w' => 0.20];
                if ($afterHours > 0 || $slackSigs->count() > 0)
                                            $weights[] = ['v' => max(0, 100 - $afterHours * 2),       'w' => 0.20];

                $engScore = null;
                if (!empty($weights)) {
                    $totalW   = array_sum(array_column($weights, 'w'));
                    $raw      = array_sum(array_map(fn($x) => $x['v'] * $x['w'], $weights)) / $totalW;
                    $nudge    = (int) round(($sentiment - 50) / 10); // -5 to +5 from tone
                    $engScore = max(0, min(100, (int) round($raw) + $nudge));
                }

                $engLevel = match(true) {
                    $engScore === null   => null,
                    $engScore >= 75      => 'Highly Engaged',
                    $engScore >= 55      => 'Engaged',
                    $engScore >= 35      => 'Moderate',
                    default              => 'Disengaged',
                };

                // Attrition risk quick check: count declining trends
                $decliningCount = 0;
                $msgTrend = null;
                $latestMsg = $slackSigs->where('metric_key','messages_sent_count')->sortByDesc('period')->first();
                $prevMsg   = $slackSigs->where('metric_key','messages_sent_count')->sortByDesc('period')->skip(1)->first();
                if ($latestMsg && $prevMsg && (float)$prevMsg->metric_value > 0) {
                    $chg = (((float)$latestMsg->metric_value - (float)$prevMsg->metric_value) / (float)$prevMsg->metric_value) * 100;
                    if ($chg < -20) $decliningCount++;
                }

                $attrRisk = match(true) {
                    $decliningCount >= 4 => 'Elevated',
                    $decliningCount >= 2 => 'Watch',
                    default              => 'Low',
                };

                $employee->task_metrics = [
                    'total'           => $total,
                    'done'            => $done,
                    'in_progress'     => $inProg,
                    'completion_rate' => $rate,
                    'story_points'    => $spDone ?: 0,
                    'bug_count'       => $bugCount,
                    'cycle_time'      => $cycleTime,
                    'has_data'        => $total > 0,
                    'engagement_score'=> $engScore,
                    'engagement_level'=> $engLevel,
                    'attrition_risk'  => $attrRisk,
                    'active_days'     => $activeDays > 0 ? (int)$activeDays : null,
                    'collaborators'   => $collaborators > 0 ? (int)$collaborators : null,
                ];
                return $employee;
            });

        $withData   = $employees->filter(fn($e) => $e->task_metrics['has_data']);
        $avgRate    = $withData->count() > 0
            ? round($withData->avg(fn($e) => $e->task_metrics['completion_rate']))
            : null;
        $avgCycle   = $withData->filter(fn($e) => $e->task_metrics['cycle_time'] !== null)->count() > 0
            ? round($withData->filter(fn($e) => $e->task_metrics['cycle_time'] !== null)
                              ->avg(fn($e) => $e->task_metrics['cycle_time']))
            : null;

        $orgStats = [
            'total_employees'        => $employees->count(),
            'employees_with_signals' => $withData->count(),
            'avg_completion_rate'    => $avgRate,
            'avg_cycle_time'         => $avgCycle,
        ];

        return view('intelligence.dashboard', compact('employees', 'orgStats', 'departments', 'period', 'deptId'));
    }

    public function employeeSignals(Employee $employee, Request $request)
    {
        // Redirect to the employee show page Work Pulse tab — the old raw signal
        // page (intelligence.employee) showed SignalSnapshot scores which were
        // from the resource-matching pipeline, not genuine work-quality signals.
        return redirect()->route('employees.show', $employee)->with(
            '_open_tab', 'tab-signals'
        );
    }

    public function computeSignals(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();

        $employees = Employee::where('organization_id', $orgId)
            ->where('is_active', true)
            ->get();

        foreach ($employees as $employee) {
            ComputeEmployeeSignalsJob::dispatch($employee);
        }

        return back()->with('success', "Signal computation queued for {$employees->count()} employees.");
    }
}
