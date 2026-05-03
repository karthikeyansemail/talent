@extends('layouts.app')
@section('title', 'Sales Pulse')
@section('page-title', 'Sales Pulse')
@section('content')

@php
    $org = auth()->user()->currentOrganization();
    /**
     * Format a metric value based on unit. Money values use the org's
     * configured currency (Settings → Workspace → Currency). No conversion.
     */
    $fmtVal = function (float $value, string $unit) use ($org): string {
        if ($unit === 'usd' || $unit === 'money') {
            return $org ? $org->formatMoney($value) : number_format($value, 0);
        }
        return number_format($value, 0);
    };
    $fmtDelta = function (float $curr, float $prev) {
        if ($prev <= 0) return ['', ''];
        $deltaPct = (int) round((($curr - $prev) / $prev) * 100);
        if ($deltaPct === 0) return ['→ 0%', 'var(--gray-500)'];
        if ($deltaPct > 0)   return ['↑ ' . $deltaPct . '%', '#16a34a'];
        return ['↓ ' . abs($deltaPct) . '%', '#dc2626'];
    };
    $periodLabel = $latestPeriod ? str_replace('-W', ' · Week ', $latestPeriod) : '—';
@endphp

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1>Sales Pulse</h1>
        <p style="margin:6px 0 0;color:var(--gray-500);font-size:13px">
            CRM activity for {{ $orgName }} — period <strong>{{ $periodLabel }}</strong>
        </p>
    </div>
    <a href="{{ route('settings.integrations.index') }}#tab-crm" class="btn btn-sm btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Manage CRM Connections
    </a>
</div>

@if(empty($reps))
    <div class="card">
        <div class="card-body" style="text-align:center;padding:60px 30px">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--gray-300)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 16px;display:block"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <h3 style="font-size:16px;color:var(--gray-700);margin:0 0 8px">No CRM activity yet</h3>
            <p style="color:var(--gray-500);font-size:13px;margin:0 0 16px;max-width:480px;margin-left:auto;margin-right:auto;line-height:1.5">
                Connect Salesforce, HubSpot, or Zoho CRM to pull deal activity per rep.
                Or run <code style="background:var(--gray-100);padding:2px 6px;border-radius:4px;font-size:12px">php artisan demo:refresh --org={{ Auth::user()->currentOrganizationId() }}</code> to seed sample data.
            </p>
            <a href="{{ route('settings.integrations.index') }}#tab-crm" class="btn btn-primary">Connect a CRM</a>
        </div>
    </div>
@else

{{-- ── Team KPI cards ─────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:24px">
    @foreach($metrics as $key => $meta)
        @php [$delta, $color] = $fmtDelta($teamTotals[$key], $teamTotalsPrev[$key] ?? 0); @endphp
        <div class="card" style="margin-bottom:0">
            <div class="card-body" style="padding:16px 18px">
                <div style="font-size:11px;color:var(--gray-500);text-transform:uppercase;letter-spacing:.04em;font-weight:600">{{ $meta['label'] }}</div>
                <div style="font-size:24px;font-weight:700;color:var(--gray-800);margin:6px 0 2px">{{ $fmtVal($teamTotals[$key], $meta['unit']) }}</div>
                @if($delta)
                    <div style="font-size:12px;color:{{ $color }};font-weight:500">{{ $delta }} <span style="color:var(--gray-400);font-weight:400">vs prev week</span></div>
                @else
                    <div style="font-size:12px;color:var(--gray-400)">No prior data</div>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- ── Per-rep table ──────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Sales Reps ({{ count($reps) }})
        </span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Rep</th>
                <th>Role</th>
                @foreach($metrics as $key => $meta)
                    <th style="text-align:right">{{ $meta['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($reps as $row)
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
                            [$d, $c] = $fmtDelta($curr, $prev);
                        @endphp
                        <td style="text-align:right">
                            <div style="font-weight:600;color:var(--gray-800)">{{ $fmtVal($curr, $meta['unit']) }}</div>
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

<div style="margin-top:16px;padding:12px 16px;background:var(--gray-50);border-radius:8px;font-size:12px;color:var(--gray-500);line-height:1.6">
    <strong>How this works:</strong> KPIs are pulled from the same EmployeeSignal table that powers Work Pulse — fed either by your live CRM connection (Settings → Integrations → Sales CRM) or by the demo refresher. Click a rep's name to open their full engagement profile.
</div>

@endif

@endsection
