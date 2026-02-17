@extends('layouts.app')
@section('title', $job->title)
@section('page-title', 'Job Details')
@section('content')

{{-- Profile Hero --}}
<div class="profile-hero">
    <div class="avatar-lg">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
    </div>
    <div class="profile-info">
        <h1>{{ $job->title }}</h1>
        <div class="profile-meta">
            @include('components.stage-badge', ['stage' => $job->status])
            @if($job->department)
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                {{ $job->department->name }}
            </span>
            @endif
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ ucwords(str_replace('_',' ',$job->employment_type)) }}
            </span>
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                {{ $job->location ?? 'Remote' }}
            </span>
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                {{ $job->min_experience }}-{{ $job->max_experience }} yrs
            </span>
        </div>
    </div>
</div>

{{-- Action Bar --}}
<div class="action-bar">
    <a href="{{ route('jobs.edit', $job) }}" class="action-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit Job
    </a>
    @if($job->jd_file_path)
    <a href="{{ route('jobs.downloadJd', $job) }}" class="action-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download JD
    </a>
    @endif
    <button onclick="openModal('addApplicationModal')" class="action-btn action-primary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
        Add Application
    </button>
    <button onclick="openModal('bulkApplyModal')" class="action-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        Bulk Add Candidates
    </button>
    <form method="POST" action="{{ route('jobs.updateStatus', $job) }}" style="display:inline" class="flex gap-8">
        @csrf
        <select name="status" onchange="this.form.submit()" class="form-control" style="width:auto;min-height:44px;padding:10px 40px 10px 14px">
            @foreach(['draft','open','on_hold','closed'] as $s)
            <option value="{{ $s }}" {{ $job->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- Tabs --}}
<div class="tabs" data-tabs>
    <button class="tab active" data-tab="tab-details">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Details
    </button>
    <button class="tab" data-tab="tab-candidates">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Candidates ({{ $job->applications->count() }})
    </button>
</div>

