{{-- Resource matches table partial. Expects $project with resourceMatches loaded --}}
@if($project->resourceMatches->count())
<table>
    <thead>
        <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Score</th>
            <th>Strengths</th>
            <th>Gaps</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    @foreach($project->resourceMatches->sortByDesc('match_score') as $match)
    {{-- Main row --}}
    <tr @if($match->explanation) data-expand-target="expand-match-{{ $match->id }}" @endif>
        <td>
            <div style="display:flex;align-items:center;gap:8px">
                @if($match->explanation)
                <button type="button"
                    class="expand-toggle"
                    data-target="expand-match-{{ $match->id }}"
                    title="View AI explanation"
                    style="background:none;border:none;cursor:pointer;padding:2px;color:var(--gray-400);display:flex;align-items:center;flex-shrink:0">
                    <svg class="expand-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
                @endif
                <a href="{{ route('employees.show', $match->employee_id) }}" class="name-link">{{ $match->employee->full_name }}</a>
            </div>
        </td>
        <td>{{ $match->employee->department?->name ?? '-' }}</td>
        <td>
            <span class="score {{ $match->match_score >= 70 ? 'high' : ($match->match_score >= 40 ? 'medium' : 'low') }}" style="font-size:16px">
                {{ number_format($match->match_score, 1) }}
            </span>
        </td>
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
    {{-- Expandable explanation row --}}
    @if($match->explanation)
    <tr id="expand-match-{{ $match->id }}" style="display:none">
        <td colspan="7" style="padding:0;background:var(--gray-50);border-top:none">
            <div style="padding:14px 18px 14px 40px;border-left:3px solid var(--primary);margin:0 0 2px">
                <div style="display:flex;align-items:center;gap:7px;margin-bottom:8px">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    <span style="font-size:12.5px;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:.04em">AI Match Explanation — {{ $match->employee->full_name }}</span>
                </div>
                <p style="margin:0;font-size:13.5px;color:var(--gray-600);line-height:1.65">{{ $match->explanation }}</p>
            </div>
        </td>
    </tr>
    @endif
    @endforeach
    </tbody>
</table>
@else
<div class="empty-state">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    <p>No resource matches yet</p>
    <div class="empty-hint">Click "Find Best Resources" to run AI matching</div>
</div>
@endif
