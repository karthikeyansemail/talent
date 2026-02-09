@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('content')
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
    <div class="card-header">Hiring Pipeline</div>
    @php
        $stageColors = ['applied'=>'#94a3b8','ai_shortlisted'=>'#2563eb','hr_screening'=>'#d97706','technical_round_1'=>'#7c3aed','technical_round_2'=>'#7c3aed','offer'=>'#ea580c','hired'=>'#16a34a','rejected'=>'#dc2626'];
        $total = array_sum($pipelineStats);
    @endphp
    <div class="pipeline-bar">
        @foreach($pipelineStats as $stage => $count)
        <div class="pipeline-segment" style="width:{{ $total > 0 ? ($count/$total*100) : 0 }}%;background:{{ $stageColors[$stage] ?? '#94a3b8' }}" title="{{ ucwords(str_replace('_',' ',$stage)) }}: {{ $count }}">{{ $count }}</div>
        @endforeach
    </div>
    <div class="flex gap-10 mt-1" style="flex-wrap:wrap">
        @foreach($pipelineStats as $stage => $count)
        <span class="text-sm"><span style="color:{{ $stageColors[$stage] ?? '#94a3b8' }}">&#9679;</span> {{ ucwords(str_replace('_',' ',$stage)) }}: {{ $count }}</span>
        @endforeach
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">Recent Applications</div>
    @if($recentApplications->isEmpty())
        <div class="empty-state"><p>No applications yet.</p></div>
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
