@extends('layouts.app')
@section('title', $student->first_name . ' ' . $student->last_name)
@section('page-title', 'Student Progress')
@section('content')

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1>{{ $student->first_name }} {{ $student->last_name }}</h1>
        <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
            {{ $student->enrollment_number ?? $student->email }}
            @if($student->department) · <strong style="color:var(--text)">{{ $student->department->name }}</strong> @endif
            @if($student->course) · {{ $student->course }} @endif
            @if($student->batch_year) · Batch {{ $student->batch_year }} @endif
        </p>
    </div>
    <a href="{{ route('placement.progress.index') }}" class="btn btn-secondary">← Back to Cohort</a>
</div>

{{-- Summary KPIs --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px">
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Tests Taken</div>
        <div style="margin-top:4px;font-size:24px;font-weight:700;color:var(--text-strong)">{{ $totalAttempts }}</div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Drives Cleared</div>
        <div style="margin-top:4px;font-size:24px;font-weight:700;color:var(--text-strong)">{{ $clearedCount }}<span style="font-size:14px;color:var(--text-muted);font-weight:500"> / {{ $completedCount }}</span></div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Avg Score</div>
        <div style="margin-top:4px;font-size:24px;font-weight:700;color:{{ $avgScore !== null ? ($avgScore >= 70 ? '#16a34a' : ($avgScore >= 40 ? '#f59e0b' : '#dc2626')) : 'var(--text-muted)' }}">{{ $avgScore !== null ? number_format($avgScore, 1) . '%' : '—' }}</div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Improvement</div>
        @if($improvementDelta !== null)
            <div style="margin-top:4px;font-size:24px;font-weight:700;color:{{ $improvementDelta > 0 ? '#16a34a' : ($improvementDelta < 0 ? '#dc2626' : 'var(--text-muted)') }}">
                {{ $improvementDelta > 0 ? '+' : '' }}{{ number_format($improvementDelta, 1) }}<span style="font-size:14px;font-weight:500">pp</span>
            </div>
            <div style="font-size:11px;color:var(--text-subtle);margin-top:2px">last 3 vs first 3</div>
        @else
            <div style="margin-top:4px;font-size:14px;color:var(--text-muted)">Need 4+ attempts</div>
        @endif
    </div></div>
</div>

{{-- Improvement chart --}}
@if(count($chartPoints) >= 2)
<div class="card">
    <div class="card-header"><span class="card-header-icon">Score Trend</span></div>
    <div class="card-body">
        @php
            $w = 720; $h = 220; $padL = 36; $padR = 16; $padT = 12; $padB = 28;
            $n = count($chartPoints);
            $stepX = $n > 1 ? ($w - $padL - $padR) / ($n - 1) : 0;
            $coords = [];
            foreach ($chartPoints as $i => $p) {
                $x = $padL + $i * $stepX;
                $y = $h - $padB - (($p['score'] / 100) * ($h - $padT - $padB));
                $coords[] = ['x' => round($x, 1), 'y' => round($y, 1), 'p' => $p];
            }
            $polyline = implode(' ', array_map(fn($c) => $c['x'] . ',' . $c['y'], $coords));
        @endphp
        <svg width="100%" viewBox="0 0 {{ $w }} {{ $h }}" style="max-width:100%;display:block">
            {{-- Y-axis grid lines at 0/25/50/75/100 --}}
            @foreach([0, 25, 50, 75, 100] as $tick)
                @php $y = $h - $padB - (($tick / 100) * ($h - $padT - $padB)); @endphp
                <line x1="{{ $padL }}" y1="{{ $y }}" x2="{{ $w - $padR }}" y2="{{ $y }}" stroke="var(--border)" stroke-width="1" stroke-dasharray="{{ $tick == 0 || $tick == 100 ? 'none' : '3,3' }}"/>
                <text x="{{ $padL - 6 }}" y="{{ $y + 4 }}" text-anchor="end" font-size="10" fill="var(--text-muted)">{{ $tick }}%</text>
            @endforeach

            {{-- Line --}}
            <polyline points="{{ $polyline }}" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linejoin="round"/>

            {{-- Points + labels --}}
            @foreach($coords as $c)
                @php $color = $c['p']['score'] >= 70 ? '#16a34a' : ($c['p']['score'] >= 40 ? '#f59e0b' : '#dc2626'); @endphp
                <circle cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="4" fill="{{ $color }}" stroke="var(--bg-card)" stroke-width="2">
                    <title>{{ $c['p']['drive'] }}: {{ number_format($c['p']['score'], 1) }}% on {{ $c['p']['date'] }}</title>
                </circle>
                <text x="{{ $c['x'] }}" y="{{ $c['y'] - 10 }}" text-anchor="middle" font-size="11" font-weight="600" fill="var(--text)">{{ round($c['p']['score']) }}</text>
                <text x="{{ $c['x'] }}" y="{{ $h - 10 }}" text-anchor="middle" font-size="10" fill="var(--text-muted)">{{ $c['p']['date'] }}</text>
            @endforeach
        </svg>
        <div style="margin-top:8px;font-size:12px;color:var(--text-muted);text-align:center">
            Hover over points to see the drive. Green = cleared band, amber = borderline, red = needs improvement.
        </div>
    </div>
</div>
@endif

{{-- Skill heatmap --}}
@if(!empty($topicHeatmap))
<div class="card">
    <div class="card-header"><span class="card-header-icon">Skill Heatmap by Topic</span></div>
    <div class="card-body">
        <p style="margin:0 0 14px;color:var(--text-muted);font-size:13px">
            Average understanding score across all questions per topic. MCQ counts 100% if correct, 0% if wrong; descriptive answers use the AI's understanding score.
        </p>
        <div style="display:grid;gap:10px">
            @foreach($topicHeatmap as $row)
                @php $color = $row['avg'] >= 70 ? '#16a34a' : ($row['avg'] >= 40 ? '#f59e0b' : '#dc2626'); @endphp
                <div style="display:flex;align-items:center;gap:14px">
                    <div style="width:160px;font-size:13px;font-weight:500;color:var(--text)">{{ $row['topic'] }}</div>
                    <div style="flex:1;height:24px;background:var(--bg-muted);border-radius:6px;position:relative;overflow:hidden">
                        <div style="position:absolute;top:0;left:0;bottom:0;width:{{ $row['avg'] }}%;background:{{ $color }};opacity:0.85;border-radius:6px;display:flex;align-items:center;justify-content:flex-end;padding-right:8px;color:#fff;font-size:11px;font-weight:600">{{ $row['avg'] >= 12 ? round($row['avg']) . '%' : '' }}</div>
                    </div>
                    <div style="width:60px;text-align:right;font-size:11px;color:var(--text-muted)">{{ $row['count'] }} q's</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Attempt history --}}
<div class="card">
    <div class="card-header"><span class="card-header-icon">Attempt History</span></div>
    <table>
        <thead>
            <tr><th>Drive</th><th>Test</th><th>Date</th><th>Time</th><th>Score</th><th>Result</th></tr>
        </thead>
        <tbody>
            @forelse($attempts as $a)
            <tr>
                <td>{{ $a->drive->company_name }}<div class="text-sm text-muted">{{ $a->drive->role_title }}</div></td>
                <td class="text-sm">{{ $a->test->title }}</td>
                <td class="text-sm text-muted">{{ $a->submitted_at?->format('d M Y H:i') ?? 'In progress' }}</td>
                <td class="text-sm text-muted">{{ $a->time_taken_seconds ? round($a->time_taken_seconds / 60) . ' min' : '—' }}</td>
                <td>
                    @if($a->score_pct !== null)
                        <span class="score {{ $a->score_pct >= 70 ? 'high' : ($a->score_pct >= 40 ? 'medium' : 'low') }}">{{ number_format($a->score_pct, 1) }}%</span>
                    @else
                        <span class="text-muted">{{ $a->grading_status }}</span>
                    @endif
                </td>
                <td>
                    @if($a->passed === true)<span class="badge badge-green">Cleared</span>
                    @elseif($a->passed === false)<span class="badge badge-red">Did Not Clear</span>
                    @else<span class="text-muted">—</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6"><div class="empty-state"><p>No attempts yet</p><p class="empty-hint">This student hasn't taken any aptitude tests yet.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
