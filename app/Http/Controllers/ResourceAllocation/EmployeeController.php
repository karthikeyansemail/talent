<?php

namespace App\Http\Controllers\ResourceAllocation;

use App\Http\Controllers\Controller;
use App\Jobs\SyncJiraTasksJob;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
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
        $employee->load(['department', 'tasks', 'resourceMatches.project', 'resume', 'signalSnapshots', 'signals']);
        $signalInsights = $this->computeSignalInsights($employee);
        return view('employees.show', compact('employee', 'signalInsights'));
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

    public function syncJiraTasks(Employee $employee)
    {
        $this->authorizeOrg($employee);
        SyncJiraTasksJob::dispatch($employee);
        return back()->with('success', 'Jira sync queued.');
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

            $insights['task'] = [
                'period'              => $currPeriod,
                'prev_period'         => $prevPeriod,
                'total_current'       => $currTasks->count(),
                'total_prev'          => $prevTasks->count(),
                'done_current'        => $doneCurr,
                'done_prev'           => $donePrev,
                'completion_rate'     => $rateCurr,
                'completion_rate_prev'=> $ratePrev,
                'spillover'           => $spillover,
                'velocity_sp'         => $spCurr > 0 ? $spCurr : null,
                'velocity_sp_prev'    => $spPrev > 0 ? $spPrev : null,
                'high_pct'            => $totalAll > 0 ? round($highAll / $totalAll * 100) : null,
                'bug_pct'             => $totalAll > 0 ? round($bugAll / $totalAll * 100) : null,
                'unique_task_types'   => $tasks->pluck('task_type')->filter()->unique()->count(),
            ];

            // Task observations
            if ($rateCurr !== null && $ratePrev !== null && abs($rateCurr - $ratePrev) >= 10) {
                $dir = $rateCurr > $ratePrev ? 'rose' : 'dropped';
                $insights['observations'][] = "Task completion rate {$dir} from {$ratePrev}% → {$rateCurr}% this period";
            }
            if ($spillover >= 3) {
                $insights['observations'][] = "Task spillover: {$spillover} tasks from the previous period remain incomplete";
            }
            if ($spCurr > 0 && $spPrev > 0) {
                $t = $this->trendPct($spCurr, $spPrev);
                if (abs($t) >= 15) {
                    $dir = $t > 0 ? 'increased' : 'decreased';
                    $absT = abs($t);
                    $insights['observations'][] = "Story point velocity {$dir} from {$spPrev} → {$spCurr} SP ({$absT}% change)";
                }
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

        return $insights;
    }

    private function trendPct(float $current, float $previous): int
    {
        if ($previous == 0) return 0;
        return (int) round(($current - $previous) / abs($previous) * 100);
    }
}
