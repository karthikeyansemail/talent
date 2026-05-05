@extends('layouts.app')
@section('title', $test->title)
@section('page-title', $test->title)
@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1>{{ $test->title }}</h1>
        <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
            {{ $test->drive->company_name }} — {{ $test->drive->role_title }} · @include('components.stage-badge', ['stage' => $test->status])
        </p>
    </div>
    <a href="{{ route('placement.tests.edit', $test) }}" class="btn btn-primary">Edit Test</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px">
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Questions</div>
        <div style="margin-top:4px;font-size:24px;font-weight:700;color:var(--text-strong)">{{ $test->questions->count() }}</div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Time Limit</div>
        <div style="margin-top:4px;font-size:24px;font-weight:700;color:var(--text-strong)">{{ $test->time_limit_minutes }} min</div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Pass Score</div>
        <div style="margin-top:4px;font-size:24px;font-weight:700;color:var(--text-strong)">{{ $test->passing_score_pct }}%</div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Attempts</div>
        <div style="margin-top:4px;font-size:24px;font-weight:700;color:var(--text-strong)">{{ $test->attempts->count() }}</div>
    </div></div>
</div>

@if($test->status === 'published')
<div style="padding:14px 18px;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:10px;margin-bottom:20px">
    <div style="font-weight:600;color:var(--text-strong);margin-bottom:6px">Public Student URL</div>
    <code style="font-size:13px;color:var(--text);word-break:break-all">{{ url('placement/test/' . $test->public_token) }}</code>
</div>
@endif

@if($test->attempts->count())
<div class="card">
    <div class="card-header"><span class="card-header-icon">Student Attempts</span></div>
    <table>
        <thead><tr><th>Student</th><th>Submitted</th><th>Time</th><th>Score</th><th>Result</th></tr></thead>
        <tbody>
            @foreach($test->attempts->sortByDesc('score_pct') as $a)
            <tr>
                <td><strong>{{ $a->student_name }}</strong><div class="text-sm text-muted">{{ $a->student_email }}</div></td>
                <td class="text-sm text-muted">{{ $a->submitted_at?->format('d M Y H:i') ?? 'In progress' }}</td>
                <td class="text-sm text-muted">{{ $a->time_taken_seconds ? round($a->time_taken_seconds / 60) . ' min' : '—' }}</td>
                <td>
                    @if($a->score_pct !== null)
                        <span class="score {{ $a->score_pct >= 70 ? 'high' : ($a->score_pct >= 40 ? 'medium' : 'low') }}">{{ number_format($a->score_pct, 1) }}%</span>
                    @else <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($a->passed === true)<span class="badge badge-green">Cleared</span>
                    @elseif($a->passed === false)<span class="badge badge-red">Did Not Clear</span>
                    @else<span class="text-muted">{{ $a->grading_status }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
