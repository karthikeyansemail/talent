@extends('layouts.app')
@section('title', $project->name)
@section('page-title', 'Project Details')
@section('content')

{{-- Profile Hero --}}
<div class="profile-hero">
    <div class="avatar-lg">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
    </div>
    <div class="profile-info">
        <h1>{{ $project->name }}</h1>
        <div class="profile-meta">
            @include('components.stage-badge', ['stage' => $project->status])
            @include('components.stage-badge', ['stage' => $project->complexity_level])
            @if($project->start_date)
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                {{ $project->start_date->format('M d, Y') }} - {{ $project->end_date?->format('M d, Y') ?? 'Ongoing' }}
            </span>
            @endif
            @if($project->creator)
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                {{ $project->creator->name }}
            </span>
            @endif
        </div>
    </div>
</div>

{{-- Action Bar --}}
<div class="action-bar">
    <a href="{{ route('projects.edit', $project) }}" class="action-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit Project
    </a>
    <button type="button" class="action-btn action-primary ai-analyze-btn"
        data-url="{{ route('projects.findResources', $project) }}"
        data-status-url="{{ route('projects.matchStatus', $project) }}"
        data-target="#resourceMatchesContent"
        data-context="project">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        <span class="ai-btn-text">Find Best Resources</span>
    </button>
</div>

<div class="grid-2">
    {{-- Project Details Card --}}
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Project Details
            </span>
        </div>
        <div class="card-body">
            <div class="detail-list">
                <div class="detail-row">
                    <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
                    <div class="row-content"><div class="row-label">Complexity</div><div class="row-value">@include('components.stage-badge', ['stage' => $project->complexity_level])</div></div>
                </div>
                <div class="detail-row">
                    <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                    <div class="row-content"><div class="row-label">Created By</div><div class="row-value">{{ $project->creator?->name }}</div></div>
                </div>
                <div class="detail-row">
                    <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                    <div class="row-content"><div class="row-label">Start Date</div><div class="row-value">{{ $project->start_date?->format('M d, Y') ?? 'TBD' }}</div></div>
                </div>
                <div class="detail-row">
                    <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                    <div class="row-content"><div class="row-label">End Date</div><div class="row-value">{{ $project->end_date?->format('M d, Y') ?? 'TBD' }}</div></div>
                </div>
            </div>
            @if($project->description)
            <div class="divider"></div>
            <div class="section-label">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Description
            </div>
            <div class="content-prose">{{ $project->description }}</div>
            @endif
            @if($project->domain_context)
            <div class="mt-2">
                <div class="section-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Domain Context
                </div>
                <div class="content-prose">{{ $project->domain_context }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Requirements Card --}}
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Requirements
            </span>
        </div>
        <div class="card-body">
            @if($project->required_skills && count($project->required_skills))
            <div class="mb-2">
                <div class="section-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Required Skills
                </div>
                <div class="tags">@foreach($project->required_skills as $s)<span class="tag">{{ $s }}</span>@endforeach</div>
            </div>
            @endif
            @if($project->required_technologies && count($project->required_technologies))
            <div class="mt-2">
                <div class="section-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    Required Technologies
                </div>
                <div class="tags">@foreach($project->required_technologies as $t)<span class="tag">{{ $t }}</span>@endforeach</div>
            </div>
            @endif
            @if((!$project->required_skills || !count($project->required_skills)) && (!$project->required_technologies || !count($project->required_technologies)))
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <p>No requirements defined yet</p>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Sprint Data --}}
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Sprint Spreadsheets
        </span>
        <span class="text-sm text-muted" style="margin-left:auto">Upload task/sprint data as additional context for AI resource matching</span>
    </div>
    <div class="card-body">
        {{-- Existing uploaded sheets --}}
        @if($project->sprintSheets->count())
        <div class="sprint-sheets-list" style="margin-bottom: 20px">
            @foreach($project->sprintSheets as $sheet)
            <div class="sprint-sheet-item">
                <div class="sprint-sheet-info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <div>
                        <span class="sprint-sheet-name">{{ $sheet->original_filename }}</span>
                        <span class="sprint-sheet-meta">
                            @if($sheet->status === 'parsed')
                                {{ $sheet->row_count }} rows
                                @if($sheet->parsed_summary)
                                    &middot; {{ $sheet->parsed_summary['unique_employees'] ?? 0 }} employees
                                    &middot; {{ $sheet->parsed_summary['total_story_points'] ?? 0 }} story points
                                @endif
                            @elseif($sheet->status === 'failed')
                                <span style="color:var(--red-600)">Parse failed: {{ $sheet->error_message }}</span>
                            @else
                                Processing...
                            @endif
                            &middot; {{ number_format($sheet->file_size / 1024, 1) }} KB
                            &middot; {{ $sheet->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                <div class="sprint-sheet-actions">
                    @if($sheet->status === 'parsed')
                    <span class="badge badge-green" style="font-size:11px">Parsed</span>
                    @elseif($sheet->status === 'failed')
                    <span class="badge badge-red" style="font-size:11px">Failed</span>
                    @endif
                    <form method="POST" action="{{ route('projects.sprintSheets.destroy', [$project, $sheet]) }}" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this spreadsheet?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Upload form --}}
        <form method="POST" action="{{ route('projects.sprintSheets.upload', $project) }}" enctype="multipart/form-data" id="sprintUploadForm">
            @csrf
            <div class="sprint-upload-area" id="sprintDropZone">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <p style="margin:8px 0 4px;font-weight:500;color:var(--gray-700)">Drop sprint spreadsheets here or click to browse</p>
                <p class="text-sm text-muted">CSV or XLSX files, up to 5MB each. Multiple files allowed.</p>
                <input type="file" name="files[]" id="sprintFileInput" multiple accept=".csv,.xlsx" style="display:none">
            </div>
            <div id="sprintFileList" class="sprint-file-list" style="display:none"></div>
            <button type="submit" id="sprintUploadBtn" class="btn btn-primary" style="margin-top:12px;display:none">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload & Parse
            </button>
        </form>

        <div class="alert alert-info" style="margin-top:16px;margin-bottom:0">
            <strong>Expected columns:</strong> employee_email, task_key, summary, status, priority, story_points, sprint_name, completed_at
            <br><span class="text-sm">Sprint data provides additional context for AI resource matching. Upload your team's sprint/task data to improve match accuracy.</span>
        </div>
    </div>
</div>

{{-- Resource Matches --}}
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Resource Matches
        </span>
    </div>
    <div id="resourceMatchesContent">
        @include('projects._resource-matches-table', ['project' => $project])
    </div>
</div>
@endsection
