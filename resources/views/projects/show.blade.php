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
    <button type="button" class="action-btn action-primary"
        id="findResourcesBtn"
        onclick="openResourceFilterModal()">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        <span class="ai-btn-text">Find Best Resources</span>
    </button>
</div>

{{-- Tabs --}}
<div data-tabs class="tabs" id="projectTabs">
    <button class="tab active" data-tab="tab-overview">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Overview
    </button>
    <button class="tab" data-tab="tab-documents">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Documents
        @if($project->documents->count())
        <span class="badge badge-blue" style="font-size:11px;padding:2px 7px">{{ $project->documents->count() }}</span>
        @endif
    </button>
    <button class="tab" data-tab="tab-sprint">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Sprint Data
        @if($project->sprintSheets->count())
        <span class="badge badge-gray" style="font-size:11px;padding:2px 7px">{{ $project->sprintSheets->count() }}</span>
        @endif
    </button>
    <button class="tab" data-tab="tab-resources" id="resourcesTab">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Resource Matches
        @php $matchCount = $project->resourceMatches()->count(); @endphp
        @if($matchCount)
        <span class="badge badge-blue" style="font-size:11px;padding:2px 7px">{{ $matchCount }}</span>
        @endif
    </button>
</div>

{{-- Tab: Overview --}}
<div id="tab-overview" class="tab-content active">
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
</div>

