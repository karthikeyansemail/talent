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
            @if($employee->skills_from_jira && isset($employee->skills_from_jira['extracted_skills']))
            @foreach($employee->skills_from_jira['extracted_skills'] as $skill)
            <div class="skill-bar">
                <span class="label">{{ $skill['skill'] ?? 'N/A' }}</span>
                <div class="bar"><div class="fill" style="width:{{ ($skill['confidence'] ?? 0) * 100 }}%"></div></div>
                <span class="percent">{{ ucfirst($skill['depth'] ?? '') }}</span>
            </div>
            @endforeach
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

{{-- Jira Tasks --}}
@if($employee->jiraTasks->count())
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            Jira Tasks ({{ $employee->jiraTasks->count() }})
        </span>
    </div>
    <table>
        <thead><tr><th>Key</th><th>Summary</th><th>Type</th><th>Status</th><th>Priority</th><th>Points</th></tr></thead>
        <tbody>
        @foreach($employee->jiraTasks->take(20) as $task)
        <tr>
            <td><span class="font-semibold" style="color:var(--primary)">{{ $task->jira_task_key }}</span></td>
            <td>{{ \Illuminate\Support\Str::limit($task->summary, 60) }}</td>
            <td><span class="badge badge-gray">{{ $task->task_type }}</span></td>
            <td><span class="badge badge-blue">{{ $task->status }}</span></td>
            <td>{{ $task->priority }}</td>
            <td>{{ $task->story_points ?? '-' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Project Matches --}}
@if($employee->resourceMatches->count())
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Project Matches
        </span>
    </div>
    <table>
        <thead><tr><th>Project</th><th>Score</th><th>Strengths</th><th>Gaps</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($employee->resourceMatches as $match)
        <tr>
            <td><a href="{{ route('projects.show', $match->project_id) }}">{{ $match->project->name }}</a></td>
            <td><span class="score {{ $match->match_score >= 70 ? 'high' : ($match->match_score >= 40 ? 'medium' : 'low') }}" style="font-size:16px">{{ number_format($match->match_score, 1) }}</span></td>
            <td><div class="tags">@foreach($match->strength_areas ?? [] as $s)<span class="tag">{{ $s }}</span>@endforeach</div></td>
            <td><div class="tags">@foreach($match->skill_gaps ?? [] as $g)<span class="tag" style="background:#fecaca;color:#b91c1c;border-color:#fecaca">{{ $g }}</span>@endforeach</div></td>
            <td>@if($match->is_assigned)<span class="badge badge-green">Assigned</span>@else<span class="badge badge-gray">Unassigned</span>@endif</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
