@extends('layouts.app')
@section('title', 'Student Progress')
@section('page-title', 'Student Progress')
@section('content')
<div class="page-header">
    <h1>Student Progress Tracking</h1>
    <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
        Per-student improvement charts across drives, skill heatmap, placement readiness scoring.
    </p>
</div>
<div class="card">
    <table>
        <thead>
            <tr><th>Student</th><th>Course / Batch</th><th>Tests Taken</th><th>Avg Score</th><th>Last Attempt</th></tr>
        </thead>
        <tbody>
            @forelse($students as $s)
                @php $stats = $attemptStats[$s->id] ?? null; @endphp
                <tr>
                    <td>{{ $s->first_name }} {{ $s->last_name }}</td>
                    <td class="text-sm text-muted">{{ $s->course ?? '—' }} {{ $s->batch_year ? '· ' . $s->batch_year : '' }}</td>
                    <td>{{ $stats?->attempts ?? 0 }}</td>
                    <td>
                        @if($stats?->avg_score)
                            <span class="score {{ $stats->avg_score >= 70 ? 'high' : ($stats->avg_score >= 40 ? 'medium' : 'low') }}">{{ number_format($stats->avg_score, 1) }}%</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-sm text-muted">{{ $stats?->last_attempt ? \Carbon\Carbon::parse($stats->last_attempt)->diffForHumans() : 'Never' }}</td>
                </tr>
            @empty
            <tr><td colspan="5"><div class="empty-state"><p>No students yet</p><p class="empty-hint">Add students via Placement → Students, then run a drive to start tracking.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<p style="margin-top:16px;color:var(--text-muted);font-size:12px">
    Improvement charts + skill heatmap per student arrive in Commit E.
</p>
@endsection
