@extends('layouts.app')
@section('title', 'Student Progress')
@section('page-title', 'Student Progress')
@section('content')
<div class="page-header">
    <h1>Student Progress Tracking</h1>
    <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
        Cohort overview of placement readiness. Click a student to see their improvement chart + skill heatmap.
    </p>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:14px 18px">
        <form method="GET" action="{{ route('placement.progress.index') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group" style="margin:0;flex:1;min-width:200px">
                <label>Search</label>
                <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Name, email, enrollment #">
            </div>
            <div class="form-group" style="margin:0;min-width:150px">
                <label>Course</label>
                <select name="course" class="form-control">
                    <option value="">All</option>
                    @foreach($courses as $c)<option value="{{ $c }}" {{ request('course') === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;min-width:120px">
                <label>Batch</label>
                <select name="batch" class="form-control">
                    <option value="">All</option>
                    @foreach($batches as $b)<option value="{{ $b }}" {{ request('batch') == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if(request('q') || request('course') || request('batch'))
                <a href="{{ route('placement.progress.index') }}" class="btn btn-secondary">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Course / Batch</th>
                <th>Tests</th>
                <th>Cleared</th>
                <th>Avg Score</th>
                <th>Trend (last 6)</th>
                <th>Last Attempt</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $s)
                @php
                    $stats = $attemptStats[$s->id] ?? null;
                    $sparkline = $recentAttempts[$s->id] ?? [];
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('placement.progress.show', $s) }}" class="name-link">
                            <strong>{{ $s->first_name }} {{ $s->last_name }}</strong>
                        </a>
                        <div class="text-sm text-muted">{{ $s->enrollment_number ?? $s->email }}</div>
                    </td>
                    <td class="text-sm text-muted">{{ $s->course ?? '—' }} {{ $s->batch_year ? '· ' . $s->batch_year : '' }}</td>
                    <td>{{ $stats?->attempts ?? 0 }}</td>
                    <td>
                        @if($stats?->attempts)
                            {{ (int) $stats->cleared_count }} <span class="text-muted text-sm">/ {{ $stats->attempts }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($stats?->avg_score)
                            <span class="score {{ $stats->avg_score >= 70 ? 'high' : ($stats->avg_score >= 40 ? 'medium' : 'low') }}">{{ number_format($stats->avg_score, 1) }}%</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if(count($sparkline) >= 2)
                            @include('placement.progress._sparkline', ['points' => $sparkline])
                        @elseif(count($sparkline) === 1)
                            <span class="text-sm text-muted">{{ number_format($sparkline[0], 1) }}%</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-sm text-muted">{{ $stats?->last_attempt ? \Carbon\Carbon::parse($stats->last_attempt)->diffForHumans() : 'Never' }}</td>
                </tr>
            @empty
            <tr><td colspan="7"><div class="empty-state"><p>No students found</p><p class="empty-hint">Add students via Placement → Students, then run a drive to start tracking progress.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($students->hasPages())
    <div style="margin-top:16px">{{ $students->links() }}</div>
@endif
@endsection
