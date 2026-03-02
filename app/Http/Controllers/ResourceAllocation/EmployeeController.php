<?php

namespace App\Http\Controllers\ResourceAllocation;

use App\Http\Controllers\Controller;
use App\Jobs\ComputeWorkPulseInsightsJob;
use App\Jobs\SyncDevOasTasksJob;
use App\Jobs\SyncGitHubProjectsJob;
use App\Jobs\SyncGitHubSignalsJob;
use App\Jobs\SyncJiraTasksJob;
use App\Jobs\SyncSlackMetricsJob;
use App\Jobs\SyncTeamsMetricsJob;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAiInsight;
use App\Models\IntegrationConnection;
use App\Models\JiraConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $org = Auth::user()->currentOrganization();
        if (!$org->canUse('resource_allocation')) {
            return redirect()->route('dashboard')
                ->with('error', 'Resource Allocation is not available on the Free plan. Upgrade to Cloud Enterprise to access this feature.');
        }

        $orgId = Auth::user()->currentOrganizationId();
        $query = Employee::where('organization_id', $orgId)->with('department');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('designation')) {
            $query->where('designation', $request->designation);
        }
        if ($request->filled('skill')) {
            $skill = $request->skill;
            $query->where('skills_from_resume', 'like', "%\"{$skill}\"%");
        }

        $employees = $query->latest()->paginate(15);
        $departments = Department::where('organization_id', $orgId)->get();
        $designations = Employee::where('organization_id', $orgId)
            ->whereNotNull('designation')->where('designation', '!=', '')
            ->distinct()->orderBy('designation')->pluck('designation');
        $allSkills = Employee::where('organization_id', $orgId)
            ->whereNotNull('skills_from_resume')
            ->pluck('skills_from_resume')
            ->filter()
            ->flatten()
            ->filter(fn($s) => is_string($s) && $s !== '')
            ->unique()->sort()->values();

        return view('employees.index', compact('employees', 'departments', 'designations', 'allSkills'));
    }

    public function create()
    {
        $departments = Department::where('organization_id', Auth::user()->currentOrganizationId())->get();
        return view('employees.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'department_id' => 'nullable|exists:departments,id',
            'designation' => 'nullable|string|max:255',
        ]);

        $validated['organization_id'] = Auth::user()->currentOrganizationId();
        $employee = Employee::create($validated);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee created.');
    }

    public function show(Employee $employee)
    {
        $this->authorizeOrg($employee);
        $employee->load(['department', 'tasks', 'resourceMatches.project', 'resume', 'signals', 'sprintSheets']);
        $signalInsights = $this->computeSignalInsights($employee);
        $aiInsight = EmployeeAiInsight::where('employee_id', $employee->id)->first();
        return view('employees.show', compact('employee', 'signalInsights', 'aiInsight'));
    }

    public function analyzeWorkPulse(Employee $employee)
    {
        $this->authorizeOrg($employee);
        $employee->load('tasks');

        if ($employee->tasks->isEmpty()) {
            if (request()->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'No task data to analyze.'], 422);
            }
            return back()->with('error', 'No task data to analyze. Sync Jira tasks first.');
        }

        Cache::put(ComputeWorkPulseInsightsJob::cacheKey($employee->id), [
            'status' => 'running',
            'pct'    => 5,
            'phase'  => 'Queuing analysis...',
        ], now()->addMinutes(10));

        ComputeWorkPulseInsightsJob::dispatch($employee);

        if (request()->expectsJson()) {
            return response()->json(['status' => 'queued', 'employee_id' => $employee->id]);
        }

        return back()->with('success', 'AI analysis queued. Reload in a moment to see results.');
    }

    public function workPulseStatus(Employee $employee)
    {
        $this->authorizeOrg($employee);
        $progress = Cache::get(ComputeWorkPulseInsightsJob::cacheKey($employee->id));

        if (!$progress) {
            return response()->json(['status' => 'idle']);
        }

        if ($progress['status'] === 'completed') {
            return response()->json([
                'status'       => 'completed',
                'pct'          => 100,
                'phase'        => $progress['phase'],
                'completed_at' => $progress['completed_at'] ?? now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'status' => $progress['status'],
            'pct'    => $progress['pct']   ?? 0,
            'phase'  => $progress['phase'] ?? 'Processing...',
        ]);
    }

    public function edit(Employee $employee)
    {
        $this->authorizeOrg($employee);
        $departments = Department::where('organization_id', Auth::user()->currentOrganizationId())->get();
        return view('employees.edit', compact('employee', 'departments'));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorizeOrg($employee);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'department_id' => 'nullable|exists:departments,id',
            'designation' => 'nullable|string|max:255',
        ]);

        $employee->update($validated);
        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $this->authorizeOrg($employee);
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted.');
    }

    public function syncWorkData(Employee $employee)
    {
        $this->authorizeOrg($employee);
        $orgId    = $employee->organization_id;
        $cacheKey = SyncJiraTasksJob::cacheKey($employee->id);

        // ── Discover active connections first (no dispatching yet) ────────
        $jira           = JiraConnection::where('organization_id', $orgId)->where('is_active', true)->first();
        $devops         = IntegrationConnection::where('organization_id', $orgId)->where('type', 'devops_boards')->where('is_active', true)->first();
        $githubProjects = IntegrationConnection::where('organization_id', $orgId)->where('type', 'github')->where('is_active', true)->first();
        $slack          = IntegrationConnection::where('organization_id', $orgId)->where('type', 'slack')->where('is_active', true)->first();
        $teams          = IntegrationConnection::where('organization_id', $orgId)->where('type', 'teams')->where('is_active', true)->first();
        $githubSignals  = IntegrationConnection::where('organization_id', $orgId)->where('type', 'github_signals')->where('is_active', true)->first();

        $dispatched = array_filter([
            $jira           ? 'Jira'            : null,
            $devops         ? 'DevOps Boards'   : null,
            $githubProjects ? 'GitHub Projects' : null,
            $slack          ? 'Slack'           : null,
            $teams          ? 'Teams'           : null,
            $githubSignals  ? 'GitHub Signals'  : null,
        ]);

        if (empty($dispatched)) {
            $msg = 'No data sources connected. Configure integrations in Settings → Integrations.';
            if (request()->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $sourceList = implode(', ', $dispatched);
        $hasJira    = (bool) $jira;

        // ── Set initial cache state BEFORE dispatching ────────────────────
        // This must come before dispatch() so that with QUEUE_CONNECTION=sync
        // the job's own cache writes (10%→100%) correctly override this value,
        // and with async queues the poller sees "running" immediately.
        if ($hasJira) {
            Cache::put($cacheKey, [
                'status' => 'running',
                'pct'    => 5,
                'phase'  => "Syncing: {$sourceList}...",
            ], now()->addMinutes(10));
        } else {
            Cache::put($cacheKey, [
                'status'       => 'completed',
                'pct'          => 100,
                'phase'        => "Sync dispatched: {$sourceList}",
                'completed_at' => now()->toIso8601String(),
            ], now()->addMinutes(10));
        }

        // ── Dispatch jobs ─────────────────────────────────────────────────
        if ($jira)           SyncJiraTasksJob::dispatch($employee);
        if ($devops)         SyncDevOasTasksJob::dispatch($devops);
        if ($githubProjects) SyncGitHubProjectsJob::dispatch($githubProjects);
        if ($slack)          SyncSlackMetricsJob::dispatch($slack);
        if ($teams)          SyncTeamsMetricsJob::dispatch($teams);
        if ($githubSignals)  SyncGitHubSignalsJob::dispatch($githubSignals);

        if (request()->expectsJson()) {
            return response()->json(['status' => 'queued', 'employee_id' => $employee->id, 'sources' => $dispatched]);
        }

        return back()->with('success', "Sync queued for: {$sourceList}.");
    }

    public function workDataSyncStatus(Employee $employee)
    {
        $this->authorizeOrg($employee);
        $progress = Cache::get(SyncJiraTasksJob::cacheKey($employee->id));

        if (!$progress) {
            return response()->json(['status' => 'idle']);
        }

        if ($progress['status'] === 'completed') {
            return response()->json([
                'status'       => 'completed',
                'pct'          => 100,
                'phase'        => $progress['phase'],
                'completed_at' => $progress['completed_at'] ?? now()->toIso8601String(),
                'redirect'     => route('employees.show', $employee),
            ]);
        }

        return response()->json([
            'status' => $progress['status'],
            'pct'    => $progress['pct']   ?? 0,
            'phase'  => $progress['phase'] ?? 'Processing...',
        ]);
    }

    public function signalIntelligenceHtml(Employee $employee)
    {
        $this->authorizeOrg($employee);
        $employee->load(['tasks', 'signals', 'sprintSheets']);
        $signalInsights = $this->computeSignalInsights($employee);
        $aiInsight = EmployeeAiInsight::where('employee_id', $employee->id)->first();
        return view('employees.partials.signal-intelligence', compact('employee', 'signalInsights', 'aiInsight'));
    }

    private function authorizeOrg(Employee $employee): void
    {
        if ($employee->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }
    }

    private function computeSignalInsights(Employee $employee): array
    {
        $insights = ['task' => [], 'comm' => [], 'code' => [], 'observations' => []];

        // ── Task signals (from employee_tasks) ────────────────────────────
        $tasks = $employee->tasks;
        if ($tasks->isNotEmpty()) {
            $byPeriod = $tasks
                ->filter(fn($t) => $t->source_created_at !== null)
                ->groupBy(fn($t) => $t->source_created_at->format('Y-m'))
                ->sortKeysDesc();

            $periods       = $byPeriod->keys()->values();
            $currPeriod    = $periods->get(0);
            $prevPeriod    = $periods->get(1);
            $currTasks     = $currPeriod ? $byPeriod->get($currPeriod) : collect();
            $prevTasks     = $prevPeriod ? $byPeriod->get($prevPeriod) : collect();

            $doneCurr = $currTasks->where('status', 'Done')->count();
            $donePrev = $prevTasks->where('status', 'Done')->count();
            $rateCurr = $currTasks->count() > 0 ? round($doneCurr / $currTasks->count() * 100) : null;
            $ratePrev = $prevTasks->count() > 0 ? round($donePrev / $prevTasks->count() * 100) : null;

            // Spillover: tasks from previous period not yet completed
            $spillover = $prevTasks->whereNotIn('status', ['Done', 'Closed', 'Resolved', 'Won\'t Do'])->count();

            $spCurr = (float) $currTasks->where('status', 'Done')->whereNotNull('story_points')->sum('story_points');
            $spPrev = (float) $prevTasks->where('status', 'Done')->whereNotNull('story_points')->sum('story_points');

            $totalAll = $tasks->count();
            $highAll  = $tasks->whereIn('priority', ['High', 'Highest', 'Critical'])->count();
            $bugAll   = $tasks->where('task_type', 'Bug')->count();

            // Cycle time (avg days from creation to completion) for current and prev period
            $doneWithDates = $currTasks->where('status', 'Done')
                ->filter(fn($t) => $t->completed_at && $t->source_created_at);
            $cycleTimeAvg = $doneWithDates->count() > 0
                ? (int) round($doneWithDates->avg(fn($t) => $t->source_created_at->diffInDays($t->completed_at)))
                : null;

            $doneWithDatesPrev = $prevTasks->where('status', 'Done')
                ->filter(fn($t) => $t->completed_at && $t->source_created_at);
            $cycleTimePrev = $doneWithDatesPrev->count() > 0
                ? (int) round($doneWithDatesPrev->avg(fn($t) => $t->source_created_at->diffInDays($t->completed_at)))
                : null;

            // Aging: all-time open tasks older than 30 days
            $agingTasks = $tasks
                ->whereNotIn('status', ['Done', 'Closed', 'Resolved', "Won't Do"])
                ->filter(fn($t) => $t->source_created_at && $t->source_created_at->diffInDays(now()) > 30)
                ->count();

            // Bug resolution rate (all-time: done bugs / all bugs)
            $allBugs = $tasks->where('task_type', 'Bug');
            $bugResolutionRate = $allBugs->count() > 0
                ? (int) round($allBugs->where('status', 'Done')->count() / $allBugs->count() * 100)
                : null;

            // High-priority completion rate (all-time: done high/critical / all high/critical)
            $allHigh = $tasks->whereIn('priority', ['High', 'Highest', 'Critical']);
            $highPriorityDoneRate = $allHigh->count() > 0
                ? (int) round($allHigh->where('status', 'Done')->count() / $allHigh->count() * 100)
                : null;

            $insights['task'] = [
                'period'                => $currPeriod,
                'prev_period'           => $prevPeriod,
                'total_current'         => $currTasks->count(),
                'total_prev'            => $prevTasks->count(),
                'done_current'          => $doneCurr,
                'done_prev'             => $donePrev,
                'completion_rate'       => $rateCurr,
                'completion_rate_prev'  => $ratePrev,
                'spillover'             => $spillover,
                'velocity_sp'           => $spCurr > 0 ? $spCurr : null,
                'velocity_sp_prev'      => $spPrev > 0 ? $spPrev : null,
                'high_pct'              => $totalAll > 0 ? round($highAll / $totalAll * 100) : null,
                'bug_pct'               => $totalAll > 0 ? round($bugAll / $totalAll * 100) : null,
                'unique_task_types'     => $tasks->pluck('task_type')->filter()->unique()->count(),
                'cycle_time_avg'        => $cycleTimeAvg,
                'cycle_time_prev'       => $cycleTimePrev,
                'aging_tasks'           => $agingTasks,
                'bug_resolution_rate'   => $bugResolutionRate,
                'high_priority_done_rate' => $highPriorityDoneRate,
            ];

            $currLabel = $this->formatPeriod($currPeriod);
            $prevLabel = $this->formatPeriod($prevPeriod);
            $insights['task']['curr_period_label'] = $currLabel;
            $insights['task']['prev_period_label'] = $prevLabel;

            // Task observations — each includes explicit period context
            if ($rateCurr !== null && $ratePrev !== null && abs($rateCurr - $ratePrev) >= 10) {
                $dir = $rateCurr > $ratePrev ? 'rose' : 'dropped';
                $insights['observations'][] = "Task completion rate {$dir}: {$ratePrev}% ({$prevLabel}) → {$rateCurr}% ({$currLabel})";
            }
            if ($spillover >= 3) {
                $insights['observations'][] = "Task spillover: {$spillover} tasks from {$prevLabel} remain incomplete in {$currLabel}";
            }
            if ($spCurr > 0 && $spPrev > 0) {
                $t = $this->trendPct($spCurr, $spPrev);
                if (abs($t) >= 15) {
                    $dir = $t > 0 ? 'increased' : 'decreased';
                    $absT = abs($t);
                    $insights['observations'][] = "Story point velocity {$dir}: {$spPrev} SP ({$prevLabel}) → {$spCurr} SP ({$currLabel}) — {$absT}% change";
                }
            }
            if ($cycleTimeAvg !== null && $cycleTimePrev !== null && $cycleTimePrev > 0) {
                $t = $this->trendPct($cycleTimeAvg, $cycleTimePrev);
                if (abs($t) >= 20) {
                    $dir = $t > 0 ? 'slower' : 'faster';
                    $absT = abs($t);
                    $insights['observations'][] = "Avg task cycle time {$dir}: {$cycleTimePrev}d ({$prevLabel}) → {$cycleTimeAvg}d ({$currLabel}) — {$absT}% change";
                }
            }
            if ($agingTasks >= 2) {
                $insights['observations'][] = "{$agingTasks} open tasks have been unresolved for more than 30 days";
            }
        }

        // ── Signal trends (employee_signals) ──────────────────────────────
        $signals = $employee->signals;
        $metricLabels = [
            'messages_sent_count'         => 'Messages sent',
            'active_days_count'           => 'Active days',
            'unique_collaborators_count'  => 'Unique collaborators',
            'after_hours_message_pct'     => 'After-hours messaging',
            'channel_messages_count'      => 'Channel messages',
            'private_chat_messages_count' => 'Direct messages',
            'calls_count'                 => 'Calls',
            'meetings_attended_count'     => 'Meetings attended',
            'commit_count'                => 'Commits',
            'pr_reviews_count'            => 'PR reviews',
            'lines_added_avg'             => 'Avg lines added/commit',
            'lines_removed_avg'           => 'Avg lines removed/commit',
        ];

        foreach (['slack', 'teams', 'github'] as $src) {
            $srcSigs  = $signals->where('source_type', $src);
            if ($srcSigs->isEmpty()) continue;
            $category = $src === 'github' ? 'code' : 'comm';

            foreach ($srcSigs->pluck('metric_key')->unique() as $key) {
                $rows    = $srcSigs->where('metric_key', $key)->sortByDesc('period')->values();
                $curr    = $rows->get(0);
                $prev    = $rows->get(1);
                $currVal = $curr ? (float) $curr->metric_value : null;
                $prevVal = $prev ? (float) $prev->metric_value : null;
                $trend   = ($currVal !== null && $prevVal !== null && $prevVal != 0)
                    ? (int) round(($currVal - $prevVal) / abs($prevVal) * 100)
                    : null;

                if (!isset($insights[$category][$key])) {
                    $insights[$category][$key] = [
                        'value'       => $currVal,
                        'prev'        => $prevVal,
                        'trend_pct'   => $trend,
                        'period'      => $curr?->period,
                        'prev_period' => $prev?->period,
                        'unit'        => $curr?->metric_unit,
                        'source'      => ucfirst($src),
                    ];
                }

                if ($trend !== null && abs($trend) >= 20 && $currVal !== null && $prevVal !== null) {
                    $label   = $metricLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                    $dir     = $trend > 0 ? 'increased' : 'decreased';
                    $absT    = abs($trend);
                    $dispCurr = fmod($currVal, 1.0) == 0.0 ? (int)$currVal : round($currVal, 1);
                    $dispPrev = fmod($prevVal, 1.0) == 0.0 ? (int)$prevVal : round($prevVal, 1);
                    $insights['observations'][] = "{$label} {$dir} from {$dispPrev} → {$dispCurr} ({$absT}% change)";
                }
            }
        }

        // ── Engagement Score & Attrition Risk ─────────────────────────
        $insights['engagement'] = $this->computeEngagementRisk($insights);

        return $insights;
    }

    /**
     * Compute a 0–100 engagement score and an attrition risk level
     * from task + comm signal data already collected in $insights.
     *
     * Engagement score components (each 0–100):
     *   40% — task completion rate (curr period)
     *   20% — Slack/Teams active days this week (out of 5)
     *   20% — collaboration breadth (unique collaborators, capped at 10)
     *   20% — work-life balance (inverse of after_hours_pct, capped at 50%)
     *
     * Attrition risk: counts declining trends across key metrics and maps
     * to three levels: Low | Watch | Elevated
     * Language is purposely non-predictive — it reflects current engagement
     * signals, NOT a prediction of departure.
     */
    private function computeEngagementRisk(array $insights): array
    {
        $ti = $insights['task'] ?? [];
        $ci = $insights['comm'] ?? [];

        // ── Engagement components ──────────────────────────────────────
        $completionScore  = null;
        $activeDaysScore  = null;
        $collabScore      = null;
        $wlbScore         = null;
        $sentimentScore   = null;

        // Task completion (40 pts max)
        if (($ti['completion_rate'] ?? null) !== null) {
            $completionScore = min(100, (int) $ti['completion_rate']);
        }

        // Slack active days (20 pts max) — normalize vs 5 days/week
        $activeDaysVal = $ci['active_days_count']['value'] ?? null;
        if ($activeDaysVal !== null) {
            $activeDaysScore = (int) min(100, round($activeDaysVal / 5 * 100));
        }

        // Collaboration breadth (20 pts max) — normalize vs 10 collaborators
        $collabVal = $ci['unique_collaborators_count']['value'] ?? null;
        if ($collabVal !== null) {
            $collabScore = (int) min(100, round($collabVal / 10 * 100));
        }

        // Work-life balance (20 pts max) — after_hours_pct: 0%=100pts, ≥50%=0pts
        $ahPct = $ci['after_hours_message_pct']['value'] ?? null;
        if ($ahPct !== null) {
            $wlbScore = (int) max(0, 100 - round($ahPct * 2));
        }

        // Sentiment signal (bonus context, 0-100 where 0=very negative, 50=neutral, 100=very positive)
        $sentimentVal = $ci['message_sentiment_score']['value'] ?? null;
        if ($sentimentVal !== null) {
            $sentimentScore = (int) min(100, max(0, round(($sentimentVal + 100) / 2)));
        }

        // ── Behavioral signals ─────────────────────────────────────────
        $initiatedVal    = $ci['initiated_conversations_count']['value'] ?? null;
        $inboundMentions = $ci['inbound_mentions_count']['value'] ?? null;
        $questionsAsked  = $ci['questions_asked_count']['value'] ?? null;
        $proactiveStatus = $ci['proactive_status_count']['value'] ?? null;
        $messageCountVal = $ci['messages_sent_count']['value'] ?? null;

        $behaviorNudge  = 0;
        $hasChasePattern = false;
        $initiationRatioPct = null;

        // Initiation ratio: starting conversations = strong engagement signal
        if ($initiatedVal !== null && $messageCountVal !== null && $messageCountVal > 0) {
            $initiationRatio = $initiatedVal / $messageCountVal;
            $initiationRatioPct = (int) round($initiationRatio * 100);
            if ($initiationRatio >= 0.4) {
                $behaviorNudge += 5; // strong initiator
            } elseif ($initiationRatio <= 0.15) {
                $behaviorNudge -= 3; // mostly passive responder
            }
        }

        // Chase pattern: repeatedly tagged by others without self-initiating = negative signal
        if ($inboundMentions !== null && $initiatedVal !== null && $inboundMentions >= 5 && $initiatedVal <= 2) {
            $behaviorNudge -= 5;
            $hasChasePattern = true;
        }

        // Learner signal: asking questions despite lower completion = active engagement
        if ($questionsAsked !== null && $questionsAsked >= 3) {
            $behaviorNudge += 3;
        }

        // Proactive communication: employee updates team without being asked = positive
        if ($proactiveStatus !== null && $proactiveStatus >= 3) {
            $behaviorNudge += 3;
        }

        $behaviorNudge = max(-8, min(8, $behaviorNudge));

        // Weighted composite score
        $score = null;
        $weights = [];
        if ($completionScore !== null)  $weights[] = ['v' => $completionScore, 'w' => 0.40];
        if ($activeDaysScore !== null)  $weights[] = ['v' => $activeDaysScore, 'w' => 0.20];
        if ($collabScore !== null)      $weights[] = ['v' => $collabScore,     'w' => 0.20];
        if ($wlbScore !== null)         $weights[] = ['v' => $wlbScore,        'w' => 0.20];

        if (!empty($weights)) {
            $totalWeight = array_sum(array_column($weights, 'w'));
            $raw = array_sum(array_map(fn($x) => $x['v'] * $x['w'], $weights)) / $totalWeight;
            $score = (int) round($raw);

            // If sentiment is available, nudge score by ±5 pts
            if ($sentimentScore !== null) {
                $nudge = (int) round(($sentimentScore - 50) / 10); // -5 to +5
                $score = max(0, min(100, $score + $nudge));
            }

            // Apply behavioral nudge (±8 pts max)
            $score = max(0, min(100, $score + $behaviorNudge));
        }

        // ── Engagement level label ─────────────────────────────────────
        $level = null;
        if ($score !== null) {
            $level = match(true) {
                $score >= 75 => 'Highly Engaged',
                $score >= 55 => 'Engaged',
                $score >= 35 => 'Moderate',
                default       => 'Disengaged',
            };
        }

        // ── Attrition risk: count declining metrics ────────────────────
        $decliningCount = 0;

        // Task completion declining
        if (($ti['completion_rate'] ?? null) !== null && ($ti['completion_rate_prev'] ?? null) !== null) {
            if ($ti['completion_rate'] < $ti['completion_rate_prev'] - 10) {
                $decliningCount++;
            }
        }

        // SP velocity declining
        if (($ti['velocity_sp'] ?? null) !== null && ($ti['velocity_sp_prev'] ?? null) !== null && $ti['velocity_sp_prev'] > 0) {
            $spTrend = ($ti['velocity_sp'] - $ti['velocity_sp_prev']) / $ti['velocity_sp_prev'] * 100;
            if ($spTrend < -20) {
                $decliningCount++;
            }
        }

        // Comm signals declining
        foreach (['messages_sent_count', 'active_days_count', 'unique_collaborators_count'] as $key) {
            $trend = $ci[$key]['trend_pct'] ?? null;
            if ($trend !== null && $trend < -20) {
                $decliningCount++;
            }
        }

        // Negative sentiment trend
        $sentTrend = $ci['message_sentiment_score']['trend_pct'] ?? null;
        if ($sentTrend !== null && $sentTrend < -30) {
            $decliningCount++;
        }

        // Chase pattern (persistent) adds to attrition risk
        if ($hasChasePattern) {
            $decliningCount++;
        }

        $risk = match(true) {
            $decliningCount >= 4 => 'Elevated',
            $decliningCount >= 2 => 'Watch',
            default              => 'Low',
        };

        return [
            'score'           => $score,
            'level'           => $level,
            'risk'            => $risk,
            'declining_count' => $decliningCount,
            'sentiment_score' => $sentimentScore,
            'components'      => [
                'completion'  => $completionScore,
                'active_days' => $activeDaysScore,
                'collab'      => $collabScore,
                'wlb'         => $wlbScore,
            ],
            'behavioral'      => [
                'initiated'        => $initiatedVal !== null ? (int) $initiatedVal : null,
                'inbound_mentions' => $inboundMentions !== null ? (int) $inboundMentions : null,
                'questions_asked'  => $questionsAsked !== null ? (int) $questionsAsked : null,
                'proactive_status' => $proactiveStatus !== null ? (int) $proactiveStatus : null,
                'chase_pattern'    => $hasChasePattern,
                'initiation_ratio' => $initiationRatioPct,
            ],
        ];
    }

    private function formatPeriod(?string $period): string
    {
        if (!$period) return '';
        [$year, $month] = explode('-', $period);
        return date('M Y', mktime(0, 0, 0, (int) $month, 1, (int) $year));
    }

    private function trendPct(float $current, float $previous): int
    {
        if ($previous == 0) return 0;
        return (int) round(($current - $previous) / abs($previous) * 100);
    }
}
