@extends('layouts.app')
@section('title', $job->title)
@section('page-title', 'Job Details')
@section('content')
<div class="page-header">
    <h1>{{ $job->title }}</h1>
    <div class="flex gap-10">
        @include('components.stage-badge', ['stage' => $job->status])
        <a href="{{ route('jobs.edit', $job) }}" class="btn btn-sm btn-secondary">Edit</a>
        <form method="POST" action="{{ route('jobs.updateStatus', $job) }}" style="display:inline">
            @csrf
            <select name="status" onchange="this.form.submit()" class="form-control" style="width:auto;display:inline;padding:5px 10px">
                @foreach(['draft','open','on_hold','closed'] as $s)
                <option value="{{ $s }}" {{ $job->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">Job Details</div>
        <div class="detail-grid">
            <div class="detail-item"><label>Department</label><div class="value">{{ $job->department?->name ?? 'N/A' }}</div></div>
            <div class="detail-item"><label>Employment Type</label><div class="value">{{ ucwords(str_replace('_',' ',$job->employment_type)) }}</div></div>
            <div class="detail-item"><label>Experience</label><div class="value">{{ $job->min_experience }}-{{ $job->max_experience }} years</div></div>
            <div class="detail-item"><label>Location</label><div class="value">{{ $job->location ?? 'N/A' }}</div></div>
            @if($job->salary_min || $job->salary_max)
            <div class="detail-item"><label>Salary Range</label><div class="value">{{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }}</div></div>
            @endif
            <div class="detail-item"><label>Created By</label><div class="value">{{ $job->creator?->name }}</div></div>
        </div>
        @if($job->required_skills && count($job->required_skills))
        <div class="mt-2"><label class="text-sm text-muted">Required Skills</label><div class="tags mt-1">@foreach($job->required_skills as $skill)<span class="tag">{{ $skill }}</span>@endforeach</div></div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">Description</div>
        <div style="white-space:pre-wrap">{{ $job->description }}</div>
        @if($job->requirements)
        <div class="mt-2"><strong>Requirements:</strong></div>
        <div style="white-space:pre-wrap">{{ $job->requirements }}</div>
        @endif
    </div>
</div>

<div class="card">
    <div class="flex-between">
        <div class="card-header" style="border:0;margin:0;padding:0">Applications ({{ $job->applications->count() }})</div>
        <button onclick="openModal('addApplicationModal')" class="btn btn-sm btn-primary">+ Add Application</button>
    </div>
    <table class="mt-2">
        <thead><tr><th>Candidate</th><th>Stage</th><th>AI Score</th><th>Applied</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($job->applications as $app)
        <tr>
            <td><a href="{{ route('candidates.show', $app->candidate_id) }}">{{ $app->candidate->full_name }}</a></td>
            <td>@include('components.stage-badge', ['stage' => $app->stage])</td>
            <td>
                @if($app->ai_score)
                <span class="score {{ $app->ai_score >= 70 ? 'high' : ($app->ai_score >= 40 ? 'medium' : 'low') }}" style="font-size:16px">{{ number_format($app->ai_score, 1) }}</span>
                @else <span class="text-muted">-</span> @endif
            </td>
            <td class="text-sm text-muted">{{ $app->applied_at?->format('M d, Y') }}</td>
            <td>
                <a href="{{ route('applications.show', $app) }}" class="btn btn-sm btn-secondary">View</a>
                <form method="POST" action="{{ route('applications.analyze', $app) }}" style="display:inline">
                    @csrf <button type="submit" class="btn btn-sm btn-primary">AI Analyze</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted">No applications yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="addApplicationModal">
    <div class="modal">
        <div class="modal-header">Add Application</div>
        <form method="POST" action="{{ route('applications.store', $job) }}">
            @csrf
            <div class="form-group">
                <label>Candidate ID</label>
                <input type="number" name="candidate_id" class="form-control" required placeholder="Enter candidate ID">
            </div>
            <div class="form-group">
                <label>Resume ID</label>
                <input type="number" name="resume_id" class="form-control" required placeholder="Enter resume ID">
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addApplicationModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Add</button>
            </div>
        </form>
    </div>
</div>
@endsection
