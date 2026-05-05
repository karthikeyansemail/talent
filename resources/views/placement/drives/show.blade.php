@extends('layouts.app')
@section('title', $drive->company_name)
@section('page-title', 'Drive: ' . $drive->company_name)
@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1>{{ $drive->company_name }}</h1>
        <p style="margin:6px 0 0;color:var(--text-muted);font-size:14px">{{ $drive->role_title }}</p>
    </div>
    <div class="flex gap-10">
        <a href="{{ route('placement.drives.edit', $drive) }}" class="btn btn-secondary">Edit</a>
        <form method="POST" action="{{ route('placement.drives.destroy', $drive) }}" style="margin:0">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this drive and all its tests?')">Delete</button>
        </form>
    </div>
</div>

{{-- Summary cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px">
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Status</div>
        <div style="margin-top:6px">@include('components.stage-badge', ['stage' => $drive->status])</div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Drive Date</div>
        <div style="margin-top:4px;font-size:18px;font-weight:600;color:var(--text-strong)">{{ $drive->drive_date?->format('d M Y') ?? '—' }}</div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Package</div>
        <div style="margin-top:4px;font-size:18px;font-weight:600;color:var(--text-strong)">{{ $drive->package_lpa ? '₹' . $drive->package_lpa . ' LPA' : '—' }}</div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Min CGPA</div>
        <div style="margin-top:4px;font-size:18px;font-weight:600;color:var(--text-strong)">{{ $drive->min_cgpa ?? '—' }}</div>
    </div></div>
    <div class="card" style="margin:0"><div class="card-body" style="padding:14px 18px">
        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Attempts</div>
        <div style="margin-top:4px;font-size:18px;font-weight:600;color:var(--text-strong)">{{ $drive->attempts->count() }}</div>
    </div></div>
</div>

{{-- Eligibility + skills --}}
<div class="card">
    <div class="card-header"><span class="card-header-icon">Eligibility & Requirements</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
            <div>
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:6px">Eligible Courses</div>
                <div>
                    @forelse($drive->eligible_courses ?? [] as $c)<span class="tag">{{ $c }}</span>@empty<span class="text-muted">All</span>@endforelse
                </div>
            </div>
            <div>
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:6px">Eligible Batch Years</div>
                <div>
                    @forelse($drive->eligible_batch_years ?? [] as $y)<span class="tag">{{ $y }}</span>@empty<span class="text-muted">All</span>@endforelse
                </div>
            </div>
        </div>
        <div style="margin-top:18px">
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:6px">Required Skills</div>
            <div>@forelse($drive->required_skills ?? [] as $s)<span class="tag">{{ $s }}</span>@empty<span class="text-muted">—</span>@endforelse</div>
        </div>
        @if($drive->description)
        <div style="margin-top:18px">
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600;margin-bottom:6px">Description</div>
            <p style="margin:0;line-height:1.6;color:var(--text)">{{ $drive->description }}</p>
        </div>
        @endif
    </div>
</div>

{{-- Aptitude tests for this drive --}}
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-header-icon">Aptitude Tests</span>
        @if(auth()->user()->isSuperAdmin() || (auth()->user()->currentOrganization()?->canUseModule('aptitude_tests') ?? false))
            <span class="text-muted" style="font-size:12px">Test creation UI ships in next commit</span>
        @endif
    </div>
    <table>
        <thead><tr><th>Test</th><th>Time Limit</th><th>Pass Score</th><th>Status</th><th>Attempts</th></tr></thead>
        <tbody>
            @forelse($drive->tests as $t)
            <tr>
                <td>{{ $t->title }}</td>
                <td>{{ $t->time_limit_minutes }} min</td>
                <td>{{ $t->passing_score_pct }}%</td>
                <td>@include('components.stage-badge', ['stage' => $t->status])</td>
                <td>{{ $t->attempts->count() }}</td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="empty-state"><p>No tests created yet</p><p class="empty-hint">AI test generation coming in Commit C.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Test attempts --}}
@if($drive->attempts->count())
<div class="card">
    <div class="card-header"><span class="card-header-icon">Student Attempts</span></div>
    <table>
        <thead><tr><th>Student</th><th>Submitted</th><th>Score</th><th>Result</th></tr></thead>
        <tbody>
            @foreach($drive->attempts->sortByDesc('score_pct') as $a)
            <tr>
                <td>
                    <strong>{{ $a->student_name }}</strong>
                    <div class="text-sm text-muted">{{ $a->student_email }}</div>
                </td>
                <td class="text-sm text-muted">{{ $a->submitted_at?->format('d M Y H:i') ?? 'In progress' }}</td>
                <td>
                    @if($a->score_pct !== null)
                        <span class="score {{ $a->score_pct >= 70 ? 'high' : ($a->score_pct >= 40 ? 'medium' : 'low') }}">{{ number_format($a->score_pct, 1) }}%</span>
                    @else <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($a->passed === true)<span class="badge badge-green">Cleared</span>
                    @elseif($a->passed === false)<span class="badge badge-red">Did Not Clear</span>
                    @else<span class="text-muted">Pending</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
