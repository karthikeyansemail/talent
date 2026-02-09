@extends('layouts.app')
@section('title', $project->name)
@section('content')
<div class="page-header">
    <h1>{{ $project->name }}</h1>
    <div class="flex gap-10">
        @include('components.stage-badge', ['stage' => $project->status])
        <form method="POST" action="{{ route('projects.findResources', $project) }}" style="display:inline">@csrf<button type="submit" class="btn btn-primary">Find Best Resources</button></form>
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-secondary">Edit</a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">Project Details</div>
        <div class="detail-grid">
            <div class="detail-item"><label>Complexity</label><div class="value">@include('components.stage-badge', ['stage' => $project->complexity_level])</div></div>
            <div class="detail-item"><label>Created By</label><div class="value">{{ $project->creator?->name }}</div></div>
            <div class="detail-item"><label>Start Date</label><div class="value">{{ $project->start_date?->format('M d, Y') ?? 'TBD' }}</div></div>
            <div class="detail-item"><label>End Date</label><div class="value">{{ $project->end_date?->format('M d, Y') ?? 'TBD' }}</div></div>
        </div>
        @if($project->description)<div class="mt-2"><label class="text-sm text-muted">Description</label><p>{{ $project->description }}</p></div>@endif
        @if($project->domain_context)<div class="mt-1"><label class="text-sm text-muted">Domain Context</label><p>{{ $project->domain_context }}</p></div>@endif
    </div>
    <div class="card">
        <div class="card-header">Requirements</div>
        @if($project->required_skills && count($project->required_skills))
        <div class="mb-2"><label class="text-sm text-muted">Required Skills</label><div class="tags mt-1">@foreach($project->required_skills as $s)<span class="tag">{{ $s }}</span>@endforeach</div></div>
        @endif
        @if($project->required_technologies && count($project->required_technologies))
        <div><label class="text-sm text-muted">Required Technologies</label><div class="tags mt-1">@foreach($project->required_technologies as $t)<span class="tag">{{ $t }}</span>@endforeach</div></div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">Resource Matches</div>
    @if($project->resourceMatches->count())
    <table>
        <thead><tr><th>Employee</th><th>Department</th><th>Score</th><th>Strengths</th><th>Gaps</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($project->resourceMatches->sortByDesc('match_score') as $match)
        <tr>
            <td><a href="{{ route('employees.show', $match->employee_id) }}">{{ $match->employee->full_name }}</a></td>
            <td>{{ $match->employee->department?->name ?? '-' }}</td>
            <td><span class="score {{ $match->match_score >= 70 ? 'high' : ($match->match_score >= 40 ? 'medium' : 'low') }}" style="font-size:16px">{{ number_format($match->match_score, 1) }}</span></td>
            <td><div class="tags">@foreach($match->strength_areas ?? [] as $s)<span class="tag">{{ $s }}</span>@endforeach</div></td>
            <td><div class="tags">@foreach($match->skill_gaps ?? [] as $g)<span class="tag" style="background:#fecaca;color:#b91c1c">{{ $g }}</span>@endforeach</div></td>
            <td>@if($match->is_assigned)<span class="badge badge-green">Assigned</span>@else<span class="badge badge-gray">Unassigned</span>@endif</td>
            <td>
                @if($match->is_assigned)
                <form method="POST" action="{{ route('resources.unassign', [$project, $match]) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-secondary">Unassign</button></form>
                @else
                <form method="POST" action="{{ route('resources.assign', [$project, $match]) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-success">Assign</button></form>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @if($match = $project->resourceMatches->first())
        @if($match->explanation)
        <div class="ai-section mt-2"><h3>AI Explanation</h3><p>{{ $match->explanation }}</p></div>
        @endif
    @endif
    @else
    <div class="empty-state"><p>No resource matches yet. Click "Find Best Resources" to run AI matching.</p></div>
    @endif
</div>
@endsection
