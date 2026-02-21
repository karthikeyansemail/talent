@extends('layouts.app')
@section('title', $employee->full_name)
@section('page-title', 'Employee Details')
@section('content')

{{-- Profile Hero --}}
<div class="profile-hero">
    <div class="avatar-lg">{{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}</div>
    <div class="profile-info">
        <h1>{{ $employee->full_name }}</h1>
        <div class="profile-meta">
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
                {{ $employee->designation ?? 'No designation' }}
            </span>
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                {{ $employee->email }}
            </span>
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                {{ $employee->department?->name ?? 'No department' }}
            </span>
            <span class="status-indicator">
                <span class="status-dot {{ $employee->is_active ? 'active' : 'inactive' }}"></span>
                {{ $employee->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
    </div>
</div>

{{-- Action Bar --}}
<div class="action-bar">
    <a href="{{ route('employees.edit', $employee) }}" class="action-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit Employee
    </a>
    <form method="POST" action="{{ route('employees.syncJira', $employee) }}" style="display:inline">
        @csrf
        <button type="submit" class="action-btn action-primary">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            Sync Jira Tasks
        </button>
    </form>
</div>

{{-- Tabs --}}
<div data-tabs class="tabs">
    <button class="tab active" data-tab="tab-overview">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Overview
    </button>
    <button class="tab" data-tab="tab-matches">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        Project Matches
        @if($employee->resourceMatches->count())
        <span class="badge badge-blue" style="font-size:11px;padding:2px 7px">{{ $employee->resourceMatches->count() }}</span>
        @endif
    </button>
    @if(in_array(auth()->user()->role, ['management','org_admin','super_admin']))
    <button class="tab" data-tab="tab-signals">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Signal Intelligence
    </button>
    @endif
</div>

{{-- Tab: Overview --}}
<div id="tab-overview" class="tab-content active">
    <div class="grid-2">
        {{-- Profile Card --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Profile Information
                </span>
            </div>
            <div class="card-body">
                <div class="detail-list">
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
                        <div class="row-content"><div class="row-label">Email</div><div class="row-value">{{ $employee->email }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></div>
                        <div class="row-content"><div class="row-label">Department</div><div class="row-value">{{ $employee->department?->name ?? 'N/A' }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg></div>
                        <div class="row-content"><div class="row-label">Designation</div><div class="row-value">{{ $employee->designation ?? 'N/A' }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                        <div class="row-content"><div class="row-label">Status</div><div class="row-value"><span class="status-indicator"><span class="status-dot {{ $employee->is_active ? 'active' : 'inactive' }}"></span>{{ $employee->is_active ? 'Active' : 'Inactive' }}</span></div></div>
                    </div>
                    @if($employee->tasks->count())
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
                        <div class="row-content"><div class="row-label">Tasks Synced</div><div class="row-value">{{ $employee->tasks->count() }} tasks across {{ $employee->tasks->groupBy('source_type')->count() }} {{ Str::plural('source', $employee->tasks->groupBy('source_type')->count()) }}</div></div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Skill Profile Card --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    Skill Profile
                </span>
            </div>
            <div class="card-body">
                @if($employee->skills_from_jira && isset($employee->skills_from_jira['extracted_skills']) && count($employee->skills_from_jira['extracted_skills']))
                    @foreach($employee->skills_from_jira['extracted_skills'] as $skill)
                    <div class="skill-bar">
                        <span class="label">{{ $skill['skill'] ?? 'N/A' }}</span>
                        <div class="bar"><div class="fill" style="width:{{ ($skill['confidence'] ?? 0) * 100 }}%"></div></div>
                        <span class="percent">{{ ucfirst($skill['depth'] ?? '') }}</span>
                    </div>
                    @endforeach
                @elseif($employee->skills_from_resume && is_array($employee->skills_from_resume) && count($employee->skills_from_resume))
                    <div class="tags" style="padding:4px 0">
                        @foreach($employee->skills_from_resume as $skill)
                        <span class="tag">{{ $skill }}</span>
                        @endforeach
                    </div>
                    <div class="empty-hint" style="margin-top:12px">Sync Jira tasks to get skill confidence scores</div>
                @else
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <p>No skill data available</p>
                        <div class="empty-hint">Sync Jira tasks to extract skills automatically</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tab: Project Matches --}}
<div id="tab-matches" class="tab-content">
    @if($employee->resourceMatches->count())
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Project Matches
            </span>
            <span class="text-sm text-muted" style="margin-left:auto">AI-ranked suitability for active projects</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Match Score</th>
                    <th>Strengths</th>
                    <th>Skill Gaps</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @foreach($employee->resourceMatches->sortByDesc('match_score') as $match)
            <tr>
                <td><a href="{{ route('projects.show', $match->project_id) }}" style="font-weight:500">{{ $match->project->name }}</a></td>
                <td>
                    @php $score = $match->match_score; $cls = $score >= 70 ? 'high' : ($score >= 40 ? 'medium' : 'low'); @endphp
                    <span class="score {{ $cls }}" style="font-size:16px">{{ number_format($score, 1) }}</span>
                </td>
                <td><div class="tags">@foreach($match->strength_areas ?? [] as $s)<span class="tag">{{ $s }}</span>@endforeach</div></td>
                <td><div class="tags">@foreach($match->skill_gaps ?? [] as $g)<span class="tag" style="background:#fecaca;color:#b91c1c;border-color:#fecaca">{{ $g }}</span>@endforeach</div></td>
                <td>@if($match->is_assigned)<span class="badge badge-green">Assigned</span>@else<span class="badge badge-gray">Unassigned</span>@endif</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                <p>No project matches yet</p>
                <div class="empty-hint">Run "Find Best Resources" on a project to see matches here</div>
            </div>
        </div>
    </div>
    @endif
</div>
@if(in_array(auth()->user()->role, ['management','org_admin','super_admin']))
{{-- Tab: Signal Intelligence --}}
<div id="tab-signals" class="tab-content">
@php
    // ── Unified tasks (all sources) ────────────────────────────────
    $allTasks    = $employee->tasks;
    $tasksBySource = $allTasks->groupBy('source_type');
    $totalTasks  = $allTasks->count();

    // For backward-compat metric cards: aggregate all sources
    $statusGroups = $allTasks->groupBy('status');
    $doneTasks    = $statusGroups->get('Done', collect())->count();
    $inProgTasks  = collect();
    foreach(['In Progress','In Review','In Development','Review','Active'] as $_s) {
        $inProgTasks = $inProgTasks->merge($statusGroups->get($_s, collect()));
    }
    $inProgCount  = $inProgTasks->count();
    $completionRate = $totalTasks > 0 ? round($doneTasks / $totalTasks * 100) : null;

    $priorityGroups = $allTasks->groupBy('priority');
    $highCount   = $priorityGroups->get('High', collect())->count() + $priorityGroups->get('Highest', collect())->count() + $priorityGroups->get('Critical', collect())->count();
    $medCount    = $priorityGroups->get('Medium', collect())->count();
    $lowCount    = $priorityGroups->get('Low', collect())->count() + $priorityGroups->get('Lowest', collect())->count();

    $typeGroups  = $allTasks->groupBy('task_type');
    $bugCount    = $typeGroups->get('Bug', collect())->count();
    $storyCount  = $typeGroups->get('Story', collect())->count() + $typeGroups->get('UserStory', collect())->count();
    $taskCount2  = $typeGroups->get('Task', collect())->count();

    $totalSP    = $allTasks->whereNotNull('story_points')->sum('story_points');
    $doneSP     = $allTasks->where('status','Done')->whereNotNull('story_points')->sum('story_points');

    // ── Source labels ──────────────────────────────────────────────
    $sourceLabels = [
        'jira'            => 'Jira',
        'zoho_projects'   => 'Zoho Projects',
        'devops_boards'   => 'DevOps Boards',
        'github_projects' => 'GitHub Projects',
    ];

    // ── Employee signals (communication + code) ───────────────────
    $employeeSignals  = $employee->signals ?? collect();
    $slackSignals     = $employeeSignals->where('source_type', 'slack');
    $teamsSignals     = $employeeSignals->where('source_type', 'teams');
    $githubSignals    = $employeeSignals->where('source_type', 'github');
    $commSignals      = $slackSignals->merge($teamsSignals);

    // Helper: get latest signal value for a metric key
    $sigVal = fn($collection, $key) =>
        $collection->where('metric_key', $key)->sortByDesc('period')->first()?->metric_value;

    // ── Project matches ───────────────────────────────────────────
    $matches     = $employee->resourceMatches;
    $bestMatch   = $matches->sortByDesc('match_score')->first();
    $avgScore    = $matches->count() ? round($matches->avg('match_score'), 1) : null;
    $allStrengths = $matches->flatMap(fn($m) => $m->strength_areas ?? [])->unique()->values();
    $allGaps      = $matches->flatMap(fn($m) => $m->skill_gaps ?? [])->unique()->values();
    $assignedCount = $matches->where('is_assigned', true)->count();

    // ── Snapshots ─────────────────────────────────────────────────
    $snapshots   = $employee->signalSnapshots->sortByDesc('period');
    $latestSnap  = $snapshots->first();

    // ── Skill intelligence ────────────────────────────────────────
    $jiraSkills  = $employee->skills_from_jira['extracted_skills'] ?? [];
    $topSkills   = collect($jiraSkills)->sortByDesc('confidence')->take(8)->values();

    // ── Has any signals? ──────────────────────────────────────────
    $hasAnything = $totalTasks > 0 || $matches->count() > 0 || $snapshots->count() > 0 || $commSignals->count() > 0 || $githubSignals->count() > 0;
@endphp

    {{-- Fact-only notice --}}
    <div style="display:flex;align-items:flex-start;gap:10px;background:#f8fafc;border:1px solid var(--gray-200);border-radius:8px;padding:12px 16px;margin:20px 0 24px">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--gray-400)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span style="font-size:12.5px;color:var(--gray-600);line-height:1.6">All signals shown here are <strong>factual data only</strong> derived from connected sources — no judgements or scores are inferred. Signals are intended to provide context for resource allocation and appraisal discussions.</span>
    </div>

    @if(!$hasAnything)
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                <p>No signals available yet</p>
                <div class="empty-hint">Connect integrations (Jira, DevOps, GitHub, Slack, Teams) or run Project Matching to generate signals for this employee</div>
            </div>
        </div>
    </div>
    @else

    {{-- ===== TASK EXECUTION SIGNALS ===== --}}
    @if($totalTasks > 0)
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 14px">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        <span style="font-weight:600;font-size:14px;color:var(--gray-800)">Task Execution</span>
        <span style="font-size:12px;color:var(--gray-400)">— {{ $totalTasks }} tasks from {{ $tasksBySource->count() }} {{ Str::plural('source', $tasksBySource->count()) }}</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
        {{-- Total Tasks --}}
        <div class="card" style="margin:0">
            <div class="card-body" style="padding:16px">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:6px;font-weight:600">Total Tasks</div>
                <div style="font-size:28px;font-weight:700;color:var(--gray-800);line-height:1">{{ $totalTasks }}</div>
                <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">tasks tracked in Jira</div>
            </div>
        </div>
        {{-- Completion Rate --}}
        <div class="card" style="margin:0">
            <div class="card-body" style="padding:16px">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:6px;font-weight:600">Completed</div>
                @if($completionRate !== null)
                <div style="font-size:28px;font-weight:700;color:{{ $completionRate >= 60 ? '#16a34a' : ($completionRate >= 30 ? '#ca8a04' : 'var(--gray-700)') }};line-height:1">{{ $completionRate }}%</div>
                <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">{{ $doneTasks }} done · {{ $inProgCount }} in progress</div>
                @else
                <div style="font-size:28px;font-weight:700;color:var(--gray-400);line-height:1">—</div>
                <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">no completed tasks</div>
                @endif
            </div>
        </div>
        {{-- High Priority --}}
        <div class="card" style="margin:0">
            <div class="card-body" style="padding:16px">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:6px;font-weight:600">High Priority</div>
                <div style="font-size:28px;font-weight:700;color:var(--gray-800);line-height:1">{{ $highCount }}</div>
                <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">of {{ $totalTasks }} tasks are high/critical</div>
            </div>
        </div>
        {{-- Story Points --}}
        <div class="card" style="margin:0">
            <div class="card-body" style="padding:16px">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:6px;font-weight:600">Story Points</div>
                @if($totalSP > 0)
                <div style="font-size:28px;font-weight:700;color:var(--gray-800);line-height:1">{{ number_format($totalSP, 0) }}</div>
                <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">total · {{ number_format($doneSP, 0) }} completed</div>
                @else
                <div style="font-size:28px;font-weight:700;color:var(--gray-400);line-height:1">—</div>
                <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">no story points set</div>
                @endif
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:24px">
        <div class="card-body" style="padding:16px 20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                {{-- Status breakdown --}}
                <div>
                    <div style="font-size:12px;font-weight:600;color:var(--gray-600);margin-bottom:10px">Status Breakdown</div>
                    @foreach($statusGroups->sortByDesc(fn($g)=>$g->count()) as $status => $group)
                    @php $pct = $totalTasks > 0 ? round($group->count()/$totalTasks*100) : 0; @endphp
                    <div style="margin-bottom:8px">
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px">
                            <span style="color:var(--gray-600)">{{ $status }}</span>
                            <span style="font-weight:600;color:var(--gray-700)">{{ $group->count() }} <span style="font-weight:400;color:var(--gray-400)">({{ $pct }}%)</span></span>
                        </div>
                        <div style="height:5px;background:var(--gray-100);border-radius:3px;overflow:hidden">
                            <div style="height:100%;width:{{ $pct }}%;background:{{ $status === 'Done' ? '#16a34a' : ($status === 'To Do' ? 'var(--gray-300)' : '#2563eb') }};border-radius:3px"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                {{-- Priority + Type --}}
                <div>
                    <div style="font-size:12px;font-weight:600;color:var(--gray-600);margin-bottom:10px">Priority Distribution</div>
                    @foreach([['Critical / High', $highCount, '#dc2626'],['Medium', $medCount, '#ca8a04'],['Low / Lowest', $lowCount, '#6b7280']] as [$label, $count, $color])
                    @if($count > 0)
                    @php $pct = $totalTasks > 0 ? round($count/$totalTasks*100) : 0; @endphp
                    <div style="margin-bottom:8px">
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px">
                            <span style="color:var(--gray-600)">{{ $label }}</span>
                            <span style="font-weight:600;color:var(--gray-700)">{{ $count }} <span style="font-weight:400;color:var(--gray-400)">({{ $pct }}%)</span></span>
                        </div>
                        <div style="height:5px;background:var(--gray-100);border-radius:3px;overflow:hidden">
                            <div style="height:100%;width:{{ $pct }}%;background:{{ $color }};border-radius:3px"></div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                    @if($bugCount > 0 || $storyCount > 0 || $taskCount2 > 0)
                    <div style="font-size:12px;font-weight:600;color:var(--gray-600);margin:14px 0 8px">Task Types</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        @if($storyCount > 0)<span style="background:#eff6ff;color:#1d4ed8;border-radius:5px;padding:3px 10px;font-size:11.5px;font-weight:600">{{ $storyCount }} Stories</span>@endif
                        @if($taskCount2 > 0)<span style="background:#f0fdf4;color:#166534;border-radius:5px;padding:3px 10px;font-size:11.5px;font-weight:600">{{ $taskCount2 }} Tasks</span>@endif
                        @if($bugCount > 0)<span style="background:#fef2f2;color:#b91c1c;border-radius:5px;padding:3px 10px;font-size:11.5px;font-weight:600">{{ $bugCount }} Bugs</span>@endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    {{-- Per-source breakdown chips --}}
    @if($tasksBySource->count() > 1)
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px">
        @foreach($tasksBySource as $source => $srcTasks)
        <div style="display:flex;align-items:center;gap:7px;background:var(--gray-50);border:1px solid var(--gray-200);border-radius:8px;padding:8px 14px">
            <div style="font-weight:600;font-size:12.5px;color:var(--gray-700)">{{ $sourceLabels[$source] ?? ucfirst(str_replace('_',' ',$source)) }}</div>
            <div style="font-size:12px;color:var(--gray-400)">{{ $srcTasks->count() }} tasks</div>
            @php $srcDone = $srcTasks->where('status','Done')->count(); $srcRate = $srcTasks->count() > 0 ? round($srcDone/$srcTasks->count()*100) : 0; @endphp
            <div style="background:{{ $srcRate >= 60 ? '#dcfce7' : ($srcRate >= 30 ? '#fef9c3' : 'var(--gray-100)') }};color:{{ $srcRate >= 60 ? '#166534' : ($srcRate >= 30 ? '#854d0e' : 'var(--gray-500)') }};border-radius:4px;padding:2px 7px;font-size:11px;font-weight:600">{{ $srcRate }}% done</div>
        </div>
        @endforeach
    </div>
    @endif
    @endif

    {{-- ===== COMMUNICATION SIGNALS ===== --}}
    @if($commSignals->count() > 0)
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 14px">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span style="font-weight:600;font-size:14px;color:var(--gray-800)">Communication Signals</span>
        <span style="font-size:12px;color:var(--gray-400)">— from {{ $slackSignals->count() > 0 ? 'Slack' : '' }}{{ $slackSignals->count() > 0 && $teamsSignals->count() > 0 ? ' · ' : '' }}{{ $teamsSignals->count() > 0 ? 'Teams' : '' }}</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px">
        @foreach([
            ['Messages Sent', $sigVal($commSignals,'messages_sent_count'), 'this week', null],
            ['Active Days', $sigVal($commSignals,'active_days_count'), 'days with activity', null],
            ['Unique Collaborators', $sigVal($commSignals,'unique_collaborators_count'), 'people this week', null],
            ['After-Hours %', $sigVal($commSignals,'after_hours_message_pct'), 'messages after 6pm / before 9am', '%'],
        ] as [$label, $val, $hint, $suffix])
        <div class="card" style="margin:0">
            <div class="card-body" style="padding:16px">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:6px;font-weight:600">{{ $label }}</div>
                @if($val !== null)
                <div style="font-size:28px;font-weight:700;color:var(--gray-800);line-height:1">{{ number_format((float)$val, 0) }}{{ $suffix }}</div>
                @else
                <div style="font-size:28px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
                @endif
                <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">{{ $hint }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ===== CODE SIGNALS ===== --}}
    @if($githubSignals->count() > 0)
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 14px">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        <span style="font-weight:600;font-size:14px;color:var(--gray-800)">Code Signals</span>
        <span style="font-size:12px;color:var(--gray-400)">— from GitHub</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px">
        @foreach([
            ['Commits', $sigVal($githubSignals,'commit_count'), 'this month'],
            ['Active Days', $sigVal($githubSignals,'active_days_count'), 'days with commits'],
            ['PR Reviews', $sigVal($githubSignals,'pr_reviews_count'), 'reviews this month'],
            ['Avg Lines Added', $sigVal($githubSignals,'lines_added_avg'), 'per commit'],
        ] as [$label, $val, $hint])
        <div class="card" style="margin:0">
            <div class="card-body" style="padding:16px">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:6px;font-weight:600">{{ $label }}</div>
                @if($val !== null)
                <div style="font-size:28px;font-weight:700;color:var(--gray-800);line-height:1">{{ number_format((float)$val, 0) }}</div>
                @else
                <div style="font-size:28px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
                @endif
                <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">{{ $hint }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @php
        $fileTypes = $githubSignals->where('metric_key','file_types_touched')->sortByDesc('period')->first()?->metadata ?? [];
        $codeAreas = $githubSignals->where('metric_key','code_areas_touched')->sortByDesc('period')->first()?->metadata ?? [];
    @endphp
    @if(count($fileTypes) > 0 || count($codeAreas) > 0)
    <div class="card" style="margin-bottom:24px">
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                @if(count($fileTypes) > 0)
                <div>
                    <div style="font-size:12px;font-weight:600;color:var(--gray-600);margin-bottom:10px">File Types Touched</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach(collect($fileTypes)->sortDesc()->take(10) as $ext => $count)
                        <span style="background:var(--gray-100);color:var(--gray-700);border-radius:5px;padding:3px 10px;font-size:11.5px;font-weight:600">.{{ $ext }} ({{ $count }})</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if(count($codeAreas) > 0)
                <div>
                    <div style="font-size:12px;font-weight:600;color:var(--gray-600);margin-bottom:10px">Code Areas Touched</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach(collect($codeAreas)->sortDesc()->take(8) as $area => $count)
                        <span style="background:#eff6ff;color:#1e40af;border-radius:5px;padding:3px 10px;font-size:11.5px;font-weight:600">{{ $area }} ({{ $count }})</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- ===== SKILL INTELLIGENCE ===== --}}
    @if(count($topSkills) > 0 || ($employee->skills_from_resume && count($employee->skills_from_resume) > 0))
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 14px">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        <span style="font-weight:600;font-size:14px;color:var(--gray-800)">Skill Intelligence</span>
        <span style="font-size:12px;color:var(--gray-400)">— from {{ count($topSkills) > 0 ? 'Jira task analysis' : 'resume' }}</span>
    </div>
    <div class="card" style="margin-bottom:24px">
        <div class="card-body">
            @if(count($topSkills) > 0)
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                @foreach($topSkills as $skill)
                @php $pct = round(($skill['confidence'] ?? 0) * 100); $depth = $skill['depth'] ?? ''; @endphp
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="flex:1">
                        <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                            <span style="color:var(--gray-700);font-weight:500">{{ $skill['skill'] ?? '' }}</span>
                            <span style="color:var(--gray-500);font-size:11.5px">{{ $depth ? ucfirst($depth) : $pct . '%' }}</span>
                        </div>
                        <div style="height:6px;background:var(--gray-100);border-radius:3px;overflow:hidden">
                            <div style="height:100%;width:{{ $pct }}%;background:{{ $pct >= 70 ? '#2563eb' : ($pct >= 40 ? '#0284c7' : '#93c5fd') }};border-radius:3px"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div style="display:flex;flex-wrap:wrap;gap:7px">
                @foreach($employee->skills_from_resume as $skill)
                @if(is_string($skill))
                <span class="tag">{{ $skill }}</span>
                @endif
                @endforeach
            </div>
            <div style="font-size:12px;color:var(--gray-400);margin-top:10px">Sync Jira tasks to get confidence-scored skill analysis</div>
            @endif
        </div>
    </div>
    @endif

    {{-- ===== PROJECT FIT SIGNALS ===== --}}
    @if($matches->count() > 0)
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 14px">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        <span style="font-weight:600;font-size:14px;color:var(--gray-800)">Project Fit Signals</span>
        <span style="font-size:12px;color:var(--gray-400)">— from {{ $matches->count() }} project evaluations</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:20px">
        <div class="card" style="margin:0">
            <div class="card-body" style="padding:16px">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:6px;font-weight:600">Best Fit Score</div>
                @php $bScore = $bestMatch?->match_score ?? 0; @endphp
                <div style="font-size:28px;font-weight:700;color:{{ $bScore >= 70 ? '#16a34a' : ($bScore >= 40 ? '#ca8a04' : '#dc2626') }};line-height:1">{{ number_format($bScore, 1) }}</div>
                <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">{{ $bestMatch?->project?->name ?? '—' }}</div>
            </div>
        </div>
        <div class="card" style="margin:0">
            <div class="card-body" style="padding:16px">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:6px;font-weight:600">Avg Fit Score</div>
                <div style="font-size:28px;font-weight:700;color:var(--gray-800);line-height:1">{{ $avgScore ?? '—' }}</div>
                <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">across {{ $matches->count() }} projects evaluated</div>
            </div>
        </div>
        <div class="card" style="margin:0">
            <div class="card-body" style="padding:16px">
                <div style="font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:6px;font-weight:600">Project Assignments</div>
                <div style="font-size:28px;font-weight:700;color:var(--gray-800);line-height:1">{{ $assignedCount }}</div>
                <div style="font-size:11.5px;color:var(--gray-400);margin-top:4px">of {{ $matches->count() }} evaluated projects</div>
            </div>
        </div>
    </div>

    @if($allStrengths->count() > 0 || $allGaps->count() > 0)
    <div class="card" style="margin-bottom:24px">
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                @if($allStrengths->count() > 0)
                <div>
                    <div style="font-size:12px;font-weight:600;color:var(--gray-600);margin-bottom:10px">Consistent Strengths</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach($allStrengths->take(12) as $s)
                        <span style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:5px;padding:3px 10px;font-size:12px">{{ $s }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if($allGaps->count() > 0)
                <div>
                    <div style="font-size:12px;font-weight:600;color:var(--gray-600);margin-bottom:10px">Identified Skill Gaps</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach($allGaps->take(12) as $g)
                        <span style="background:#fff7ed;color:#9a3412;border:1px solid #fed7aa;border-radius:5px;padding:3px 10px;font-size:12px">{{ $g }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Best match explanation --}}
    @if($bestMatch?->explanation)
    <div class="card" style="margin-bottom:24px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Best Match Explanation
            </span>
            <span style="margin-left:auto;font-size:12px;color:var(--gray-400)">{{ $bestMatch->project?->name }} · Score {{ number_format($bestMatch->match_score, 1) }}</span>
        </div>
        <div class="card-body">
            <p style="margin:0;font-size:13.5px;color:var(--gray-700);line-height:1.7">{{ $bestMatch->explanation }}</p>
        </div>
    </div>
    @endif
    @endif

    {{-- ===== COMPUTED INDICES (from SignalSnapshot) ===== --}}
    @if($latestSnap)
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 14px">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        <span style="font-weight:600;font-size:14px;color:var(--gray-800)">Behavioural Indices</span>
        <span style="font-size:12px;color:var(--gray-400)">— period: {{ $latestSnap->period }}</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px">
        @foreach([
            ['Consistency', $latestSnap->consistency_index, 'Regularity of task completion and output over time'],
            ['Workload Pressure', $latestSnap->workload_pressure, 'Volume and urgency of concurrent tasks'],
            ['Context Switching', $latestSnap->context_switching_index, 'Frequency of switching between unrelated work areas'],
            ['Collaboration', $latestSnap->collaboration_density, 'Breadth and depth of cross-team activity'],
            ['Recovery Signal', $latestSnap->recovery_signal, 'Ability to recover output after high-pressure periods'],
        ] as [$label, $val, $hint])
        <div class="card" style="margin:0">
            <div class="card-body" style="padding:14px">
                <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:6px;font-weight:600">{{ $label }}</div>
                @if($val !== null)
                @php $pct = min(100, round((float)$val * 10)); @endphp
                <div style="font-size:22px;font-weight:700;color:var(--gray-800);line-height:1">{{ number_format((float)$val, 1) }}<span style="font-size:12px;color:var(--gray-400)">/10</span></div>
                <div style="height:4px;background:var(--gray-100);border-radius:2px;overflow:hidden;margin-top:6px">
                    <div style="height:100%;width:{{ $pct }}%;background:{{ $pct >= 70 ? '#2563eb' : ($pct >= 40 ? '#ca8a04' : '#e5e7eb') }};border-radius:2px"></div>
                </div>
                @else
                <div style="font-size:22px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
                @endif
                <div style="font-size:10.5px;color:var(--gray-400);margin-top:5px;line-height:1.4">{{ $hint }}</div>
            </div>
        </div>
        @endforeach
    </div>

    @if($latestSnap->ai_summary)
    <div class="card" style="margin-bottom:24px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                AI Signal Summary
            </span>
            <span style="margin-left:auto;font-size:12px;color:var(--gray-400)">{{ $latestSnap->period }}</span>
        </div>
        <div class="card-body">
            <p style="margin:0;font-size:13.5px;color:var(--gray-700);line-height:1.7">{{ $latestSnap->ai_summary }}</p>
        </div>
    </div>
    @endif

    {{-- Historical snapshots --}}
    @if($snapshots->count() > 1)
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 14px">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.08"/></svg>
        <span style="font-weight:600;font-size:14px;color:var(--gray-800)">Signal History</span>
    </div>
    <div class="card" style="margin-bottom:24px">
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Consistency</th>
                    <th>Workload</th>
                    <th>Context Sw.</th>
                    <th>Collaboration</th>
                    <th>Recovery</th>
                    <th>AI Summary</th>
                </tr>
            </thead>
            <tbody>
            @foreach($snapshots->take(12) as $snap)
            <tr>
                <td style="font-weight:500;font-size:12.5px">{{ $snap->period }}</td>
                @foreach([$snap->consistency_index,$snap->workload_pressure,$snap->context_switching_index,$snap->collaboration_density,$snap->recovery_signal] as $idx)
                <td style="font-size:13px">{{ $idx !== null ? number_format((float)$idx,1) : '—' }}</td>
                @endforeach
                <td style="font-size:12px;color:var(--gray-600);max-width:220px">{{ $snap->ai_summary ? \Illuminate\Support\Str::limit($snap->ai_summary, 80) : '—' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
    @endif

    @endif {{-- end not-empty check --}}
</div>
@endif {{-- end management role gate --}}

@endsection
