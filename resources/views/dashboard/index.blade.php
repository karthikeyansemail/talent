@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('content')
<div class="page-header">
    <div>
        <h1>Dashboard</h1>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-value">{{ $stats['total_jobs'] }}</div>
        <div class="stat-label">Total Jobs ({{ $stats['open_jobs'] }} open)</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value">{{ $stats['total_candidates'] }}</div>
        <div class="stat-label">Total Candidates</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-value">{{ $stats['total_applications'] }}</div>
        <div class="stat-label">Applications</div>
    </div>
    <div class="stat-card info">
        <div class="stat-value">{{ $stats['total_employees'] }}</div>
        <div class="stat-label">Employees</div>
    </div>
    <div class="stat-card primary">
        <div class="stat-value">{{ $stats['total_projects'] }}</div>
        <div class="stat-label">Projects</div>
    </div>
</div>

@if(count($pipelineStats))
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Hiring Pipeline
        </span>
    </div>
    <div class="card-body">
        @php
            $stageColors = ['applied'=>'#94a3b8','ai_shortlisted'=>'#4f46e5','hr_screening'=>'#d97706','technical_round_1'=>'#7c3aed','technical_round_2'=>'#7c3aed','offer'=>'#ea580c','hired'=>'#059669','rejected'=>'#dc2626'];
            $total = array_sum($pipelineStats);
        @endphp
        <div class="pipeline-bar">
            @foreach($pipelineStats as $stage => $count)
            <div class="pipeline-segment" style="width:{{ $total > 0 ? ($count/$total*100) : 0 }}%;background:{{ $stageColors[$stage] ?? '#94a3b8' }}" title="{{ ucwords(str_replace('_',' ',$stage)) }}: {{ $count }}">{{ $count }}</div>
            @endforeach
        </div>
        <div class="flex gap-16 mt-2" style="flex-wrap:wrap">
            @foreach($pipelineStats as $stage => $count)
            <span class="text-sm flex-center gap-6">
                <span style="width:8px;height:8px;border-radius:50%;background:{{ $stageColors[$stage] ?? '#94a3b8' }};display:inline-block"></span>
                {{ ucwords(str_replace('_',' ',$stage)) }}: <strong>{{ $count }}</strong>
            </span>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Recent Applications
        </span>
    </div>
    @if($recentApplications->isEmpty())
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <p>No applications yet</p>
            <p class="empty-hint">Applications will appear here as candidates apply to jobs</p>
        </div>
    @else
    <table>
        <thead><tr><th>Candidate</th><th>Job</th><th>Stage</th><th>Applied</th></tr></thead>
        <tbody>
        @foreach($recentApplications as $app)
        <tr>
            <td><a href="{{ route('candidates.show', $app->candidate_id) }}">{{ $app->candidate->full_name }}</a></td>
            <td><a href="{{ route('jobs.show', $app->job_posting_id) }}">{{ $app->jobPosting->title }}</a></td>
            <td>@include('components.stage-badge', ['stage' => $app->stage])</td>
            <td class="text-sm text-muted">{{ $app->applied_at?->diffForHumans() }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
