@extends('layouts.app')
@section('title', 'Application Details')
@section('page-title', 'Application Details')
@section('content')

{{-- Profile Hero --}}
<div class="profile-hero">
    <div class="avatar-lg">{{ strtoupper(substr($application->candidate->first_name, 0, 1) . substr($application->candidate->last_name, 0, 1)) }}</div>
    <div class="profile-info">
        <h1>{{ $application->candidate->full_name }}</h1>
        <div class="profile-meta">
            @include('components.stage-badge', ['stage' => $application->stage])
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
                {{ $application->jobPosting->title }}
            </span>
            @if($application->applied_at)
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Applied {{ $application->applied_at->format('M d, Y') }}
            </span>
            @endif
            @if($application->ai_score)
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                AI Score: <strong>{{ number_format($application->ai_score, 1) }}</strong>
            </span>
            @endif
        </div>
    </div>
</div>

{{-- Action Bar --}}
<div class="action-bar">
    <a href="{{ route('candidates.show', $application->candidate_id) }}" class="action-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        View Candidate
    </a>
    <a href="{{ route('jobs.show', $application->job_posting_id) }}" class="action-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
        View Job
    </a>
    <form method="POST" action="{{ route('applications.analyze', $application) }}" style="display:inline">
        @csrf
        <button type="submit" class="action-btn action-primary">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            Run AI Analysis
        </button>
    </form>
</div>

