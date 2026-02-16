@extends('layouts.app')
@section('title', $employee->full_name . ' - Signals')
@section('page-title', 'Employee Signals')
@section('content')
<div class="page-header">
    <h1>{{ $employee->full_name }}</h1>
    <a href="{{ route('intelligence.dashboard') }}?period={{ $period }}" class="btn btn-secondary">Back to Dashboard</a>
</div>

<div class="profile-hero" style="margin-bottom:24px">
    <div class="avatar-lg">{{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}</div>
    <div class="profile-info">
        <h1>{{ $employee->full_name }}</h1>
        <div class="profile-meta">
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
                {{ $employee->designation ?? 'Employee' }}
            </span>
            @if($employee->department)
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                {{ $employee->department->name }}
            </span>
            @endif
            <span class="meta-item">Period: <strong>{{ $period }}</strong></span>
        </div>
    </div>
</div>

@if($snapshot)
{{-- Meta-Signals --}}
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card primary">
        <div class="stat-value">{{ number_format($snapshot->consistency_index, 1) }}</div>
        <div class="stat-label">Consistency Index</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value">{{ number_format($snapshot->recovery_signal, 1) }}</div>
        <div class="stat-label">Recovery Signal</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-value">{{ number_format($snapshot->workload_pressure, 1) }}</div>
        <div class="stat-label">Workload Pressure</div>
    </div>
    <div class="stat-card info">
        <div class="stat-value">{{ number_format($snapshot->context_switching_index, 1) }}</div>
        <div class="stat-label">Context Switching</div>
    </div>
    <div class="stat-card" style="border-top:3px solid #8b5cf6">
        <div class="stat-value">{{ number_format($snapshot->collaboration_density, 1) }}</div>
        <div class="stat-label">Collaboration Density</div>
    </div>
</div>

{{-- AI Summary --}}
@if($snapshot->ai_summary)
<div class="ai-section" style="margin-bottom:24px">
    <h3>
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        AI Analysis Summary
    </h3>
    <p class="content-prose">{{ $snapshot->ai_summary }}</p>

    @if($snapshot->ai_analysis && isset($snapshot->ai_analysis['signal_insights']))
    <div style="margin-top:16px">
        <div class="section-label">Objective Insights</div>
        <ul style="padding-left:20px;margin:0">
            @foreach($snapshot->ai_analysis['signal_insights'] as $insight)
            <li style="font-size:13.5px;color:var(--gray-600);padding:4px 0">{{ $insight }}</li>
            @endforeach
        </ul>
    </div>
    @endif
</div>
@endif
@endif

{{-- Raw Signals Table --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Raw Signal Data
        </span>
    </div>
    <table>
        <thead><tr><th>Source</th><th>Metric</th><th>Value</th><th>Unit</th></tr></thead>
        <tbody>
        @forelse($signals as $signal)
        <tr>
            <td><span class="badge badge-blue">{{ $signal->source_type }}</span></td>
            <td>{{ str_replace('_', ' ', ucfirst($signal->metric_key)) }}</td>
            <td class="font-semibold">{{ number_format($signal->metric_value, 2) }}</td>
            <td class="text-muted">{{ $signal->metric_unit ?? '-' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center text-muted">No raw signal data for this period. Run "Compute Signals" from the dashboard.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Historical Trend --}}
@if($history->count() > 1)
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Historical Trend
        </span>
    </div>
    <div class="card-body">
        <table>
            <thead><tr><th>Period</th><th>Consistency</th><th>Recovery</th><th>Workload</th><th>Switching</th><th>Collaboration</th></tr></thead>
            <tbody>
            @foreach($history as $h)
            <tr>
                <td class="font-medium">{{ $h->period }}</td>
                <td>{{ number_format($h->consistency_index ?? 0, 1) }}</td>
                <td>{{ number_format($h->recovery_signal ?? 0, 1) }}</td>
                <td>{{ number_format($h->workload_pressure ?? 0, 1) }}</td>
                <td>{{ number_format($h->context_switching_index ?? 0, 1) }}</td>
                <td>{{ number_format($h->collaboration_density ?? 0, 1) }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
