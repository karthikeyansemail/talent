{{-- Resource matches table partial. Expects $project with resourceMatches loaded --}}
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
        <td><div class="tags">@foreach($match->skill_gaps ?? [] as $g)<span class="tag" style="background:#fecaca;color:#b91c1c;border-color:#fecaca">{{ $g }}</span>@endforeach</div></td>
        <td>@if($match->is_assigned)<span class="badge badge-green">Assigned</span>@else<span class="badge badge-gray">Unassigned</span>@endif</td>
        <td>
            @if($match->is_assigned)
            <form method="POST" action="{{ route('resources.unassign', [$project, $match]) }}" style="display:inline">@csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-secondary">Unassign</button>
            </form>
            @else
            <form method="POST" action="{{ route('resources.assign', [$project, $match]) }}" style="display:inline">@csrf
                <button type="submit" class="btn btn-sm btn-success">Assign</button>
            </form>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@php $topMatch = $project->resourceMatches->sortByDesc('match_score')->first(); @endphp
@if($topMatch && $topMatch->explanation)
<div class="card-body">
    <div class="ai-section">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            AI Analysis
        </h3>
        <p style="font-size:13.5px;color:var(--gray-600);line-height:1.6">{{ $topMatch->explanation }}</p>
    </div>
</div>
@endif
@else
<div class="empty-state">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    <p>No resource matches yet</p>
    <div class="empty-hint">Click "Find Best Resources" to run AI matching</div>
</div>
@endif
