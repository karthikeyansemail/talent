@extends('layouts.app')
@section('title', 'Work Pulse')
@section('page-title', 'Work Pulse')
@section('content')
<div class="page-header">
    <h1>Work Pulse — Team Overview</h1>
    @php
        $periodLabels = ['2w'=>'Last 2 weeks','4w'=>'Last 4 weeks','1m'=>'Last month','3m'=>'Last 3 months','6m'=>'Last 6 months'];
    @endphp
    <span style="font-size:13px;color:var(--gray-400)">
        {{ $periodLabels[$period] ?? 'Last 4 weeks' }}
        @if($deptId && $departments->firstWhere('id', $deptId))
            · {{ $departments->firstWhere('id', $deptId)->name }}
        @endif
    </span>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('intelligence.dashboard') }}"
    style="display:flex;gap:10px;align-items:center;margin-bottom:20px;flex-wrap:wrap">
    <label style="font-size:12px;color:var(--gray-500);font-weight:500">Period</label>
    <select name="period" onchange="this.form.submit()" class="form-control" style="width:auto;font-size:13px;padding:6px 10px">
        @foreach($periodLabels as $val => $label)
        <option value="{{ $val }}" {{ $period === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <label style="font-size:12px;color:var(--gray-500);font-weight:500;margin-left:6px">Department</label>
    <select name="department_id" onchange="this.form.submit()" class="form-control" style="width:auto;font-size:13px;padding:6px 10px">
        <option value="">All Departments</option>
        @foreach($departments as $dept)
        <option value="{{ $dept->id }}" {{ $deptId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
        @endforeach
    </select>
</form>

{{-- Org-level stats --}}
<div class="stats-grid" style="margin-bottom:28px">
    <div class="stat-card primary">
        <div class="stat-value">{{ $orgStats['total_employees'] }}</div>
        <div class="stat-label">Total Employees</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value">{{ $orgStats['employees_with_signals'] }}</div>
        <div class="stat-label">With Task Data</div>
    </div>
    <div class="stat-card info">
        <div class="stat-value">{{ $orgStats['avg_completion_rate'] !== null ? $orgStats['avg_completion_rate'] . '%' : '—' }}</div>
        <div class="stat-label">Avg Completion Rate</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-value">{{ $orgStats['avg_cycle_time'] !== null ? $orgStats['avg_cycle_time'] . 'd' : '—' }}</div>
        <div class="stat-label">Avg Cycle Time</div>
    </div>
</div>

{{-- Section header --}}
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <h2 style="font-size:16px;font-weight:700;color:var(--gray-800);margin:0">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;margin-bottom:2px"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Team Pulse Cards
    </h2>
    <span style="font-size:12px;color:var(--gray-400)">Click any card to open an employee's full Work Pulse</span>
</div>

{{-- Employee Pulse Cards Grid --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(400px,1fr));gap:16px;align-items:start">
@forelse($employees as $emp)
@php
    $m = $emp->task_metrics;
    $engLevel = $m['engagement_level'] ?? null;
    $attrRisk = $m['attrition_risk'] ?? 'Low';

    $engColors = match($engLevel) {
        'Highly Engaged' => ['bg'=>'#f0fdf4','border'=>'#bbf7d0','badge_bg'=>'#dcfce7','badge_fg'=>'#166534','ring'=>'#16a34a'],
        'Engaged'        => ['bg'=>'#eff6ff','border'=>'#bfdbfe','badge_bg'=>'#dbeafe','badge_fg'=>'#1e40af','ring'=>'#2563eb'],
        'Moderate'       => ['bg'=>'#fefce8','border'=>'#fde68a','badge_bg'=>'#fef9c3','badge_fg'=>'#854d0e','ring'=>'#ca8a04'],
        'Disengaged'     => ['bg'=>'#fef2f2','border'=>'#fecaca','badge_bg'=>'#fee2e2','badge_fg'=>'#991b1b','ring'=>'#dc2626'],
        default          => ['bg'=>'#f9fafb','border'=>'#e5e7eb','badge_bg'=>'#f3f4f6','badge_fg'=>'#374151','ring'=>'#9ca3af'],
    };

    $riskDot = match($attrRisk) {
        'Elevated' => '#dc2626',
        'Watch'    => '#d97706',
        default    => '#16a34a',
    };

    $initials = collect(explode(' ', $emp->full_name))->map(fn($p) => strtoupper(substr($p,0,1)))->take(2)->join('');
    $rate = $m['completion_rate'];
    $rateColor = $rate === null ? '#d1d5db' : ($rate >= 70 ? '#16a34a' : ($rate >= 40 ? '#d97706' : '#dc2626'));

    // Build a short natural-language summary for this employee
    $summaryParts = [];
    if ($rate !== null) {
        $summaryParts[] = "completed tasks at {$rate}%";
    }
    if ($m['cycle_time'] !== null) {
        $summaryParts[] = "avg {$m['cycle_time']}d cycle time";
    }
    if ($m['story_points']) {
        $summaryParts[] = "{$m['story_points']} SP delivered";
    }
    if ($m['active_days'] !== null) {
        $summaryParts[] = "active {$m['active_days']} days";
    } elseif ($m['in_progress'] > 0) {
        $summaryParts[] = "{$m['in_progress']} tasks in progress";
    }
    $cardSummary = count($summaryParts) > 0 ? ucfirst(implode(', ', $summaryParts)) . '.' : null;
@endphp

<a href="{{ route('employees.show', $emp) }}#tab-signals"
   style="display:block;text-decoration:none;background:#fff;border:1px solid {{ $engColors['border'] }};border-radius:14px;padding:0;transition:box-shadow 0.15s,transform 0.1s;cursor:pointer"
   onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.08)';this.style.transform='translateY(-1px)'"
   onmouseout="this.style.boxShadow='none';this.style.transform='none'">

    {{-- Card top bar with engagement color --}}
    <div style="height:4px;background:{{ $engColors['ring'] }};border-radius:14px 14px 0 0"></div>

    <div style="padding:18px 20px">
        {{-- Header row --}}
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:14px">
            {{-- Avatar --}}
            <div style="width:42px;height:42px;border-radius:50%;background:{{ $engColors['ring'] }}22;border:2px solid {{ $engColors['ring'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <span style="font-size:13px;font-weight:700;color:{{ $engColors['ring'] }}">{{ $initials }}</span>
            </div>

            {{-- Name + dept + badges --}}
            <div style="flex:1;min-width:0">
                <div style="font-size:15px;font-weight:700;color:var(--gray-800);margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $emp->full_name }}</div>
                <div style="font-size:12px;color:var(--gray-500);margin-bottom:8px">{{ $emp->designation ?? '' }}{{ ($emp->designation && $emp->department) ? ' · ' : '' }}{{ $emp->department?->name ?? '' }}</div>

                {{-- Badges --}}
                <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                    @if($engLevel)
                    <span style="font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;background:{{ $engColors['badge_bg'] }};color:{{ $engColors['badge_fg'] }}">{{ $engLevel }}</span>
                    @endif
                    @if($attrRisk !== 'Low')
                    <span style="display:flex;align-items:center;gap:4px;font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px;background:{{ $attrRisk==='Elevated'?'#fee2e2':'#fef3c7' }};color:{{ $attrRisk==='Elevated'?'#991b1b':'#92400e' }}">
                        <span style="width:5px;height:5px;border-radius:50%;background:{{ $riskDot }};display:inline-block"></span>
                        Risk: {{ $attrRisk }}
                    </span>
                    @endif
                </div>
            </div>
        </div>

        @if($m['has_data'])
        {{-- Completion bar --}}
        @if($rate !== null)
        <div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--gray-500);margin-bottom:4px">
                <span>Task completion</span>
                <span style="font-weight:700;color:{{ $rateColor }}">{{ $rate }}%</span>
            </div>
            <div style="height:5px;background:#f3f4f6;border-radius:3px;overflow:hidden">
                <div style="height:100%;width:{{ $rate }}%;background:{{ $rateColor }};border-radius:3px;transition:width 0.5s"></div>
            </div>
        </div>
        @endif

        {{-- Natural language summary --}}
        @if($cardSummary)
        <p style="font-size:13px;color:var(--gray-600);line-height:1.6;margin:0 0 10px">{{ $cardSummary }}</p>
        @endif

        {{-- AI narrative snippet --}}
        @if($emp->aiInsight)
        <p style="font-size:12.5px;color:var(--gray-600);line-height:1.55;margin:0;font-style:italic;border-left:3px solid var(--gray-200);padding-left:10px">
            "{{ Str::limit($emp->aiInsight->management_narrative, 120) }}"
        </p>
        @else
        <p style="font-size:12px;color:var(--gray-300);margin:0;font-style:italic">No AI analysis yet — click to open Work Pulse and analyze</p>
        @endif

        @else
        {{-- No data state --}}
        <div style="text-align:center;padding:14px 0;color:var(--gray-300);font-size:12.5px">
            No task data — connect Jira / sync tasks
        </div>
        @endif
    </div>
</a>

@empty
<div class="card" style="grid-column:1/-1"><div class="card-body">
    <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        <p>No employees found</p>
        <div class="empty-hint">Add employees in the Resource Allocation section</div>
    </div>
</div></div>
@endforelse
</div>{{-- end grid --}}

@endsection
