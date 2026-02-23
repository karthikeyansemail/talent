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
    // ── Raw task data ──────────────────────────────────────────────
    $allTasks      = $employee->tasks;
    $tasksBySource = $allTasks->groupBy('source_type');
    $totalTasks    = $allTasks->count();
    $sourceLabels  = ['jira'=>'Jira','zoho_projects'=>'Zoho Projects','devops_boards'=>'DevOps Boards','github_projects'=>'GitHub Projects'];

    // Status groups
    $statusGroups = $allTasks->groupBy('status');
    $doneTasks    = $statusGroups->get('Done', collect())->count();
    $inProgTasks  = collect();
    foreach(['In Progress','In Review','In Development','Review','Active'] as $_s) {
        $inProgTasks = $inProgTasks->merge($statusGroups->get($_s, collect()));
    }
    $inProgCount    = $inProgTasks->count();
    $priorityGroups = $allTasks->groupBy('priority');
    $highCount = $priorityGroups->get('High', collect())->count()
               + $priorityGroups->get('Highest', collect())->count()
               + $priorityGroups->get('Critical', collect())->count();
    $medCount  = $priorityGroups->get('Medium', collect())->count();
    $lowCount  = $priorityGroups->get('Low', collect())->count()
               + $priorityGroups->get('Lowest', collect())->count();
    $typeGroups = $allTasks->groupBy('task_type');
    $bugCount   = $typeGroups->get('Bug', collect())->count();
    $storyCount = $typeGroups->get('Story', collect())->count() + $typeGroups->get('UserStory', collect())->count();
    $taskCount2 = $typeGroups->get('Task', collect())->count();

    // ── Signal data ────────────────────────────────────────────────
    $employeeSignals = $employee->signals ?? collect();
    $slackSignals    = $employeeSignals->where('source_type', 'slack');
    $teamsSignals    = $employeeSignals->where('source_type', 'teams');
    $githubSignals   = $employeeSignals->where('source_type', 'github');
    $commSignals     = $slackSignals->merge($teamsSignals);

    // ── Sprint sheet data ──────────────────────────────────────────
    $sprintSheets = $employee->sprintSheets->sortBy('start_date');

    // ── Insights from controller ───────────────────────────────────
    $ti  = $signalInsights['task'] ?? [];
    $ci  = $signalInsights['comm'] ?? [];
    $gi  = $signalInsights['code'] ?? [];
    $obs = $signalInsights['observations'] ?? [];

    // ── Jira skills ────────────────────────────────────────────────
    $jiraSkills = $employee->skills_from_jira['extracted_skills'] ?? [];
    $topSkills  = collect($jiraSkills)->sortByDesc('confidence')->take(8)->values();

    // ── At a Glance: directional chips ─────────────────────────────
    // Throughput: completion rate period-over-period
    $throughputDir = null; $throughputHint = '';
    if (($ti['completion_rate'] ?? null) !== null && ($ti['completion_rate_prev'] ?? null) !== null) {
        $diff = $ti['completion_rate'] - $ti['completion_rate_prev'];
        $throughputDir  = $diff >= 10 ? '↑' : ($diff <= -10 ? '↓' : '→');
        $throughputHint = abs((int)$diff) . 'pts vs ' . ($ti['prev_period'] ?? 'prev');
    } elseif (($ti['completion_rate'] ?? null) !== null) {
        $throughputDir  = '→';
        $throughputHint = $ti['completion_rate'] . '% this period';
    }

    // Quality: bug resolution rate (≥70% = good, <40% = low)
    $qualityDir = null; $qualityHint = '';
    if (($ti['bug_resolution_rate'] ?? null) !== null) {
        $bRR = $ti['bug_resolution_rate'];
        $qualityDir  = $bRR >= 70 ? '↑' : ($bRR < 40 ? '↓' : '→');
        $qualityHint = $bRR . '% of bugs resolved';
    } elseif ($bugCount === 0 && $totalTasks > 0) {
        $qualityDir  = '→';
        $qualityHint = 'no bugs in task set';
    }

    // Flow: cycle time trend (shorter = improving = ↑)
    $flowDir = null; $flowHint = '';
    if (($ti['cycle_time_avg'] ?? null) !== null && ($ti['cycle_time_prev'] ?? null) !== null && $ti['cycle_time_prev'] > 0) {
        $ctTrend = (int) round(($ti['cycle_time_avg'] - $ti['cycle_time_prev']) / $ti['cycle_time_prev'] * 100);
        $flowDir  = $ctTrend <= -15 ? '↑' : ($ctTrend >= 15 ? '↓' : '→');
        $flowHint = 'avg ' . $ti['cycle_time_avg'] . 'd/task';
    } elseif (($ti['cycle_time_avg'] ?? null) !== null) {
        $flowDir  = '→';
        $flowHint = 'avg ' . $ti['cycle_time_avg'] . ' days/task';
    }

    // Communication: trend from first comm metric with trend_pct
    $commDir = null; $commHint = '';
    foreach($ci as $cKey => $cIns) {
        if (($cIns['trend_pct'] ?? null) !== null) {
            $t = $cIns['trend_pct'];
            $commDir  = $t >= 15 ? '↑' : ($t <= -15 ? '↓' : '→');
            $commHint = ($cIns['source'] ?? 'Comm') . ' activity';
            break;
        }
    }

    // Code: trend from first code metric with trend_pct
    $codeDir = null; $codeHint = '';
    foreach($gi as $gKey => $gIns) {
        if (($gIns['trend_pct'] ?? null) !== null) {
            $t = $gIns['trend_pct'];
            $codeDir  = $t >= 15 ? '↑' : ($t <= -15 ? '↓' : '→');
            $codeHint = 'code contribution';
            break;
        }
    }

    $glanceChips = array_filter([
        $throughputDir !== null ? ['Throughput', $throughputDir, $throughputHint] : null,
        $qualityDir    !== null ? ['Quality',    $qualityDir,    $qualityHint]    : null,
        $flowDir       !== null ? ['Flow',       $flowDir,       $flowHint]       : null,
        $commDir       !== null ? ['Communication', $commDir,    $commHint]       : null,
        $codeDir       !== null ? ['Code Activity',  $codeDir,   $codeHint]       : null,
    ]);

    $hasAnything = $totalTasks > 0 || $commSignals->count() > 0 || $githubSignals->count() > 0
               || count($topSkills) > 0 || ($employee->skills_from_resume && count($employee->skills_from_resume) > 0)
               || $sprintSheets->count() > 0;
