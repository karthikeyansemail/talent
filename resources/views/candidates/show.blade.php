@extends('layouts.app')
@section('title', $candidate->full_name)
@section('page-title', 'Candidate Details')
@section('content')

{{-- Profile Hero --}}
<div class="profile-hero">
    <div class="avatar-lg">{{ strtoupper(substr($candidate->first_name, 0, 1) . substr($candidate->last_name, 0, 1)) }}</div>
    <div class="profile-info">
        <h1>{{ $candidate->full_name }}</h1>
        <div class="profile-meta">
            @if($candidate->current_title)
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
                {{ $candidate->current_title }}
            </span>
            @endif
            @if($candidate->current_company)
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                {{ $candidate->current_company }}
            </span>
            @endif
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                {{ $candidate->email }}
            </span>
            @if($candidate->experience_years)
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ $candidate->experience_years }} years exp.
            </span>
            @endif
            <span class="badge badge-gray">{{ ucfirst($candidate->source) }}</span>
        </div>
    </div>
</div>

{{-- Action Bar --}}
<div class="action-bar">
    <a href="{{ route('candidates.edit', $candidate) }}" class="action-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit Candidate
    </a>
    <form method="POST" action="{{ route('candidates.destroy', $candidate) }}" style="display:inline">
        @csrf @method('DELETE')
        <button type="submit" class="action-btn action-danger" onclick="return confirm('Delete this candidate?')">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Delete
        </button>
    </form>
</div>

{{-- Tabs --}}
<div class="tabs" data-tabs>
    <button class="tab active" data-tab="tab-profile">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Profile
    </button>
    <button class="tab" data-tab="tab-applications">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
        Applications ({{ $candidate->applications->count() }})
    </button>
</div>

{{-- Tab: Profile --}}
<div class="tab-content active" id="tab-profile">
    <div class="grid-2">
        {{-- Profile Card --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Profile Information
                </span>
            </div>
            <div class="card-body">
                <div class="detail-list">
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
                        <div class="row-content"><div class="row-label">Email</div><div class="row-value">{{ $candidate->email }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
                        <div class="row-content"><div class="row-label">Phone</div><div class="row-value">{{ $candidate->phone ?? 'N/A' }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                        <div class="row-content"><div class="row-label">Current Company</div><div class="row-value">{{ $candidate->current_company ?? 'N/A' }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg></div>
                        <div class="row-content"><div class="row-label">Current Title</div><div class="row-value">{{ $candidate->current_title ?? 'N/A' }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                        <div class="row-content"><div class="row-label">Experience</div><div class="row-value">{{ $candidate->experience_years ?? 'N/A' }} years</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
                        <div class="row-content"><div class="row-label">Source</div><div class="row-value"><span class="badge badge-blue">{{ ucfirst($candidate->source) }}</span></div></div>
                    </div>
                </div>
                @if($candidate->skills && count($candidate->skills))
                <div class="notes-block">
                    <div class="section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        Skills
                    </div>
                    <div class="tags" style="margin-top:8px">
                        @foreach($candidate->skills as $skill)
                        <span class="tag">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
                @if($candidate->notes)
                <div class="notes-block">
                    <div class="section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        Notes
                    </div>
                    <p>{{ $candidate->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Resumes Card --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Resumes
                </span>
            </div>
            <div class="card-body">
                <div class="upload-area">
                    <form method="POST" action="{{ route('resumes.upload', $candidate) }}" enctype="multipart/form-data">
                        @csrf
                        <div style="display:flex;align-items:center;gap:12px;justify-content:center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--gray-400)"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <input type="file" name="resume" class="form-control" accept=".pdf,.docx" required style="max-width:240px">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Upload
                            </button>
                        </div>
                    </form>
                </div>

                @if($candidate->resumes->count())
                <div class="mt-2">
                    @foreach($candidate->resumes as $resume)
                    <div class="detail-row">
                        <div class="row-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <div class="row-content">
                            <div class="row-value">{{ $resume->file_name }}</div>
                            <div class="row-label">{{ strtoupper($resume->file_type) }} &middot; {{ $resume->created_at->format('M d, Y') }}</div>
                        </div>
                        <a href="{{ route('resumes.download', [$candidate, $resume]) }}" class="btn btn-sm btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download
                        </a>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tab: Applications --}}
<div class="tab-content" id="tab-applications">
    {{-- Apply to Job Postings --}}
    @if($availableJobs->count())
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Apply to Job Postings
            </span>
        </div>
        <div class="card-body">
            <p class="text-muted" style="margin-bottom: 12px">Select one or more open job postings to apply this candidate to.</p>
            <form method="POST" action="{{ route('candidates.applyToJobs', $candidate) }}">
                @csrf
                @if($candidate->resumes->count() > 1)
                <div class="form-group" style="margin-bottom: 12px">
                    <label>Resume to attach</label>
                    <select name="resume_id" class="form-control" style="max-width: 400px">
                        @foreach($candidate->resumes as $resume)
                        <option value="{{ $resume->id }}" {{ $loop->last ? 'selected' : '' }}>{{ $resume->file_name }} ({{ $resume->created_at->format('M d, Y') }})</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="apply-jobs-list">
                    @foreach($availableJobs as $job)
                    <label class="apply-job-option">
                        <input type="checkbox" name="job_ids[]" value="{{ $job->id }}">
                        <span class="apply-job-title">{{ $job->title }}</span>
                        <span class="badge {{ $job->status === 'open' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($job->status) }}</span>
                    </label>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 12px">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Apply to Selected Jobs
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Applications --}}
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
                Applications ({{ $candidate->applications->count() }})
            </span>
        </div>
        @if($candidate->applications->count())
        <table>
            <thead><tr><th>Job</th><th>Stage</th><th>AI Score</th><th>Applied</th><th></th></tr></thead>
            <tbody>
            @foreach($candidate->applications as $app)
            <tr>
                <td><a href="{{ route('jobs.show', $app->job_posting_id) }}">{{ $app->jobPosting->title }}</a></td>
                <td>@include('components.stage-badge', ['stage' => $app->stage])</td>
                <td>
                    @if($app->ai_score)
                    <span class="score {{ $app->ai_score >= 70 ? 'high' : ($app->ai_score >= 40 ? 'medium' : 'low') }}" style="font-size:16px">{{ number_format($app->ai_score, 1) }}</span>
                    @else <span class="text-muted">-</span> @endif
                </td>
                <td class="text-sm text-muted">{{ $app->applied_at?->format('M d, Y') }}</td>
                <td>
                    <a href="{{ route('applications.show', $app) }}" class="btn btn-sm btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        View
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="card-body">
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
                <p>No applications yet</p>
                @if($availableJobs->count())
                <div class="empty-hint">Use the form above to apply this candidate to job postings</div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
