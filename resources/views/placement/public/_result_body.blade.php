{{-- Result body shown after grading completes --}}
@php
    $pct = (float) ($attempt->score_pct ?? 0);
    $passed = (bool) $attempt->passed;
    $bigColor = $passed ? '#16a34a' : ($pct >= 50 ? '#f59e0b' : '#dc2626');
    $icon = $passed ? '✓' : '✗';
    $headline = $passed ? 'Great work — you cleared the test!' : 'Test submitted — keep practicing!';
@endphp

<div style="display:inline-flex;width:80px;height:80px;border-radius:50%;background:{{ $bigColor }}22;color:{{ $bigColor }};align-items:center;justify-content:center;font-size:36px;font-weight:700;margin-bottom:16px">
    {{ $icon }}
</div>

<h2 style="margin:0 0 8px;font-size:20px;color:var(--text-strong)">{{ $headline }}</h2>

<div style="margin:18px 0;font-size:48px;font-weight:800;color:{{ $bigColor }}">
    {{ number_format($pct, 1) }}%
</div>

<div style="font-size:14px;color:var(--text-muted);margin-bottom:24px">
    {{ number_format((float) $attempt->score_obtained, 1) }} / {{ $attempt->total_marks_available }} marks
    · Pass mark: {{ $attempt->test->passing_score_pct }}%
</div>

<div style="display:flex;justify-content:center;gap:24px;font-size:13px;color:var(--text-muted);margin-bottom:8px">
    <div>
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Time Taken</div>
        <div style="font-size:16px;color:var(--text);margin-top:2px;font-weight:600">
            {{ $attempt->time_taken_seconds ? round($attempt->time_taken_seconds / 60, 1) . ' min' : '—' }}
        </div>
    </div>
    <div>
        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.04em;font-weight:600">Result</div>
        <div style="font-size:16px;margin-top:2px;font-weight:600;color:{{ $passed ? '#16a34a' : '#dc2626' }}">
            {{ $passed ? 'Cleared' : 'Did Not Clear' }}
        </div>
    </div>
</div>
