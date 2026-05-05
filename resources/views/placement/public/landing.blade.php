@extends('placement.public._layout')
@section('title', $test->title)
@section('content')

<div class="public-header">
    <h1>{{ $test->title }}</h1>
    <div class="sub">{{ $test->drive->company_name }} · {{ $test->drive->role_title }}</div>
</div>

<div class="card">
    <div class="card-header"><span class="card-header-icon">Test Information</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:18px">
            <div>
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Questions</div>
                <div style="font-size:20px;font-weight:700;margin-top:2px">{{ $test->questions->count() }}</div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Time Limit</div>
                <div style="font-size:20px;font-weight:700;margin-top:2px">{{ $test->time_limit_minutes }} min</div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Pass Score</div>
                <div style="font-size:20px;font-weight:700;margin-top:2px">{{ $test->passing_score_pct }}%</div>
            </div>
        </div>

        @if($test->instructions)
            <div style="margin-bottom:18px;padding:14px 16px;background:var(--bg-muted);border-radius:8px;font-size:14px;line-height:1.6;color:var(--text)">
                {{ $test->instructions }}
            </div>
        @endif

        <div style="padding:12px 16px;background:#fff7ed;border-left:3px solid #f97316;border-radius:6px;font-size:13px;color:var(--text);margin-bottom:20px">
            <strong>Important:</strong> Once you start, the timer will run continuously even if you close the browser.
            You can attempt this test only <strong>once</strong> per email address. Make sure your details are correct.
        </div>

        <h3 style="font-size:15px;color:var(--text-strong);margin:0 0 12px">Your Details</h3>
        <form method="POST" action="{{ route('placement.public.start', $test->public_token) }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="student_name" class="form-control" required value="{{ old('student_name') }}">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="student_email" class="form-control" required value="{{ old('student_email') }}">
                </div>
            </div>
            <div class="form-group">
                <label>Enrollment Number (optional)</label>
                <input type="text" name="student_enrollment" class="form-control" value="{{ old('student_enrollment') }}" placeholder="e.g. CSE_2026_001">
            </div>
            <button type="submit" class="btn btn-primary" style="font-size:15px;padding:12px 24px">
                Start Test →
            </button>
        </form>
    </div>
</div>

<div style="margin-top:20px;text-align:center;color:var(--text-subtle);font-size:12px">
    Powered by Nalam Pulse Placement Training
</div>
@endsection