<div class="grid-2">
    {{-- Application Info + Stage Management --}}
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Application Info
            </span>
        </div>
        <div class="card-body">
            <div class="detail-list">
                <div class="detail-row">
                    <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
                    <div class="row-content"><div class="row-label">Candidate</div><div class="row-value"><a href="{{ route('candidates.show', $application->candidate_id) }}">{{ $application->candidate->full_name }}</a></div></div>
                </div>
                <div class="detail-row">
                    <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg></div>
                    <div class="row-content"><div class="row-label">Job Position</div><div class="row-value"><a href="{{ route('jobs.show', $application->job_posting_id) }}">{{ $application->jobPosting->title }}</a></div></div>
                </div>
                <div class="detail-row">
                    <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                    <div class="row-content"><div class="row-label">Applied On</div><div class="row-value">{{ $application->applied_at?->format('M d, Y') }}</div></div>
                </div>
                <div class="detail-row">
                    <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
                    <div class="row-content"><div class="row-label">Resume</div><div class="row-value">{{ $application->resume?->file_name ?? 'N/A' }}</div></div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="section-label">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Update Stage
            </div>
            <form method="POST" action="{{ route('applications.updateStage', $application) }}">
                @csrf @method('PUT')
                <div class="flex gap-10" style="margin-bottom:12px">
                    <select name="stage" class="form-control">
                        @foreach(['applied','ai_shortlisted','hr_screening','technical_round_1','technical_round_2','offer','hired','rejected'] as $s)
                        <option value="{{ $s }}" {{ $application->stage === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
                <div class="form-group" style="margin-bottom:8px">
                    <input type="text" name="stage_notes" class="form-control" placeholder="Stage notes (optional)" value="{{ $application->stage_notes }}">
                </div>
                @if($application->stage === 'rejected')
                <div class="form-group" style="margin-bottom:0">
                    <input type="text" name="rejection_reason" class="form-control" placeholder="Rejection reason" value="{{ $application->rejection_reason }}">
                </div>
                @endif
            </form>
        </div>
    </div>

    {{-- AI Analysis Card --}}
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                AI Analysis
            </span>
        </div>
        <div class="card-body">
            @if($application->ai_analysis)
            @php $ai = $application->ai_analysis; @endphp

            <div class="ai-score-hero">
                <div class="score {{ ($application->ai_score ?? 0) >= 70 ? 'high' : (($application->ai_score ?? 0) >= 40 ? 'medium' : 'low') }}">
                    {{ number_format($application->ai_score ?? $ai['overall_score'] ?? 0, 1) }}
                </div>
                <div class="score-label">Overall Score out of 100</div>
                @if(isset($ai['recommendation']))
                <div class="mt-1">
                    <span class="badge badge-blue">{{ ucwords(str_replace('_',' ',$ai['recommendation'])) }}</span>
                    @if($application->ai_score_version)
                    <span class="badge badge-secondary" style="margin-left:4px">v{{ $application->ai_score_version }}</span>
                    @endif
                </div>
                @endif
            </div>

            {{-- Show expanded signals if ai_signals present, otherwise fall back to legacy 4-bar --}}
            @if($application->ai_signals)
                @php
                    $coreSignals = [
                        'skill_match_score' => 'Skill Match',
                        'experience_score' => 'Experience',
                        'relevance_score' => 'Relevance',
                        'authenticity_score' => 'Authenticity',
                    ];
                    $qualitySignals = [
                        'keyword_density' => 'Keyword Density',
                        'generic_language' => 'Generic Language',
                        'verifiable_evidence' => 'Verifiable Evidence',
                        'career_progression' => 'Career Progression',
                        'quantified_claims' => 'Quantified Claims',
                    ];
                    $signals = $application->ai_signals;
                @endphp
                <div class="signal-group-label">Core Signals</div>
                @foreach($coreSignals as $key => $label)
                @if(isset($signals[$key]))
                <div class="skill-bar">
                    <span class="label">{{ $label }}</span>
                    <div class="bar"><div class="fill" style="width:{{ $signals[$key] }}%"></div></div>
                    <span class="percent">{{ number_format($signals[$key], 0) }}%</span>
                </div>
                @endif
                @endforeach

                @php $hasQuality = collect($qualitySignals)->keys()->filter(fn($k) => isset($signals[$k]) && $signals[$k] != 50)->isNotEmpty(); @endphp
                @if($hasQuality)
                <div class="signal-group-label" style="margin-top:12px">Quality Signals</div>
                @foreach($qualitySignals as $key => $label)
                @if(isset($signals[$key]))
                <div class="skill-bar">
                    <span class="label">{{ $label }}</span>
                    <div class="bar"><div class="fill quality-fill" style="width:{{ $signals[$key] }}%"></div></div>
                    <span class="percent">{{ number_format($signals[$key], 0) }}%</span>
                </div>
                @endif
                @endforeach
                @endif
            @else
                @foreach(['skill_match_score'=>'Skill Match','experience_score'=>'Experience','relevance_score'=>'Relevance','authenticity_score'=>'Authenticity'] as $key=>$label)
                @if(isset($ai[$key]))
                <div class="skill-bar">
                    <span class="label">{{ $label }}</span>
                    <div class="bar"><div class="fill" style="width:{{ $ai[$key] }}%"></div></div>
                    <span class="percent">{{ number_format($ai[$key], 0) }}%</span>
                </div>
                @endif
                @endforeach
            @endif

            @if(isset($ai['explanation']))
            <div class="notes-block mt-2">
                <p>{{ $ai['explanation'] }}</p>
            </div>
            @endif

            @if((isset($ai['strengths']) && count($ai['strengths'])) || (isset($ai['concerns']) && count($ai['concerns'])))
            <div class="ai-strengths-concerns">
                @if(isset($ai['strengths']) && count($ai['strengths']))
                <div class="sc-section strengths">
                    <h4>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Strengths
                    </h4>
                    <ul class="sc-list">@foreach($ai['strengths'] as $s)<li>{{ $s }}</li>@endforeach</ul>
                </div>
                @endif
                @if(isset($ai['concerns']) && count($ai['concerns']))
                <div class="sc-section concerns">
                    <h4>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Concerns
                    </h4>
                    <ul class="sc-list">@foreach($ai['concerns'] as $c)<li>{{ $c }}</li>@endforeach</ul>
                </div>
                @endif
            </div>
            @endif

            <div class="text-sm text-muted mt-2" style="display:flex;align-items:center;gap:6px">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Analyzed: {{ $application->ai_analyzed_at?->format('M d, Y H:i') }}
            </div>
            @else
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                <p>No AI analysis yet</p>
                <div class="empty-hint">Click "Run AI Analysis" to generate insights</div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Interview Feedback --}}
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Interview Feedback
        </span>
        <button onclick="openModal('feedbackModal')" class="btn btn-sm btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Feedback
        </button>
    </div>
    <table>
        <thead><tr><th>Stage</th><th>Interviewer</th><th>Rating</th><th>Recommendation</th><th>Notes</th><th></th></tr></thead>
        <tbody>
        @forelse($application->feedback as $fb)
        <tr>
            <td>{{ ucwords(str_replace('_',' ',$fb->stage)) }}</td>
            <td>{{ $fb->interviewer->name }}</td>
            <td>
                <div class="star-rating">
                    @for($i = 1; $i <= 5; $i++)
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="{{ $i <= $fb->rating ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="{{ $i > $fb->rating ? 'empty' : '' }}"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
            </td>
            <td>@if($fb->recommendation) @include('components.stage-badge', ['stage' => $fb->recommendation]) @endif</td>
            <td class="text-sm">{{ \Illuminate\Support\Str::limit($fb->notes, 80) }}</td>
            <td>
                <form method="POST" action="{{ route('feedback.destroy', $fb) }}" style="display:inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this feedback?')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        Delete
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted">No feedback yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Feedback Modal --}}
<div class="modal-overlay" id="feedbackModal">
    <div class="modal">
        <div class="modal-header">Add Interview Feedback</div>
        <form method="POST" action="{{ route('feedback.store', $application) }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>Stage</label>
                    <select name="stage" class="form-control">
                        @foreach(['hr_screening','technical_round_1','technical_round_2','offer'] as $s)
                        <option value="{{ $s }}">{{ ucwords(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Rating <span style="color:var(--danger)">*</span></label>
                        <input type="hidden" name="rating" id="feedbackRatingInput" required>
                        <div class="star-picker" id="feedbackStarPicker">
                            @for($i = 1; $i <= 5; $i++)
                            <button type="button" class="star-btn" data-value="{{ $i }}" title="{{ $i }} star{{ $i > 1 ? 's' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </button>
                            @endfor
                            <span class="star-picker-label" id="starPickerLabel">Select rating</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Recommendation <span style="color:var(--danger)">*</span></label>
                        <select name="recommendation" class="form-control" required>
                            <option value="">Select...</option>
                            @foreach(['strong_yes'=>'Strong Yes','yes'=>'Yes','neutral'=>'Neutral','no'=>'No','strong_no'=>'Strong No'] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group"><label>Strengths</label><textarea name="strengths" class="form-control" rows="2" placeholder="Key strengths observed..."></textarea></div>
                <div class="form-group"><label>Weaknesses</label><textarea name="weaknesses" class="form-control" rows="2" placeholder="Areas of concern..."></textarea></div>
                <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('feedbackModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </div>
        </form>
    </div>
</div>
@endsection