{{-- Tab: Details --}}
<div class="tab-content active" id="tab-details">
    <div class="grid-2">
        {{-- Job Details Card --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Job Details
                </span>
            </div>
            <div class="card-body">
                <div class="detail-list">
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></div>
                        <div class="row-content"><div class="row-label">Department</div><div class="row-value">{{ $job->department?->name ?? 'N/A' }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                        <div class="row-content"><div class="row-label">Employment Type</div><div class="row-value">{{ ucwords(str_replace('_',' ',$job->employment_type)) }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
                        <div class="row-content"><div class="row-label">Experience</div><div class="row-value">{{ $job->min_experience }}-{{ $job->max_experience }} years</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                        <div class="row-content"><div class="row-label">Location</div><div class="row-value">{{ $job->location ?? 'N/A' }}</div></div>
                    </div>
                    @if($job->salary_min || $job->salary_max)
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
                        <div class="row-content"><div class="row-label">Salary Range</div><div class="row-value">{{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }}</div></div>
                    </div>
                    @endif
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                        <div class="row-content"><div class="row-label">Created By</div><div class="row-value">{{ $job->creator?->name }}</div></div>
                    </div>
                </div>
                @if($job->required_skills && count($job->required_skills))
                <div class="mt-2">
                    <div class="section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        Required Skills
                    </div>
                    <div class="tags">@foreach($job->required_skills as $skill)<span class="tag">{{ $skill }}</span>@endforeach</div>
                </div>
                @endif
                @if($job->skill_experience_details)
                <div class="mt-2">
                    <div class="section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Skill Experience Details
                    </div>
                    <div class="content-prose">{!! nl2br(e($job->skill_experience_details)) !!}</div>
                </div>
                @endif
                @if($job->notes)
                <div class="notes-block" style="margin-top: 16px">
                    <div class="section-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        Notes
                    </div>
                    <p>{{ $job->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Description Card --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Description
                </span>
            </div>
            <div class="card-body">
                <div class="content-prose">{{ $job->description }}</div>
                @if($job->key_responsibilities)
                <div class="divider"></div>
                <div class="section-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="12" y1="8" x2="12" y2="16"/></svg>
                    Key Responsibilities
                </div>
                <div class="content-prose">{!! nl2br(e($job->key_responsibilities)) !!}</div>
                @endif
                @if($job->requirements)
                <div class="divider"></div>
                <div class="section-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    Requirements
                </div>
                <div class="content-prose">{{ $job->requirements }}</div>
                @endif
                @if($job->expectations)
                <div class="divider"></div>
                <div class="section-label">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Expectations
                </div>
                <div class="content-prose">{!! nl2br(e($job->expectations)) !!}</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tab: Candidates --}}
<div class="tab-content" id="tab-candidates">
    {{-- Compare Toolbar (hidden by default) --}}
    <div class="compare-toolbar" id="compareToolbar" style="display:none">
        <span id="compareCount">0 candidates selected</span>
        <button type="button" class="btn btn-primary btn-sm" id="compareBtn" onclick="openComparisonModal()" disabled>
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Compare
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="clearComparison()">Clear</button>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Applications ({{ $job->applications->count() }})
            </span>
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width:36px"></th>
                    <th>Candidate</th>
                    <th>Stage</th>
                    <th>AI Score</th>
                    <th>Applied</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($job->applications as $app)
            <tr>
                <td>
                    @if($app->ai_analysis)
                    <input type="checkbox" class="compare-checkbox" onchange="updateCompareSelection()"
                        data-app-id="{{ $app->id }}"
                        data-candidate-name="{{ $app->candidate->full_name }}"
                        data-score="{{ $app->ai_score ?? 0 }}"
                        data-recommendation="{{ $app->ai_analysis['recommendation'] ?? '' }}"
                        data-strengths="{{ json_encode($app->ai_analysis['strengths'] ?? []) }}"
                        data-concerns="{{ json_encode($app->ai_analysis['concerns'] ?? []) }}"
                        data-explanation="{{ $app->ai_analysis['explanation'] ?? '' }}"
                        data-skill-match="{{ $app->ai_signals['skill_match_score'] ?? $app->ai_analysis['skill_match_score'] ?? '' }}"
                        data-experience="{{ $app->ai_signals['experience_score'] ?? $app->ai_analysis['experience_score'] ?? '' }}"
                        data-relevance="{{ $app->ai_signals['relevance_score'] ?? $app->ai_analysis['relevance_score'] ?? '' }}"
                        data-authenticity="{{ $app->ai_signals['authenticity_score'] ?? $app->ai_analysis['authenticity_score'] ?? '' }}"
                    >
                    @endif
                </td>
                <td><a href="{{ route('candidates.show', $app->candidate_id) }}">{{ $app->candidate->full_name }}</a></td>
                <td>
                    <select class="stage-select stage-{{ $app->stage }}"
                        data-url="{{ route('applications.updateStage', $app) }}"
                        data-original="{{ $app->stage }}"
                        onchange="updateStageInline(this)">
                        @foreach(['applied','ai_shortlisted','hr_screening','technical_round_1','technical_round_2','offer','hired','rejected'] as $st)
                        <option value="{{ $st }}" {{ $app->stage === $st ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $st)) }}</option>
                        @endforeach
                    </select>
                </td>
                <td id="aiScore-{{ $app->id }}">
                    @if($app->ai_score)
                    <span class="score {{ $app->ai_score >= 70 ? 'high' : ($app->ai_score >= 40 ? 'medium' : 'low') }}" style="font-size:16px">{{ number_format($app->ai_score, 1) }}</span>
                    @else <span class="text-muted">-</span> @endif
                </td>
                <td class="text-sm text-muted">{{ $app->applied_at?->format('M d, Y') }}</td>
                <td>
                    <div class="table-actions">
                        @if($app->ai_analysis)
                        <button type="button" class="btn btn-sm btn-secondary expand-toggle" data-target="expand-{{ $app->id }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                            Expand
                        </button>
                        @endif
                        <a href="{{ route('applications.show', $app) }}" class="btn btn-sm btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            View
                        </a>
                        <button type="button" class="btn btn-sm btn-primary ai-analyze-btn"
                            data-url="{{ route('applications.analyze', $app) }}"
                            data-status-url="{{ route('applications.analysisStatus', $app) }}"
                            data-target="#aiResult-{{ $app->id }}"
                            data-score-target="#aiScore-{{ $app->id }}"
                            data-row-id="{{ $app->id }}"
                            data-context="job-candidate">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            <span class="ai-btn-text">AI Analyze</span>
                        </button>
                    </div>
                </td>
            </tr>
            {{-- Expandable AI Analysis Row (always rendered so JS can inject results) --}}
            <tr class="expandable-row" id="expand-{{ $app->id }}" style="display:none">
                <td colspan="6">
                    <div class="inline-analysis-panel" id="aiResult-{{ $app->id }}">
                        @if($app->ai_analysis)
                            @include('components.ai-analysis-inline', ['application' => $app, 'compact' => true])
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted">No applications yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Add Application Modal --}}
<div class="modal-overlay" id="addApplicationModal">
    <div class="modal" style="max-width:540px">
        <div class="modal-header">Add Application</div>
        <form method="POST" action="{{ route('applications.store', $job) }}" id="addApplicationForm">
            @csrf
            <input type="hidden" name="candidate_id" id="selectedCandidateId">
            <input type="hidden" name="resume_id" id="selectedResumeId">
            <div class="modal-body">
                <div class="form-group" id="candidateSearchGroup">
                    <label>Search Candidate</label>
                    <div class="candidate-search-wrap">
                        <input type="text" id="candidateSearchInput" class="form-control" placeholder="Type candidate name or email..." autocomplete="off" data-url="{{ route('candidates.search') }}">
                        <div class="candidate-search-results" id="candidateSearchResults"></div>
                    </div>
                </div>
                <div id="selectedCandidateCard" class="selected-candidate-card" style="display:none">
                    <div class="selected-candidate-info">
                        <div class="selected-candidate-name" id="selectedCandidateName"></div>
                        <div class="selected-candidate-meta" id="selectedCandidateMeta"></div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary" id="clearCandidateBtn">Change</button>
                </div>
                <div class="form-group" id="resumeSelectGroup" style="display:none">
                    <label>Resume</label>
                    <select id="resumeSelect" class="form-control"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addApplicationModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" id="addApplicationSubmit" disabled>Add Application</button>
            </div>
        </form>
    </div>
</div>

{{-- Comparison Modal --}}
<div class="modal-overlay" id="comparisonModal">
    <div class="modal" style="max-width:90vw;width:900px">
        <div class="modal-header">
            Candidate Comparison
            <button type="button" onclick="closeModal('comparisonModal')" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gray-500);margin-left:auto">&times;</button>
        </div>
        <div class="modal-body" id="comparisonContent" style="overflow-x:auto">
            {{-- Populated by JavaScript --}}
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeModal('comparisonModal')" class="btn btn-secondary">Close</button>
        </div>
    </div>
</div>

{{-- Bulk Add Candidates Modal --}}
<div class="modal-overlay" id="bulkApplyModal">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            Bulk Add Candidates & Apply
            <button type="button" onclick="closeModal('bulkApplyModal')" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gray-500);margin-left:auto">&times;</button>
        </div>
        <form method="POST" action="{{ route('applications.bulkApply', $job) }}" enctype="multipart/form-data" id="bulkApplyForm">
            @csrf
            <div class="modal-body">
                <p class="text-muted" style="margin-bottom:16px">
                    Upload resumes (PDF or DOCX) to create candidates and automatically apply them to
                    <strong>{{ $job->title }}</strong>. Up to 20 files at once.
                </p>

                <input type="file" id="bulkApplyFileInput" name="resumes[]" multiple accept=".pdf,.docx" style="display:none">

                <div class="ai-upload-area" id="bulkApplyUploadArea">
                    <div class="upload-content">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--gray-400)"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        <p style="margin:8px 0 4px;font-weight:500;color:var(--gray-700)">Drop resumes here or click to select</p>
                        <p class="text-muted" style="font-size:13px">PDF or DOCX, up to 10MB each</p>
                    </div>
                </div>

                <div id="bulkApplyFileList" class="bulk-file-list" style="display:none"></div>
                <div id="bulkApplyFileCount" class="bulk-file-count" style="display:none">
                    <span id="bulkApplyFileCountText">0 files selected</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('bulkApplyModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" id="bulkApplySubmitBtn" disabled>
                    Upload & Apply Candidates
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
