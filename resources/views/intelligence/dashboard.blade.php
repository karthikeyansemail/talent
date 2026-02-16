@extends('layouts.app')
@section('title', 'Signal Intelligence')
@section('page-title', 'Signal Intelligence')
@section('content')
<div class="page-header">
    <h1>Signal Intelligence</h1>
    <div style="display:flex;gap:10px;align-items:center">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input type="text" name="period" class="form-control" value="{{ $period }}" placeholder="2026-W06" style="width:140px">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        </form>
        <form method="POST" action="{{ route('intelligence.compute') }}">
            @csrf
            <input type="hidden" name="period" value="{{ $period }}">
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Compute Signals
            </button>
        </form>
    </div>
</div>

{{-- Org-level stats --}}
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-value">{{ $orgStats['total_employees'] }}</div>
        <div class="stat-label">Total Employees</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value">{{ $orgStats['employees_with_signals'] }}</div>
        <div class="stat-label">With Signal Data</div>
    </div>
    <div class="stat-card info">
        <div class="stat-value">{{ $orgStats['avg_consistency'] !== null ? number_format($orgStats['avg_consistency'], 1) : '-' }}</div>
        <div class="stat-label">Avg Consistency Index</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-value">{{ $orgStats['avg_workload_pressure'] !== null ? number_format($orgStats['avg_workload_pressure'], 1) : '-' }}</div>
        <div class="stat-label">Avg Workload Pressure</div>
    </div>
</div>

{{-- Employee Signal Cards --}}
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            Employee Signals &mdash; {{ $period }}
        </span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Consistency</th>
                <th>Recovery</th>
                <th>Workload</th>
                <th>Switching</th>
                <th>Collaboration</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $emp)
            <tr>
                <td><a href="{{ route('intelligence.employee', $emp) }}?period={{ $period }}">{{ $emp->full_name }}</a></td>
                <td>{{ $emp->department?->name ?? '-' }}</td>
                @if($emp->snapshot)
                <td>
                    <div class="signal-bar">
                        <div class="signal-fill" style="width:{{ $emp->snapshot->consistency_index ?? 0 }}%;background:var(--primary)"></div>
                    </div>
                    <span class="text-xs text-muted">{{ number_format($emp->snapshot->consistency_index ?? 0, 1) }}</span>
                </td>
                <td>
                    <div class="signal-bar">
                        <div class="signal-fill" style="width:{{ $emp->snapshot->recovery_signal ?? 0 }}%;background:var(--success)"></div>
                    </div>
                    <span class="text-xs text-muted">{{ number_format($emp->snapshot->recovery_signal ?? 0, 1) }}</span>
                </td>
                <td>
                    <div class="signal-bar">
                        <div class="signal-fill" style="width:{{ $emp->snapshot->workload_pressure ?? 0 }}%;background:var(--warning)"></div>
                    </div>
                    <span class="text-xs text-muted">{{ number_format($emp->snapshot->workload_pressure ?? 0, 1) }}</span>
                </td>
                <td>
                    <div class="signal-bar">
                        <div class="signal-fill" style="width:{{ $emp->snapshot->context_switching_index ?? 0 }}%;background:var(--info)"></div>
                    </div>
                    <span class="text-xs text-muted">{{ number_format($emp->snapshot->context_switching_index ?? 0, 1) }}</span>
                </td>
                <td>
                    <div class="signal-bar">
                        <div class="signal-fill" style="width:{{ $emp->snapshot->collaboration_density ?? 0 }}%;background:#8b5cf6"></div>
                    </div>
                    <span class="text-xs text-muted">{{ number_format($emp->snapshot->collaboration_density ?? 0, 1) }}</span>
                </td>
                @else
                <td colspan="5" class="text-muted text-sm">No signal data for this period</td>
                @endif
                <td>
                    <a href="{{ route('intelligence.employee', $emp) }}?period={{ $period }}" class="btn btn-sm btn-secondary">Details</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted">No employees found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
