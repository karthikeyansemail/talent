@extends('layouts.app')
@section('title', 'Support Pulse')
@section('page-title', 'Support Pulse')
@section('content')

@php
    /**
     * Format value based on unit. For "lower is better" metrics
     * (response time, reopen rate), invert the delta color logic.
     */
    $fmtVal = function (float $value, string $unit): string {
        if ($unit === 'percent')  return number_format($value, 1) . '%';
        if ($unit === 'minutes')  {
            if ($value >= 60)  return number_format($value / 60, 1) . 'h';
            return number_format($value, 0) . 'm';
        }
        return number_format($value, 0);
    };
    $fmtDelta = function (float $curr, float $prev, bool $higherBetter) {
        if ($prev <= 0 && $curr <= 0) return ['', ''];
        if ($prev == 0) return ['', ''];
        $deltaPct = (int) round((($curr - $prev) / $prev) * 100);
        if ($deltaPct === 0) return ['→ 0%', 'var(--text-muted)'];
        $isImprovement = $higherBetter ? ($deltaPct > 0) : ($deltaPct < 0);
        $color = $isImprovement ? '#16a34a' : '#dc2626';
        $arrow = $deltaPct > 0 ? '↑' : '↓';
        return [$arrow . ' ' . abs($deltaPct) . '%', $color];
    };
    $periodLabel = $latestPeriod ? str_replace('-W', ' · Week ', $latestPeriod) : '—';
@endphp

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1>Support Pulse</h1>
        <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
            Customer support agent activity for {{ $orgName }} — period <strong>{{ $periodLabel }}</strong>
        </p>
    </div>
    <a href="{{ route('settings.integrations.index') }}#tab-support" class="btn btn-sm btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/></svg>
        Manage Support Connections
    </a>
</div>

@if(empty($agents))
    <div class="card">
        <div class="card-body" style="text-align:center;padding:60px 30px">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-subtle)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 16px;display:block"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/></svg>
            <h3 style="font-size:16px;color:var(--text-strong);margin:0 0 8px">No support activity yet</h3>
            <p style="color:var(--text-muted);font-size:13px;margin:0 0 16px;max-width:480px;margin-left:auto;margin-right:auto;line-height:1.5">
                Connect Zendesk or Freshdesk to pull per-agent ticket activity.
                Or run <code style="background:var(--code-bg);padding:2px 6px;border-radius:4px;font-size:12px">php artisan demo:refresh --org={{ Auth::user()->currentOrganizationId() }}</code> to seed sample data.
            </p>
            <a href="{{ route('settings.integrations.index') }}#tab-support" class="btn btn-primary">Connect Support Tool</a>
        </div>
    </div>
@else

{{-- Team KPI cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:24px">
    @foreach($metrics as $key => $meta)
        @php [$delta, $color] = $fmtDelta($teamCurrent[$key], $teamPrev[$key] ?? 0, $meta['higher_better']); @endphp
        <div class="card" style="margin-bottom:0">
            <div class="card-body" style="padding:16px 18px">
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">{{ $meta['label'] }}</div>
                <div style="font-size:24px;font-weight:700;color:var(--text-strong);margin:6px 0 2px">{{ $fmtVal($teamCurrent[$key], $meta['unit']) }}</div>
                @if($delta)
                    <div style="font-size:12px;color:{{ $color }};font-weight:500">{{ $delta }} <span style="color:var(--text-subtle);font-weight:400">vs prev week</span></div>
                @else
                    <div style="font-size:12px;color:var(--text-subtle)">No prior data</div>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- Per-agent table --}}
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Support Agents ({{ count($agents) }})
        </span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Agent</th>
                <th>Role</th>
                @foreach($metrics as $key => $meta)
                    <th style="text-align:right">{{ $meta['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($agents as $row)
                @php $emp = $row['employee']; @endphp
                <tr>
                    <td>
                        @if(auth()->user()->isSuperAdmin() || (auth()->user()->currentOrganization()?->canUseModule('work_signals') ?? false))
                            <a href="{{ route('employees.show', $emp->id) }}" class="name-link">{{ $emp->first_name }} {{ $emp->last_name }}</a>
                        @else
                            {{ $emp->first_name }} {{ $emp->last_name }}
                        @endif
                    </td>
                    <td class="text-sm text-muted">{{ $emp->designation ?? '—' }}</td>
                    @foreach($metrics as $key => $meta)
                        @php
                            $curr = $row['current'][$key] ?? 0;
                            $prev = $row['previous'][$key] ?? 0;
                            [$d, $c] = $fmtDelta($curr, $prev, $meta['higher_better']);
                        @endphp
                        <td style="text-align:right">
                            <div style="font-weight:600;color:var(--text-strong)">{{ $fmtVal($curr, $meta['unit']) }}</div>
                            @if($d)
                                <div style="font-size:11px;color:{{ $c }};margin-top:2px">{{ $d }}</div>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top:16px;padding:12px 16px;background:var(--bg-muted);border-radius:8px;font-size:12px;color:var(--text-muted);line-height:1.6">
    <strong>How this works:</strong> KPIs come from the same EmployeeSignal table that powers Work Pulse and Sales Pulse — fed by your live Zendesk/Freshdesk connection (Settings → Integrations → Customer Support) or by the demo refresher. Click an agent name to view their full engagement profile.
</div>

@endif

@endsection
