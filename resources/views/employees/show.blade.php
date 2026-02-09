@extends('layouts.app')
@section('title', $employee->full_name)
@section('content')
<div class="page-header">
    <h1>{{ $employee->full_name }}</h1>
    <div class="flex gap-10">
        <form method="POST" action="{{ route('employees.syncJira', $employee) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-primary">Sync Jira</button></form>
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-secondary">Edit</a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">Profile</div>
        <div class="detail-grid">
            <div class="detail-item"><label>Email</label><div class="value">{{ $employee->email }}</div></div>
            <div class="detail-item"><label>Department</label><div class="value">{{ $employee->department?->name ?? 'N/A' }}</div></div>
            <div class="detail-item"><label>Designation</label><div class="value">{{ $employee->designation ?? 'N/A' }}</div></div>
            <div class="detail-item"><label>Status</label><div class="value">{{ $employee->is_active ? 'Active' : 'Inactive' }}</div></div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">Skill Profile</div>
        @if($employee->skills_from_jira && isset($employee->skills_from_jira['extracted_skills']))
        @foreach($employee->skills_from_jira['extracted_skills'] as $skill)
        <div class="skill-bar">
            <span class="label">{{ $skill['skill'] ?? 'N/A' }}</span>
            <div class="bar"><div class="fill" style="width:{{ ($skill['confidence'] ?? 0) * 100 }}%"></div></div>
            <span class="percent">{{ ucfirst($skill['depth'] ?? '') }}</span>
        </div>
        @endforeach
        @else
        <div class="empty-state"><p>No skill data. Sync Jira tasks to extract skills.</p></div>
        @endif
    </div>
</div>

@if($employee->jiraTasks->count())
<div class="card">
    <div class="card-header">Jira Tasks ({{ $employee->jiraTasks->count() }})</div>
    <table>
        <thead><tr><th>Key</th><th>Summary</th><th>Type</th><th>Status</th><th>Priority</th><th>Points</th></tr></thead>
        <tbody>
        @foreach($employee->jiraTasks->take(20) as $task)
        <tr>
            <td class="font-bold">{{ $task->jira_task_key }}</td>
            <td>{{ \Illuminate\Support\Str::limit($task->summary, 60) }}</td>
            <td><span class="badge badge-gray">{{ $task->task_type }}</span></td>
            <td>{{ $task->status }}</td>
            <td>{{ $task->priority }}</td>
            <td>{{ $task->story_points ?? '-' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if($employee->resourceMatches->count())
<div class="card">
    <div class="card-header">Project Matches</div>
    <table>
        <thead><tr><th>Project</th><th>Score</th><th>Strengths</th><th>Gaps</th><th>Assigned</th></tr></thead>
        <tbody>
        @foreach($employee->resourceMatches as $match)
        <tr>
            <td><a href="{{ route('projects.show', $match->project_id) }}">{{ $match->project->name }}</a></td>
            <td><span class="score {{ $match->match_score >= 70 ? 'high' : ($match->match_score >= 40 ? 'medium' : 'low') }}" style="font-size:16px">{{ number_format($match->match_score, 1) }}</span></td>
            <td><div class="tags">@foreach($match->strength_areas ?? [] as $s)<span class="tag">{{ $s }}</span>@endforeach</div></td>
            <td><div class="tags">@foreach($match->skill_gaps ?? [] as $g)<span class="tag" style="background:#fecaca;color:#b91c1c">{{ $g }}</span>@endforeach</div></td>
            <td>@include('components.stage-badge', ['stage' => $match->is_assigned ? 'hired' : 'applied'])</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
