@extends('layouts.app')
@section('title', 'Hiring Reports')
@section('page-title', 'Hiring Reports')
@section('content')
<h1 class="mb-3">Hiring Reports</h1>

<div class="grid-2">
    <div class="card">
        <div class="card-header">Pipeline Summary</div>
        @php $stageColors = ['applied'=>'#94a3b8','ai_shortlisted'=>'#2563eb','hr_screening'=>'#d97706','technical_round_1'=>'#7c3aed','technical_round_2'=>'#7c3aed','offer'=>'#ea580c','hired'=>'#16a34a','rejected'=>'#dc2626']; @endphp
        @foreach($pipelineStats as $stage => $count)
        <div class="flex-between mb-1">
            <span>{{ ucwords(str_replace('_',' ',$stage)) }}</span>
            <span class="font-bold">{{ $count }}</span>
        </div>
        <div style="height:8px;background:var(--gray-100);border-radius:4px;margin-bottom:12px">
            <div style="height:100%;width:{{ $count > 0 ? min($count * 10, 100) : 0 }}%;background:{{ $stageColors[$stage] ?? '#94a3b8' }};border-radius:4px"></div>
        </div>
        @endforeach
        @if(empty($pipelineStats))<div class="empty-state"><p>No data yet.</p></div>@endif
    </div>

    <div class="card">
        <div class="card-header">Job Status Overview</div>
        @foreach($jobStats as $status => $count)
        <div class="flex-between mb-1">
            <span>@include('components.stage-badge', ['stage' => $status])</span>
            <span class="font-bold">{{ $count }}</span>
        </div>
        @endforeach
        @if(empty($jobStats))<div class="empty-state"><p>No jobs yet.</p></div>@endif
    </div>
</div>

<div class="card">
    <div class="card-header">Recent Hires</div>
    @if($recentHires->isEmpty())
        <div class="empty-state"><p>No hires yet.</p></div>
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
