@extends('layouts.app')
@section('title', $candidate->full_name)
@section('page-title', 'Candidate Details')
@section('content')
<div class="page-header">
    <h1>{{ $candidate->full_name }}</h1>
    <div class="flex gap-10">
        <a href="{{ route('candidates.edit', $candidate) }}" class="btn btn-sm btn-secondary">Edit</a>
        <form method="POST" action="{{ route('candidates.destroy', $candidate) }}" style="display:inline">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this candidate?')">Delete</button>
        </form>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">Profile</div>
        <div class="detail-grid">
            <div class="detail-item"><label>Email</label><div class="value">{{ $candidate->email }}</div></div>
            <div class="detail-item"><label>Phone</label><div class="value">{{ $candidate->phone ?? 'N/A' }}</div></div>
            <div class="detail-item"><label>Current Company</label><div class="value">{{ $candidate->current_company ?? 'N/A' }}</div></div>
            <div class="detail-item"><label>Current Title</label><div class="value">{{ $candidate->current_title ?? 'N/A' }}</div></div>
            <div class="detail-item"><label>Experience</label><div class="value">{{ $candidate->experience_years ?? 'N/A' }} years</div></div>
            <div class="detail-item"><label>Source</label><div class="value">{{ ucfirst($candidate->source) }}</div></div>
        </div>
        @if($candidate->notes)
        <div class="mt-2"><label class="text-sm text-muted">Notes</label><p class="mt-1">{{ $candidate->notes }}</p></div>
        @endif
    </div>

    <div class="card">
        <div class="flex-between">
            <div class="card-header" style="border:0;margin:0;padding:0">Resumes</div>
        </div>
        <form method="POST" action="{{ route('resumes.upload', $candidate) }}" enctype="multipart/form-data" class="mt-2">
            @csrf
            <div class="flex gap-10">
                <input type="file" name="resume" class="form-control" accept=".pdf,.docx" required>
                <button type="submit" class="btn btn-sm btn-primary">Upload</button>
            </div>
        </form>
        @if($candidate->resumes->count())
        <table class="mt-2">
            <thead><tr><th>File</th><th>Type</th><th>Uploaded</th><th></th></tr></thead>
            <tbody>
            @foreach($candidate->resumes as $resume)
            <tr>
                <td>{{ $resume->file_name }}</td>
                <td><span class="badge badge-gray">{{ strtoupper($resume->file_type) }}</span></td>
                <td class="text-sm text-muted">{{ $resume->created_at->format('M d, Y') }}</td>
                <td><a href="{{ route('resumes.download', [$candidate, $resume]) }}" class="btn btn-sm btn-secondary">Download</a></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

@if($candidate->applications->count())
<div class="card">
    <div class="card-header">Applications</div>
    <table>
        <thead><tr><th>Job</th><th>Stage</th><th>AI Score</th><th>Applied</th><th></th></tr></thead>
        <tbody>
        @foreach($candidate->applications as $app)
        <tr>
            <td><a href="{{ route('jobs.show', $app->job_posting_id) }}">{{ $app->jobPosting->title }}</a></td>
            <td>@include('components.stage-badge', ['stage' => $app->stage])</td>
            <td>{{ $app->ai_score ? number_format($app->ai_score, 1) : '-' }}</td>
            <td class="text-sm text-muted">{{ $app->applied_at?->format('M d, Y') }}</td>
            <td><a href="{{ route('applications.show', $app) }}" class="btn btn-sm btn-secondary">View</a></td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