@endphp

    {{-- ===== FACT-ONLY NOTICE ===== --}}
    <div style="display:flex;align-items:flex-start;gap:10px;background:#f8fafc;border:1px solid var(--gray-200);border-radius:8px;padding:12px 16px;margin:20px 0 20px">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--gray-400)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span style="font-size:12.5px;color:var(--gray-600);line-height:1.6">Signals show <strong>factual patterns</strong> from connected tools — no character judgements are made. All trends are period-over-period data. <strong>Humans decide meaning.</strong></span>
    </div>

    @if(!$hasAnything)
    <div class="card"><div class="card-body">
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <p>No signals available yet</p>
            <div class="empty-hint">Connect integrations (Jira, DevOps, GitHub, Slack, Teams) and sync tasks to generate signals for this employee</div>
        </div>
    </div></div>
    @else

    {{-- ===== AT A GLANCE ===== --}}
    @if(count($glanceChips) > 0)
    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px">
        @foreach($glanceChips as [$chipLabel, $chipDir, $chipHint])
        <div style="display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--gray-200);border-radius:10px;padding:10px 16px;min-width:140px">
            <span style="font-size:20px;line-height:1;color:var(--gray-700)">{{ $chipDir }}</span>
            <div>
                <div style="font-size:12px;font-weight:700;color:var(--gray-700)">{{ $chipLabel }}</div>
                <div style="font-size:11px;color:var(--gray-400)">{{ $chipHint }}</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ===== OBSERVED CHANGES ===== --}}
    @if(count($obs) > 0)
    <div class="card" style="margin-bottom:20px;border-left:3px solid var(--primary)">
        <div class="card-body" style="padding:14px 18px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                <span style="font-weight:700;font-size:13px;color:var(--gray-800)">Observed Changes</span>
                <span style="font-size:11px;color:var(--gray-400)">period-over-period factual observations</span>
            </div>
            <div style="display:flex;flex-direction:column;gap:7px">
                @foreach($obs as $observation)
                <div style="display:flex;align-items:flex-start;gap:8px;padding:7px 10px;background:var(--gray-50);border-radius:6px">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="var(--gray-400)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span style="font-size:12.5px;color:var(--gray-700);line-height:1.5">{{ $observation }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ===== TASK-BASED SECTIONS ===== --}}
    @if($totalTasks > 0)
    @php
        $taskSourceStr = $tasksBySource->keys()->map(fn($s) => $sourceLabels[$s] ?? ucfirst(str_replace('_',' ',$s)))->join(', ');
        $completionTrend = ($ti['completion_rate'] !== null && $ti['completion_rate_prev'] !== null)
            ? (int)($ti['completion_rate'] - $ti['completion_rate_prev']) : null;
        $spTrend = ($ti['velocity_sp'] !== null && $ti['velocity_sp_prev'] !== null && $ti['velocity_sp_prev'] > 0)
            ? (int) round(($ti['velocity_sp'] - $ti['velocity_sp_prev']) / $ti['velocity_sp_prev'] * 100)
            : null;
        $ctTrendPct = ($ti['cycle_time_avg'] !== null && $ti['cycle_time_prev'] !== null && $ti['cycle_time_prev'] > 0)
            ? (int) round(($ti['cycle_time_avg'] - $ti['cycle_time_prev']) / $ti['cycle_time_prev'] * 100)
            : null;
    @endphp

    {{-- ── 1. Throughput & Velocity ── --}}
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
        <span style="font-weight:600;font-size:13.5px;color:var(--gray-800)">Throughput &amp; Velocity</span>
        <span style="font-size:12px;color:var(--gray-400)">— from {{ $taskSourceStr }} · {{ $ti['period'] ?? '' }}</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
        {{-- Completion rate --}}
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">Completion Rate</div>
            @if($ti['completion_rate'] !== null)
            <div style="display:flex;align-items:baseline;flex-wrap:wrap;gap:3px">
                <span style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $ti['completion_rate'] }}%</span>
                @if($completionTrend !== null)
                @php $a = $completionTrend > 0 ? '↑' : '↓'; @endphp
                <span style="font-size:11px;font-weight:600;padding:2px 6px;border-radius:10px;background:var(--gray-100);color:var(--gray-600)">{{ $a }} {{ abs($completionTrend) }}pts</span>
                @endif
            </div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">{{ $ti['done_current'] ?? 0 }} done · {{ $inProgCount }} in progress</div>
            @if($ti['completion_rate_prev'] !== null)<div style="font-size:11px;color:var(--gray-400)">Prev period: {{ $ti['completion_rate_prev'] }}%</div>@endif
            @else
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">no completed tasks</div>
            @endif
        </div></div>
        {{-- Tasks this period --}}
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">Tasks This Period</div>
            <div style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $ti['total_current'] ?? $totalTasks }}</div>
            @if(($ti['total_prev'] ?? null) !== null)
            @php $tDiff = ($ti['total_current'] ?? 0) - $ti['total_prev']; $tA = $tDiff > 0 ? '↑' : ($tDiff < 0 ? '↓' : '→'); @endphp
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">{{ $tA }} {{ abs($tDiff) }} vs prev period ({{ $ti['total_prev'] }})</div>
            @endif
        </div></div>
        {{-- SP velocity --}}
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">SP Velocity</div>
            @if($ti['velocity_sp'] !== null)
            <div style="display:flex;align-items:baseline;flex-wrap:wrap;gap:3px">
                <span style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ number_format($ti['velocity_sp'], 0) }}</span>
                @if($spTrend !== null)
                @php $a = $spTrend > 0 ? '↑' : '↓'; @endphp
                <span style="font-size:11px;font-weight:600;padding:2px 6px;border-radius:10px;background:var(--gray-100);color:var(--gray-600)">{{ $a }} {{ abs($spTrend) }}%</span>
                @endif
            </div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">SP completed this period</div>
            @if($ti['velocity_sp_prev'] !== null)<div style="font-size:11px;color:var(--gray-400)">Prev: {{ number_format($ti['velocity_sp_prev'], 0) }} SP</div>@endif
            @else
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">no story points tracked</div>
            @endif
        </div></div>
        {{-- High-priority completion rate --}}
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">High-Priority Done</div>
            @if($ti['high_priority_done_rate'] !== null)
            <div style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $ti['high_priority_done_rate'] }}%</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">of high/critical tasks completed</div>
            @elseif($highCount === 0)
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">no high-priority tasks</div>
            @else
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            @endif
        </div></div>
    </div>

    {{-- ── 2. Work Quality & Focus ── --}}
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <span style="font-weight:600;font-size:13.5px;color:var(--gray-800)">Work Quality &amp; Focus</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px">
        {{-- Bug resolution rate --}}
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">Bug Resolution Rate</div>
            @if($ti['bug_resolution_rate'] !== null)
            <div style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $ti['bug_resolution_rate'] }}%</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">{{ $allTasks->where('task_type','Bug')->where('status','Done')->count() }} of {{ $bugCount }} bugs resolved</div>
            @else
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">no bugs in task set</div>
            @endif
        </div></div>
        {{-- Bug share of work --}}
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">Bug Share of Work</div>
            @if(($ti['bug_pct'] ?? null) !== null)
            <div style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $ti['bug_pct'] }}%</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">{{ $bugCount }} bugs of {{ $totalTasks }} total tasks</div>
            @else
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            @endif
        </div></div>
        {{-- Spillover --}}
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">Task Spillover</div>
            @if($ti['prev_period'] !== null)
            <div style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $ti['spillover'] ?? 0 }}</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">tasks from {{ $ti['prev_period'] }} still open</div>
            @else
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">need 2+ periods of data</div>
            @endif
        </div></div>
        {{-- Work type mix --}}
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">Work Type Mix</div>
            @if($bugCount > 0 || $storyCount > 0 || $taskCount2 > 0)
            <div style="display:flex;flex-direction:column;gap:5px;margin-top:4px">
                @if($storyCount > 0)<div style="display:flex;justify-content:space-between;font-size:11.5px"><span style="color:var(--gray-600)">Stories</span><span style="font-weight:600;background:#eff6ff;color:#1d4ed8;padding:1px 7px;border-radius:4px">{{ $storyCount }}</span></div>@endif
                @if($taskCount2 > 0)<div style="display:flex;justify-content:space-between;font-size:11.5px"><span style="color:var(--gray-600)">Tasks</span><span style="font-weight:600;background:#f0fdf4;color:#166534;padding:1px 7px;border-radius:4px">{{ $taskCount2 }}</span></div>@endif
                @if($bugCount > 0)<div style="display:flex;justify-content:space-between;font-size:11.5px"><span style="color:var(--gray-600)">Bugs</span><span style="font-weight:600;background:#fef2f2;color:#b91c1c;padding:1px 7px;border-radius:4px">{{ $bugCount }}</span></div>@endif
            </div>
            @else
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            @endif
        </div></div>
    </div>
    {{-- Status + Priority breakdown --}}
    <div class="card" style="margin-bottom:20px">
        <div class="card-body" style="padding:14px 18px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                <div>
                    <div style="font-size:11.5px;font-weight:600;color:var(--gray-600);margin-bottom:8px">Status Breakdown</div>
                    @foreach($statusGroups->sortByDesc(fn($g)=>$g->count())->take(5) as $status => $group)
                    @php $pct = $totalTasks > 0 ? round($group->count()/$totalTasks*100) : 0; @endphp
                    <div style="margin-bottom:7px">
                        <div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:2px">
                            <span style="color:var(--gray-600)">{{ $status }}</span>
                            <span style="font-weight:600;color:var(--gray-700)">{{ $group->count() }} <span style="font-weight:400;color:var(--gray-400)">({{ $pct }}%)</span></span>
                        </div>
                        <div style="height:4px;background:var(--gray-100);border-radius:3px;overflow:hidden">
                            <div style="height:100%;width:{{ $pct }}%;background:{{ $status==='Done'?'#16a34a':($status==='To Do'?'var(--gray-300)':'#2563eb') }};border-radius:3px"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div>
                    <div style="font-size:11.5px;font-weight:600;color:var(--gray-600);margin-bottom:8px">Priority Distribution</div>
                    @foreach([['Critical / High',$highCount,'#dc2626'],['Medium',$medCount,'#ca8a04'],['Low / Lowest',$lowCount,'#6b7280']] as [$lbl,$cnt,$col])
                    @if($cnt>0)
                    @php $pct=$totalTasks>0?round($cnt/$totalTasks*100):0; @endphp
                    <div style="margin-bottom:7px">
                        <div style="display:flex;justify-content:space-between;font-size:11.5px;margin-bottom:2px">
                            <span style="color:var(--gray-600)">{{ $lbl }}</span>
                            <span style="font-weight:600;color:var(--gray-700)">{{ $cnt }} <span style="font-weight:400;color:var(--gray-400)">({{ $pct }}%)</span></span>
                        </div>
                        <div style="height:4px;background:var(--gray-100);border-radius:3px;overflow:hidden">
                            <div style="height:100%;width:{{ $pct }}%;background:{{ $col }};border-radius:3px"></div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ── 3. Responsiveness & Flow ── --}}
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        <span style="font-weight:600;font-size:13.5px;color:var(--gray-800)">Responsiveness &amp; Flow</span>
        <span style="font-size:11.5px;color:var(--gray-400)">— how quickly work moves through</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:24px">
        {{-- Avg cycle time --}}
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">Avg Cycle Time</div>
            @if($ti['cycle_time_avg'] !== null)
            <div style="display:flex;align-items:baseline;flex-wrap:wrap;gap:3px">
                <span style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $ti['cycle_time_avg'] }}</span>
                <span style="font-size:13px;color:var(--gray-500);margin-left:2px">days</span>
                @if($ctTrendPct !== null)
                {{-- For cycle time: lower is faster, so negative trend = ↑ (improving) --}}
                @php $a = $ctTrendPct < 0 ? '↑' : '↓'; @endphp
                <span style="font-size:11px;font-weight:600;padding:2px 6px;border-radius:10px;background:var(--gray-100);color:var(--gray-600)">{{ $a }} {{ abs($ctTrendPct) }}%</span>
                @endif
            </div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">avg from task creation to done</div>
            @if($ti['cycle_time_prev'] !== null)<div style="font-size:11px;color:var(--gray-400)">Prev period: {{ $ti['cycle_time_prev'] }} days</div>@endif
            @else
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">no completed tasks with timestamps</div>
            @endif
        </div></div>
        {{-- Aging open tasks --}}
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">Aging Open Tasks</div>
            <div style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $ti['aging_tasks'] ?? 0 }}</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">open tasks older than 30 days</div>
        </div></div>
        {{-- In progress now --}}
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">In Progress Now</div>
            <div style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $inProgCount }}</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">tasks currently in progress / review</div>
        </div></div>
    </div>
    @endif {{-- end totalTasks > 0 --}}

    {{-- ===== COMMUNICATION & COLLABORATION ===== --}}
    @if($commSignals->count() > 0)
    @php
        $commSrc = ($slackSignals->count() > 0 ? 'Slack' : '') . ($slackSignals->count() > 0 && $teamsSignals->count() > 0 ? ' · ' : '') . ($teamsSignals->count() > 0 ? 'Teams' : '');
        $commMetrics = [
            ['metric_key' => 'messages_sent_count',        'label' => 'Messages Sent',         'hint' => 'total messages this period'],
            ['metric_key' => 'active_days_count',          'label' => 'Active Days',            'hint' => 'days with communication activity'],
            ['metric_key' => 'unique_collaborators_count', 'label' => 'Unique Collaborators',   'hint' => 'people interacted with this period'],
            ['metric_key' => 'after_hours_message_pct',   'label' => 'After-Hours Messages',   'hint' => '% messages before 9am / after 6pm'],
            ['metric_key' => 'calls_count',                'label' => 'Calls',                  'hint' => 'call sessions this period'],
            ['metric_key' => 'meetings_attended_count',    'label' => 'Meetings Attended',      'hint' => 'meetings this period'],
        ];
        $commDisplay = collect($commMetrics)->filter(fn($m) => ($ci[$m['metric_key']]['value'] ?? null) !== null)->values();
    @endphp
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span style="font-weight:600;font-size:13.5px;color:var(--gray-800)">Communication &amp; Collaboration</span>
        <span style="font-size:12px;color:var(--gray-400)">— from {{ $commSrc }}</span>
    </div>
    @if($commDisplay->count() > 0)
    <div style="display:grid;grid-template-columns:repeat({{ min(3,$commDisplay->count()) }},1fr);gap:12px;margin-bottom:24px">
        @foreach($commDisplay->take(6) as $m)
        @php
            $ins = $ci[$m['metric_key']] ?? [];
            $val = $ins['value'] ?? null;
            $prv = $ins['prev'] ?? null;
            $tnd = $ins['trend_pct'] ?? null;
            $isSuffix = str_ends_with($m['metric_key'],'_pct') ? '%' : '';
            $dispVal = ($val !== null && fmod((float)$val,1.0)!=0.0 && !$isSuffix) ? round((float)$val,1) : (int)$val;
        @endphp
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">{{ $m['label'] }}</div>
            @if($val !== null)
            <div style="display:flex;align-items:baseline;flex-wrap:wrap;gap:3px;margin-bottom:2px">
                <span style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $dispVal }}{{ $isSuffix }}</span>
                @if($tnd !== null)
                @php $a = $tnd > 0 ? '↑' : '↓'; @endphp
                <span style="font-size:11px;font-weight:600;padding:2px 5px;border-radius:10px;background:var(--gray-100);color:var(--gray-600)">{{ $a }} {{ abs($tnd) }}%</span>
                @endif
            </div>
            @if($prv !== null)<div style="font-size:11px;color:var(--gray-400)">Prev: {{ fmod((float)$prv,1.0)!=0.0?round((float)$prv,1):(int)$prv }}{{ $isSuffix }}</div>@endif
            @else
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            @endif
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px">{{ $m['hint'] }}</div>
        </div></div>
        @endforeach
    </div>
    @endif
    @endif

    {{-- ===== CODE CONTRIBUTION ===== --}}
    @if($githubSignals->count() > 0)
    @php
        $codeMetrics = [
            ['metric_key'=>'commit_count',    'label'=>'Commits',          'hint'=>'commits this period'],
            ['metric_key'=>'active_days_count','label'=>'Active Coding Days','hint'=>'days with commits'],
            ['metric_key'=>'pr_reviews_count', 'label'=>'PR Reviews',       'hint'=>'pull request reviews'],
            ['metric_key'=>'lines_added_avg',  'label'=>'Avg Lines Added',  'hint'=>'lines added per commit'],
        ];
        $fileTypes = $githubSignals->where('metric_key','file_types_touched')->sortByDesc('period')->first()?->metadata ?? [];
        $codeAreas = $githubSignals->where('metric_key','code_areas_touched')->sortByDesc('period')->first()?->metadata ?? [];
    @endphp
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        <span style="font-weight:600;font-size:13.5px;color:var(--gray-800)">Code Contribution</span>
        <span style="font-size:12px;color:var(--gray-400)">— from GitHub</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:{{ (count($fileTypes)+count($codeAreas))>0?'14':'24' }}px">
        @foreach($codeMetrics as $m)
        @php $ins=$gi[$m['metric_key']]??[]; $val=$ins['value']??null; $prv=$ins['prev']??null; $tnd=$ins['trend_pct']??null; @endphp
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">{{ $m['label'] }}</div>
            @if($val!==null)
            <div style="display:flex;align-items:baseline;flex-wrap:wrap;gap:3px;margin-bottom:2px">
                <span style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ number_format((float)$val,0) }}</span>
                @if($tnd!==null)@php $a=$tnd>0?'↑':'↓'; @endphp<span style="font-size:11px;font-weight:600;padding:2px 5px;border-radius:10px;background:var(--gray-100);color:var(--gray-600)">{{ $a }} {{ abs($tnd) }}%</span>@endif
            </div>
            @if($prv!==null)<div style="font-size:11px;color:var(--gray-400)">Prev: {{ number_format((float)$prv,0) }}</div>@endif
            @else
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            @endif
            <div style="font-size:11px;color:var(--gray-400);margin-top:2px">{{ $m['hint'] }}</div>
        </div></div>
        @endforeach
    </div>
    @if(count($fileTypes)>0||count($codeAreas)>0)
    <div class="card" style="margin-bottom:24px"><div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
            @if(count($fileTypes)>0)
            <div>
                <div style="font-size:11.5px;font-weight:600;color:var(--gray-600);margin-bottom:8px">File Types Touched</div>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    @foreach(collect($fileTypes)->sortDesc()->take(12) as $ext=>$cnt)
                    <span style="background:var(--gray-100);color:var(--gray-700);border-radius:5px;padding:3px 9px;font-size:11.5px;font-weight:600">.{{ $ext }} <span style="font-weight:400;color:var(--gray-400)">({{ $cnt }})</span></span>
                    @endforeach
                </div>
            </div>
            @endif
            @if(count($codeAreas)>0)
            <div>
                <div style="font-size:11.5px;font-weight:600;color:var(--gray-600);margin-bottom:8px">Code Areas</div>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    @foreach(collect($codeAreas)->sortDesc()->take(10) as $area=>$cnt)
                    <span style="background:#eff6ff;color:#1e40af;border-radius:5px;padding:3px 9px;font-size:11.5px;font-weight:600">{{ $area }} <span style="font-weight:400;color:#3b82f6">({{ $cnt }})</span></span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div></div>
    @else
    <div style="margin-bottom:24px"></div>
    @endif
    @endif

    {{-- ===== SKILL INTELLIGENCE ===== --}}
    @if(count($topSkills) > 0 || ($employee->skills_from_resume && count($employee->skills_from_resume) > 0))
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        <span style="font-weight:600;font-size:13.5px;color:var(--gray-800)">Skill Intelligence</span>
        <span style="font-size:12px;color:var(--gray-400)">— observed from {{ count($topSkills) > 0 ? 'actual task work' : 'resume' }}</span>
    </div>
    <div class="card" style="margin-bottom:24px"><div class="card-body">
        @if(count($topSkills) > 0)
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            @foreach($topSkills as $skill)
            @php $pct = round(($skill['confidence'] ?? 0) * 100); $depth = $skill['depth'] ?? ''; @endphp
            <div>
                <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px">
                    <span style="color:var(--gray-700);font-weight:500">{{ $skill['skill'] ?? '' }}</span>
                    <span style="color:var(--gray-500);font-size:11.5px">{{ $depth ? ucfirst($depth) : $pct . '%' }}</span>
                </div>
                <div style="height:5px;background:var(--gray-100);border-radius:3px;overflow:hidden">
                    <div style="height:100%;width:{{ $pct }}%;background:{{ $pct>=70?'#2563eb':($pct>=40?'#0284c7':'#93c5fd') }};border-radius:3px"></div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div style="display:flex;flex-wrap:wrap;gap:7px">
            @foreach($employee->skills_from_resume as $skill)
            @if(is_string($skill))<span class="tag">{{ $skill }}</span>@endif
            @endforeach
        </div>
        <div style="font-size:12px;color:var(--gray-400);margin-top:10px">Sync Jira tasks to get confidence-scored skill analysis from real work</div>
        @endif
    </div></div>
    @endif

    {{-- ===== SPRINT / PROGRAM MANAGER SIGNALS ===== --}}
    @if($sprintSheets->count() > 0)
    @php
        $latestSprint = $sprintSheets->last();
        $sprintAccuracies = $sprintSheets->map(fn($s) => $s->planned_points > 0
            ? round($s->completed_points / $s->planned_points * 100) : null)->filter()->values();
        $avgPlanAccuracy = $sprintAccuracies->count() > 0 ? round($sprintAccuracies->avg()) : null;
        $velocityTrend   = $sprintSheets->count() >= 2
            ? (int)($sprintSheets->last()->completed_points - $sprintSheets->first()->completed_points)
            : null;
    @endphp
    <div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <span style="font-weight:600;font-size:13.5px;color:var(--gray-800)">Sprint &amp; Program Manager Signals</span>
        <span style="font-size:12px;color:var(--gray-400)">— {{ $sprintSheets->count() }} sprints</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px">
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">Avg Planning Accuracy</div>
            <div style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $avgPlanAccuracy ?? '—' }}{{ $avgPlanAccuracy!==null?'%':'' }}</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">planned SP delivered on average</div>
        </div></div>
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">Latest Sprint Delivery</div>
            @if($latestSprint)
            @php $delRate = $latestSprint->planned_points > 0 ? round($latestSprint->completed_points / $latestSprint->planned_points * 100) : null; @endphp
            <div style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $delRate ?? '—' }}{{ $delRate!==null?'%':'' }}</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">{{ $latestSprint->sprint_name }} · {{ $latestSprint->completed_points }}/{{ $latestSprint->planned_points }} SP</div>
            @endif
        </div></div>
        <div class="card" style="margin:0"><div class="card-body" style="padding:14px">
            <div style="font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--gray-500);margin-bottom:5px;font-weight:600">Velocity Trend</div>
            @if($velocityTrend !== null)
            @php $a = $velocityTrend > 0 ? '↑' : ($velocityTrend < 0 ? '↓' : '→'); @endphp
            <div style="font-size:26px;font-weight:700;color:var(--gray-800);line-height:1">{{ $a }} {{ abs($velocityTrend) }}</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">SP change from first to latest sprint</div>
            @else
            <div style="font-size:26px;font-weight:700;color:var(--gray-300);line-height:1">—</div>
            <div style="font-size:11px;color:var(--gray-400);margin-top:3px">need 2+ sprints</div>
            @endif
        </div></div>
    </div>
    <div class="card" style="margin-bottom:24px">
        <table>
            <thead><tr><th>Sprint</th><th>Period</th><th>Planned SP</th><th>Delivered SP</th><th>Accuracy</th><th>Tasks</th></tr></thead>
            <tbody>
            @foreach($sprintSheets as $s)
            @php $acc = $s->planned_points > 0 ? round($s->completed_points / $s->planned_points * 100) : null; @endphp
            <tr>
                <td style="font-weight:500;font-size:12.5px">{{ $s->sprint_name }}</td>
                <td style="font-size:12px;color:var(--gray-500)">{{ \Carbon\Carbon::parse($s->start_date)->format('M d') }} – {{ \Carbon\Carbon::parse($s->end_date)->format('M d') }}</td>
                <td style="font-size:13px">{{ $s->planned_points }}</td>
                <td style="font-size:13px">{{ $s->completed_points }}</td>
                <td style="font-size:13px;font-weight:600">{{ $acc !== null ? $acc.'%' : '—' }}</td>
                <td style="font-size:12px;color:var(--gray-500)">{{ $s->tasks_completed }}/{{ $s->tasks_planned }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @endif {{-- end not-empty check --}}
</div>
@endif {{-- end management role gate --}}

@endsection
