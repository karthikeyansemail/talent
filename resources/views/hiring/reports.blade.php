@extends('layouts.app')
@section('title', 'Hiring Reports')
@section('page-title', 'Hiring Reports')
@section('content')
<div class="page-header">
    <h1>Hiring Reports</h1>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Pipeline Summary
            </span>
        </div>
        <div class="card-body">
            @php $stageColors = ['applied'=>'#94a3b8','ai_shortlisted'=>'#2563eb','hr_screening'=>'#d97706','technical_round_1'=>'#7c3aed','technical_round_2'=>'#7c3aed','offer'=>'#ea580c','hired'=>'#16a34a','rejected'=>'#dc2626']; @endphp
            @foreach($pipelineStats as $stage => $count)
            <div class="flex-between mb-1">
                <span class="font-medium">{{ ucwords(str_replace('_',' ',$stage)) }}</span>
                <span class="font-bold">{{ $count }}</span>
            </div>
            <div style="height:8px;background:var(--gray-100);border-radius:4px;margin-bottom:12px">
                <div style="height:100%;width:{{ $count > 0 ? min($count * 10, 100) : 0 }}%;background:{{ $stageColors[$stage] ?? '#94a3b8' }};border-radius:4px;transition:width 0.5s ease"></div>
            </div>
            @endforeach
            @if(empty($pipelineStats))
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                <p>No data yet</p>
            </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
                Job Status Overview
            </span>
        </div>
        <div class="card-body">
            @foreach($jobStats as $status => $count)
            <div class="flex-between mb-1">
                <span>@include('components.stage-badge', ['stage' => $status])</span>
                <span class="font-bold">{{ $count }}</span>
            </div>
            @endforeach
            @if(empty($jobStats))
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
                <p>No jobs yet</p>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Recent Hires
        </span>
    </div>
    @if($recentHires->isEmpty())
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            <p>No hires yet</p>
        </div>
    @else
    <table>
        <thead><tr><th>Candidate</th><th>Job</th><th>Date</th></tr></thead>
        <tbody>
        @foreach($recentHires as $app)
        <tr>
            <td>{{ $app->candidate->full_name }}</td>
            <td>{{ $app->jobPosting->title }}</td>
            <td class="text-sm text-muted">{{ $app->updated_at->format('M d, Y') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
