@extends('layouts.app')
@section('title', 'Work Pulse')
@section('page-title', 'Work Pulse')
@section('content')
<div class="page-header">
    <h1>Work Pulse — Team Overview</h1>
    @php
        $periodLabels = ['2w'=>'Last 2 weeks','4w'=>'Last 4 weeks','1m'=>'Last month','3m'=>'Last 3 months','6m'=>'Last 6 months'];
    @endphp
</div>

<div class="filter-bar">
    <form method="GET" action="{{ route('intelligence.dashboard') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:nowrap;width:100%">
        <select name="period" onchange="this.form.submit()" class="form-control" style="width:160px;flex:0 0 160px;height:40px;padding:0 28px 0 10px;font-size:13px;box-sizing:border-box;background-position:right 8px center;background-size:14px">
            @foreach($periodLabels as $val => $label)
            <option value="{{ $val }}" {{ $period === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="department_id" onchange="this.form.submit()" class="form-control" style="width:180px;flex:0 0 180px;height:40px;padding:0 28px 0 10px;font-size:13px;box-sizing:border-box;background-position:right 8px center;background-size:14px">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
            <option value="{{ $dept->id }}" {{ $deptId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="dash-stats">
        <div class="dash-stat" style="cursor:default">
            <div class="dash-stat__icon dash-stat__icon--primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="dash-stat__info">
                <span class="dash-stat__value">{{ $orgStats['total_employees'] }}</span>
                <span class="dash-stat__label">Total Employees</span>
            </div>
        </div>
        <div class="dash-stat" style="cursor:default">
            <div class="dash-stat__icon dash-stat__icon--success">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            </div>
            <div class="dash-stat__info">
                <span class="dash-stat__value">{{ $orgStats['employees_with_signals'] }}</span>
                <span class="dash-stat__label">With Task Data</span>
            </div>
        </div>
        <div class="dash-stat" style="cursor:default">
            <div class="dash-stat__icon dash-stat__icon--info">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div class="dash-stat__info">
                <span class="dash-stat__value">{{ $orgStats['avg_completion_rate'] !== null ? $orgStats['avg_completion_rate'] . '%' : '—' }}</span>
                <span class="dash-stat__label">Avg Completion</span>
            </div>
        </div>
        <div class="dash-stat" style="cursor:default">
            <div class="dash-stat__icon dash-stat__icon--warning">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="dash-stat__info">
                <span class="dash-stat__value">{{ $orgStats['avg_cycle_time'] !== null ? $orgStats['avg_cycle_time'] . 'd' : '—' }}</span>
                <span class="dash-stat__label">Avg Cycle Time</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <table style="table-layout:fixed">
        <colgroup>
            <col style="width:25%">
            <col style="width:15%">
            <col style="width:17%">
            <col style="width:12%">
            <col style="width:10%">
            <col style="width:11%">
            <col style="width:10%">
        </colgroup>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Engagement</th>
                <th>Completion</th>
                <th>Cycle Time</th>
                <th>Story Pts</th>
                <th>Risk</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($employees as $emp)
        @php
            $m = $emp->task_metrics;
            $engLevel = $m['engagement_level'] ?? null;
            $attrRisk = $m['attrition_risk'] ?? 'Low';
            $rate = $m['completion_rate'];
            $rateColor = $rate === null ? 'var(--gray-400)' : ($rate >= 70 ? 'var(--success)' : ($rate >= 40 ? 'var(--warning)' : 'var(--danger)'));
        @endphp
        <tr data-expand-target="expand-pulse-{{ $emp->id }}">
            <td>
                <div style="display:flex;align-items:center;gap:8px">
                    <button type="button" class="expand-toggle" data-target="expand-pulse-{{ $emp->id }}" title="View AI insight" style="background:none;border:none;cursor:pointer;padding:2px;color:var(--gray-400);display:flex;align-items:center;flex-shrink:0">
                        <svg class="expand-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                    <div>
                        <a href="{{ route('employees.show', $emp) }}#tab-signals" class="name-link" style="display:block">{{ $emp->full_name }}</a>
                        <span style="font-size:12px;color:var(--gray-400)">{{ $emp->designation ?? '' }}{{ ($emp->designation && $emp->department) ? ' · ' : '' }}{{ $emp->department?->name ?? '' }}</span>
                    </div>
                </div>
            </td>
            <td>
                @if($engLevel)
                @php
                    $engBadge = match($engLevel) {
                        'Highly Engaged' => ['bg'=>'var(--success-light)','fg'=>'var(--success)','border'=>'var(--success-border)'],
                        'Engaged'        => ['bg'=>'var(--info-light)','fg'=>'var(--info)','border'=>'var(--info-border)'],
                        'Moderate'       => ['bg'=>'var(--warning-light)','fg'=>'var(--warning)','border'=>'var(--warning-border)'],
                        'Disengaged'     => ['bg'=>'var(--danger-light)','fg'=>'var(--danger)','border'=>'var(--danger-border)'],
                        default          => ['bg'=>'var(--gray-100)','fg'=>'var(--gray-600)','border'=>'var(--gray-200)'],
                    };
                @endphp
                <span class="tag" style="background:{{ $engBadge['bg'] }};color:{{ $engBadge['fg'] }};border:1px solid {{ $engBadge['border'] }};font-weight:600">{{ $engLevel }}</span>
                @else
                <span class="text-muted">—</span>
                @endif
            </td>
            <td>
                @if($rate !== null)
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:60px;height:5px;background:var(--gray-100);border-radius:3px;overflow:hidden">
                        <div style="height:100%;width:{{ $rate }}%;background:{{ $rateColor }};border-radius:3px"></div>
                    </div>
                    <span style="font-size:13px;font-weight:600;color:{{ $rateColor }}">{{ $rate }}%</span>
                </div>
                @else
                <span class="text-muted">—</span>
                @endif
            </td>
            <td>
                @if($m['cycle_time'] !== null)
                <span style="font-weight:500">{{ $m['cycle_time'] }}d</span>
                @else
                <span class="text-muted">—</span>
                @endif
            </td>
            <td>
                @if($m['story_points'])
                <span style="font-weight:500">{{ $m['story_points'] }}</span>
                @else
                <span class="text-muted">—</span>
                @endif
            </td>
            <td>
                @if($attrRisk !== 'Low')
                @php
                    $riskStyle = $attrRisk === 'Elevated'
                        ? ['bg'=>'var(--danger-light)','fg'=>'var(--danger)','border'=>'var(--danger-border)','dot'=>'var(--danger)']
                        : ['bg'=>'var(--warning-light)','fg'=>'var(--warning)','border'=>'var(--warning-border)','dot'=>'var(--warning)'];
                @endphp
                <span class="tag" style="background:{{ $riskStyle['bg'] }};color:{{ $riskStyle['fg'] }};border:1px solid {{ $riskStyle['border'] }};font-weight:600;display:inline-flex;align-items:center;gap:4px">
                    <span style="width:5px;height:5px;border-radius:50%;background:{{ $riskStyle['dot'] }};display:inline-block"></span>
                    {{ $attrRisk }}
                </span>
                @else
                <span class="tag" style="background:var(--success-light);color:var(--success);border:1px solid var(--success-border);font-weight:600">Low</span>
                @endif
            </td>
            <td>
                <a href="{{ route('employees.show', $emp) }}#tab-signals" class="action-link">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Pulse
                </a>
            </td>
        </tr>
        <tr id="expand-pulse-{{ $emp->id }}" style="display:none">
            <td colspan="7" style="padding:0;background:var(--gray-50);border-top:none">
                <div style="padding:14px 18px 14px 40px;border-left:3px solid var(--primary);margin:0 0 2px">
                    @if($emp->aiInsight)
                    <div style="display:flex;align-items:center;gap:7px;margin-bottom:8px">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        <span style="font-size:12.5px;font-weight:600;color:var(--primary);text-transform:uppercase;letter-spacing:.04em">AI Insight — {{ $emp->full_name }}</span>
                    </div>
                    <p style="margin:0;font-size:13.5px;color:var(--gray-600);line-height:1.65">{{ $emp->aiInsight->management_narrative }}</p>
                    @else
                    <div style="display:flex;align-items:center;gap:10px">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--gray-400)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <span style="font-size:13px;color:var(--gray-500)">No AI analysis yet. <a href="{{ route('employees.show', $emp) }}#tab-signals" style="color:var(--primary);font-weight:500">Open Work Pulse</a> to generate insights for this employee.</span>
                    </div>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted">No employees found. Add employees in the Resource Allocation section.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