{{-- Tab: Documents --}}
<div id="tab-documents" class="tab-content">
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Project Documents
            </span>
            @if($project->documents->count())
            <form method="POST" action="{{ route('projects.syncFromDocuments', $project) }}" style="margin-left:auto">
                @csrf
                <button type="submit" class="action-btn action-primary" style="height:34px;font-size:13px;padding:0 14px">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    Sync Project from Documents
                </button>
            </form>
            @endif
        </div>
        <div class="card-body">

            {{-- Existing documents list --}}
            @if($project->documents->count())
            <div class="sprint-sheets-list" style="margin-bottom:20px">
                @foreach($project->documents as $doc)
                <div class="sprint-sheet-item">
                    <div class="sprint-sheet-info">
                        @if($doc->isCharter())
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 0-4 4c0 2 2 3 2 6H6a2 2 0 0 0-2 2v2h16v-2a2 2 0 0 0-2-2h-4c0-3 2-4 2-6a4 4 0 0 0-4-4z"/><path d="M9 18v1a3 3 0 0 0 6 0v-1"/></svg>
                        @else
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        @endif
                        <div>
                            <span class="sprint-sheet-name">{{ $doc->original_filename }}</span>
                            <span class="sprint-sheet-meta">
                                {{ $doc->label }}
                                &middot; {{ strtoupper($doc->file_type) }}
                                &middot; {{ number_format($doc->file_size / 1024, 1) }} KB
                                &middot; {{ $doc->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                    <div class="sprint-sheet-actions">
                        @if($doc->isCharter())
                        <span class="badge badge-blue" style="font-size:11px">Charter</span>
                        @endif
                        <form method="POST" action="{{ route('projects.documents.destroy', [$project, $doc]) }}" style="display:inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this document?')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Upload new document --}}
            <form method="POST" action="{{ route('projects.documents.upload', $project) }}" enctype="multipart/form-data" id="docUploadForm">
                @csrf
                <div style="margin-bottom:10px">
                    <label style="font-size:13px;font-weight:500;color:var(--gray-700);display:block;margin-bottom:4px">Document Label <span style="font-weight:400;color:var(--gray-500)">(optional)</span></label>
                    <input type="text" name="label" class="form-control" placeholder="e.g. Technical Specification, Scope Document, Updated Charter…" style="max-width:420px">
                </div>
                <div class="sprint-upload-area" id="docDropZone">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <p style="margin:8px 0 4px;font-weight:500;color:var(--gray-700)">Drop a PDF or DOCX file here, or click to browse</p>
                    <p class="text-sm text-muted">Requirement documents, technical specs, scope updates — up to 10 MB</p>
                    <input type="file" name="document" id="docFileInput" accept=".pdf,.docx" style="display:none">
                </div>
                <div id="docFileName" style="display:none;margin-top:8px;font-size:13px;color:var(--gray-600)"></div>
                <button type="submit" id="docUploadBtn" class="btn btn-primary" style="margin-top:12px;display:none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Upload Document
                </button>
            </form>

            <div class="alert alert-info" style="margin-top:16px;margin-bottom:0">
                <strong>Tip:</strong> After uploading or removing documents, click <strong>Sync Project from Documents</strong> to update the project description, skills, and domain context automatically from the latest document content.
            </div>
        </div>
    </div>
</div>

{{-- Tab: Sprint Data --}}
<div id="tab-sprint" class="tab-content">
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Sprint Spreadsheets
            </span>
            <span class="text-sm text-muted" style="margin-left:auto">Upload task/sprint data as additional context for AI resource matching</span>
        </div>
        <div class="card-body">
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
</div>

{{-- Tab: Resource Matches --}}
<div id="tab-resources" class="tab-content">
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Resource Matches
            </span>
            <span class="text-sm text-muted" style="margin-left:auto">Click "Find Best Resources" in the toolbar to run AI matching</span>
        </div>
        <div id="resourceMatchesContent">
            @include('projects._resource-matches-table', ['project' => $project])
        </div>
    </div>
</div>

<script>
// Document upload drop zone
(function() {
    var dropZone   = document.getElementById('docDropZone');
    var fileInput  = document.getElementById('docFileInput');
    var uploadBtn  = document.getElementById('docUploadBtn');
    var fileNameEl = document.getElementById('docFileName');
    if (!dropZone) return;

    function showFile(file) {
        fileNameEl.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        fileNameEl.style.display = 'block';
        uploadBtn.style.display  = 'inline-flex';
    }

    dropZone.addEventListener('click', function() { fileInput.click(); });
    dropZone.addEventListener('dragover', function(e) { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', function() { dropZone.classList.remove('drag-over'); });
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        var file = e.dataTransfer.files[0];
        if (file) {
            var dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
            showFile(file);
        }
    });
    fileInput.addEventListener('change', function() {
        if (fileInput.files[0]) showFile(fileInput.files[0]);
    });
})();
</script>
{{-- Resource Filter Modal --}}
<div id="resourceFilterModal" class="modal-overlay" style="display:none;z-index:200">
    <div class="modal" style="max-width:540px;width:100%">
        <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between">
            <span>Configure Resource Search</span>
            <button type="button" onclick="closeResourceFilterModal()" style="background:none;border:none;font-size:22px;line-height:1;cursor:pointer;color:var(--gray-500);padding:0">&times;</button>
        </div>
        <div class="modal-body">
            {{-- Department filter --}}
            <div class="form-group" style="margin-bottom:18px">
                <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block">
                    Filter by Departments
                    <span style="font-weight:400;color:var(--gray-500)">(leave unchecked to include all)</span>
                </label>
                @if($departments->isNotEmpty())
                <div id="deptCheckboxes" style="display:flex;flex-wrap:wrap;gap:8px">
                    @foreach($departments as $dept)
                    <label style="display:flex;align-items:center;gap:5px;font-size:13px;cursor:pointer;background:var(--gray-100);border:1px solid var(--gray-200);border-radius:6px;padding:5px 10px">
                        <input type="checkbox" class="dept-filter" value="{{ $dept->id }}" onchange="schedulePreview()" style="margin:0">
                        {{ $dept->name }}
                    </label>
                    @endforeach
                </div>
                @else
                <p style="font-size:13px;color:var(--gray-500);margin:0">No departments configured.</p>
                @endif
            </div>

            {{-- Skill keywords --}}
            <div class="form-group" style="margin-bottom:18px">
                <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block">Skill Keywords</label>
                <input type="text" id="skillKeywordsInput" class="form-control"
                    placeholder="e.g. PHP, Laravel, React"
                    oninput="schedulePreview()"
                    style="width:100%">
                <div style="font-size:12px;color:var(--gray-500);margin-top:4px">
                    Pre-filled from project required skills. Employees matching ANY keyword are included.
                </div>
            </div>

            {{-- Max candidates slider --}}
            <div class="form-group" style="margin-bottom:18px">
                <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block">
                    Max Candidates to Send to AI: <strong id="maxCandidatesLabel">100</strong>
                </label>
                <input type="range" id="maxCandidatesSlider" min="10" max="500" step="10" value="100"
                    oninput="document.getElementById('maxCandidatesLabel').textContent=this.value; schedulePreview()"
                    style="width:100%;accent-color:var(--primary)">
                <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--gray-400)">
                    <span>10</span><span>500</span>
                </div>
            </div>

            {{-- Live preview --}}
            <div id="resourceFilterPreview" class="alert" style="margin-bottom:0;background:var(--blue-50,#eff6ff);border:1px solid var(--blue-200,#bfdbfe);color:var(--blue-800,#1e40af);border-radius:8px;padding:10px 14px;font-size:13px">
                <span id="previewText">Calculating...</span>
            </div>
        </div>
        <div class="modal-footer" style="padding:16px 24px;border-top:1px solid var(--gray-100);display:flex;justify-content:flex-end;gap:10px">
            <button type="button" onclick="closeResourceFilterModal()" class="btn btn-outline">Cancel</button>
            <button type="button" id="startMatchingBtn" onclick="submitResourceFilter()" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                Start Matching
            </button>
        </div>
    </div>
</div>

<script>
var _rfPreviewTimer = null;
var _rfCountUrl    = '{{ route('projects.candidateCount', $project) }}';
var _rfMatchUrl    = '{{ route('projects.findResources', $project) }}';
var _rfStatusUrl   = '{{ route('projects.matchStatus', $project) }}';
var _rfDefaultSkills = '{{ implode(', ', $project->required_skills ?? []) }}';

function openResourceFilterModal() {
    document.getElementById('skillKeywordsInput').value = _rfDefaultSkills;
    document.getElementById('maxCandidatesSlider').value = 100;
    document.getElementById('maxCandidatesLabel').textContent = '100';
    document.querySelectorAll('.dept-filter').forEach(function(cb) { cb.checked = false; });
    document.getElementById('resourceFilterModal').style.display = 'flex';
    updateResourceFilterPreview();
}

function closeResourceFilterModal() {
    document.getElementById('resourceFilterModal').style.display = 'none';
}

function schedulePreview() {
    clearTimeout(_rfPreviewTimer);
    _rfPreviewTimer = setTimeout(updateResourceFilterPreview, 450);
}

function buildFilterParams() {
    var depts = Array.from(document.querySelectorAll('.dept-filter:checked')).map(function(el) { return el.value; });
    return {
        departments: depts,
        skill_keywords: document.getElementById('skillKeywordsInput').value,
        max_candidates: document.getElementById('maxCandidatesSlider').value,
    };
}

function updateResourceFilterPreview() {
    var params = buildFilterParams();
    var qs = new URLSearchParams();
    params.departments.forEach(function(d) { qs.append('departments[]', d); });
    qs.set('skill_keywords', params.skill_keywords);
    qs.set('max_candidates', params.max_candidates);

    document.getElementById('previewText').textContent = 'Calculating...';

    fetch(_rfCountUrl + '?' + qs.toString(), { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var n = data.count || 0;
            document.getElementById('previewText').textContent =
                n + ' employee' + (n !== 1 ? 's' : '') + ' will be sent to AI for matching' +
                (n === 0 ? ' — try broadening the filters.' : '.');
            document.getElementById('startMatchingBtn').disabled = (n === 0);
        })
        .catch(function() {
            document.getElementById('previewText').textContent = 'Could not load preview.';
        });
}

function submitResourceFilter() {
    var params = buildFilterParams();

    // Switch to Resource Matches tab
    document.querySelectorAll('#projectTabs .tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });
    document.getElementById('resourcesTab').classList.add('active');
    document.getElementById('tab-resources').classList.add('active');

    closeResourceFilterModal();

    // Proxy through the existing AI analysis button (reuse its progress UI)
    var btn = document.getElementById('findResourcesBtn');
    btn.disabled = true;
    var btnText = btn.querySelector('.ai-btn-text');
    btnText.textContent = 'Processing...';
    btn.classList.add('analyzing');

    var progressEl = showAiProgress(btn, 'project', '#resourceMatchesContent');
    var since = new Date().toISOString();

    fetch(_rfMatchUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': getCSRFToken(),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(params),
    })
    .then(function(r) {
        if (!r.ok) return r.json().then(function(d) { throw new Error(d.message || 'Error'); });
        return r.json();
    })
    .then(function(data) {
        if (data.status === 'queued') {
            simulateAiProgress(progressEl, _rfStatusUrl, since, btn, '#resourceMatchesContent', 'project', 'Find Best Resources');
        } else {
            resetAiButton(btn, 'Find Best Resources');
            hideAiProgress(progressEl);
        }
    })
    .catch(function(err) {
        resetAiButton(btn, 'Find Best Resources');
        hideAiProgress(progressEl);
        showAiFlashError(err.message || 'Failed to start matching. Please try again.');
    });
}

// Close modal on overlay click
document.getElementById('resourceFilterModal').addEventListener('click', function(e) {
    if (e.target === this) closeResourceFilterModal();
});
</script>
@endsection
