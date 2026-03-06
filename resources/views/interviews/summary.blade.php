@extends('layouts.app')
@section('title', 'Interview Summary — ' . $session->candidate->first_name . ' ' . $session->candidate->last_name)

@section('content')
<div class="page-header" style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
    <a href="{{ route('interviews.index') }}" class="btn btn-outline btn-sm">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back
    </a>
    <h1 class="page-title">Interview Summary</h1>
    @if($session->status !== 'completed')
        <span class="badge badge-warning">In Progress</span>
    @endif
</div>

@php
    $summary = $session->summary ?? [];
@endphp

{{-- Session Meta --}}
<div class="grid-2" style="margin-bottom:24px;">
    <div class="card">
        <div class="card-body" style="display:flex; align-items:center; gap:16px;">
            <div style="width:48px; height:48px; border-radius:50%; background:var(--primary-light); display:flex; align-items:center; justify-content:center;">
                <svg width="24" height="24" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div>
                <strong style="font-size:18px;">{{ $session->candidate->first_name }} {{ $session->candidate->last_name }}</strong>
                <p class="text-muted" style="margin:2px 0;">{{ $session->application->jobPosting->title ?? 'N/A' }}</p>
                <span class="badge badge-info">{{ str_replace('_', ' ', ucfirst($session->interview_type)) }}</span>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex-between" style="margin-bottom:8px;">
                <span class="text-muted">Interviewer</span>
                <span>{{ $session->interviewer->name ?? 'N/A' }}</span>
            </div>
            <div class="flex-between" style="margin-bottom:8px;">
                <span class="text-muted">Duration</span>
                <span>
                    @if($session->duration_seconds)
                        {{ floor($session->duration_seconds / 60) }} min {{ $session->duration_seconds % 60 }}s
                    @else
                        -
                    @endif
                </span>
            </div>
            <div class="flex-between">
                <span class="text-muted">Date</span>
                <span>{{ $session->started_at?->format('M d, Y g:i A') ?? '-' }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Interview Outcome --}}
@if($session->status === 'in_progress')
    {{-- Session still active — offer to end it here --}}
    <div class="card" style="margin-bottom:24px; border:2px solid var(--warning);">
        <div class="card-body" style="text-align:center; padding:24px;">
            <svg width="32" height="32" fill="none" stroke="var(--warning)" stroke-width="2" viewBox="0 0 24 24" style="margin-bottom:8px;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            <p style="margin-bottom:16px; font-weight:500;">This interview session is still active.</p>
            <form method="POST" action="{{ route('interviews.end', $session) }}">
                @csrf
                <button type="submit" class="btn btn-danger" style="font-size:16px; padding:12px 32px;">End Interview Now</button>
            </form>
        </div>
    </div>
@elseif($session->status === 'completed')
    @if($session->outcome)
        {{-- Outcome already recorded — show badge with option to change --}}
        <div class="card" style="margin-bottom:24px;">
            <div class="card-body" style="display:flex; align-items:center; gap:16px; padding:20px 24px;">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
                <div style="flex:1;">
                    <span class="text-muted" style="font-size:13px;">Interview Outcome</span>
                    <div style="margin-top:4px;">
                        @if($session->outcome === 'advanced')
                            <span class="badge" style="background:#dcfce7; color:#166534; font-size:14px; padding:6px 16px;">Advanced to Next Round</span>
                        @elseif($session->outcome === 'waitlisted')
                            <span class="badge" style="background:#fef3c7; color:#92400e; font-size:14px; padding:6px 16px;">Waitlisted</span>
                        @elseif($session->outcome === 'rejected')
                            <span class="badge" style="background:#fee2e2; color:#991b1b; font-size:14px; padding:6px 16px;">Rejected</span>
                        @endif
                    </div>
                </div>
                <div style="display:flex; gap:8px; margin-left:auto;">
                    @if($session->outcome === 'waitlisted')
                    <form method="POST" action="{{ route('interviews.reopen', $session) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Reopen this interview? A new session will be created for this candidate with the same interviewer.')">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:middle;"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                            Reopen Interview
                        </button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('interviews.revertOutcome', $session) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Revert this outcome? The application stage will be restored and you can record a new decision.')">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:middle;"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                            Change Decision
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @else
        {{-- Outcome not yet recorded — show decision form --}}
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                <h3>Complete Interview</h3>
            </div>
            <div class="card-body">
                <p class="text-muted" style="margin-bottom:16px;">Record your decision for this candidate. This will update their application status.</p>
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px;">
                    {{-- Advance --}}
                    <form method="POST" action="{{ route('interviews.complete', $session) }}">
                        @csrf
                        <input type="hidden" name="outcome" value="advanced">
                        <button type="submit" class="ir-outcome-btn ir-outcome-btn--advance" onclick="return confirm('Advance this candidate to the next round?')">
                            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
                            <strong>Advance</strong>
                            <span>Move to next round</span>
                        </button>
                    </form>
                    {{-- Waitlist --}}
                    <form method="POST" action="{{ route('interviews.complete', $session) }}">
                        @csrf
                        <input type="hidden" name="outcome" value="waitlisted">
                        <button type="submit" class="ir-outcome-btn ir-outcome-btn--waitlist" onclick="return confirm('Waitlist this candidate for later review?')">
                            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            <strong>Waitlist</strong>
                            <span>Review after all interviews</span>
                        </button>
                    </form>
                    {{-- Reject --}}
                    <form method="POST" action="{{ route('interviews.complete', $session) }}">
                        @csrf
                        <input type="hidden" name="outcome" value="rejected">
                        <button type="submit" class="ir-outcome-btn ir-outcome-btn--reject" onclick="return confirm('Reject this candidate?')">
                            <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                            <strong>Reject</strong>
                            <span>Eliminate candidate</span>
                        </button>
                    </form>
                </div>
                <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--gray-100); text-align:center;">
                    <form method="POST" action="{{ route('interviews.reopen', $session) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Reopen this interview? A new session will be created for this candidate with the same interviewer.')">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:middle;"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                            Reopen Interview Instead
                        </button>
                    </form>
                    <p class="text-muted" style="font-size:12px; margin:8px 0 0;">Schedule a new interview session for this candidate without recording a decision.</p>
                </div>
            </div>
        </div>
    @endif
@endif

{{-- AI Summary Section --}}
<div id="ir-ai-summary-section"
     data-generate-url="{{ route('interviews.generateSummary', $session) }}"
     data-status-url="{{ route('interviews.summaryStatus', $session) }}"
     data-csrf="{{ csrf_token() }}"
     data-has-summary="{{ !empty($summary) && empty($summary['_error']) ? '1' : '0' }}"
     data-has-transcripts="{{ $session->transcripts->isNotEmpty() ? '1' : '0' }}">

@if(!empty($summary) && empty($summary['_error']) && empty($summary['_insufficient_data']))
    {{-- AI Summary exists — show results --}}
    <div id="ir-ai-results">
        @if(!empty($summary['_low_data']))
        <div style="padding:12px 20px; background:#fef3c7; border-radius:var(--radius); margin-bottom:16px; font-size:13px; color:#92400e;">
            <strong>Limited data available.</strong> This summary was generated from a small amount of transcript/question data. A manual summary is recommended to supplement it.
        </div>
        @endif

        {{-- AI Analysis Header --}}
        <div class="card" style="margin-bottom:24px;">
            <div class="card-body" style="display:flex; align-items:center; gap:12px; padding:20px 24px;">
                <div style="width:40px; height:40px; border-radius:50%; background:var(--primary-light); display:flex; align-items:center; justify-content:center;">
                    <svg width="20" height="20" fill="none" stroke="var(--primary)" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                </div>
                <div>
                    <strong style="font-size:16px;">AI Interview Analysis</strong>
                    <p class="text-muted" style="margin:2px 0 0; font-size:13px;">Factual observations and skill assessment — final judgment rests with the interviewer.</p>
                </div>
            </div>
        </div>

        {{-- Scores --}}
        @php
            $scores = [
                'Technical Depth' => $summary['technical_depth'] ?? null,
                'Communication' => $summary['communication_score'] ?? null,
                'Problem Solving' => $summary['problem_solving_score'] ?? null,
            ];
        @endphp
        @if(collect($scores)->filter()->isNotEmpty())
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>
                <h3>Scores</h3>
            </div>
            <div class="card-body">
                @foreach($scores as $label => $score)
                    @if(!is_null($score))
                    <div style="margin-bottom:16px;">
                        <div class="flex-between" style="margin-bottom:4px;">
                            <span>{{ $label }}</span>
                            <strong>{{ $score }}/100</strong>
                        </div>
                        <div style="height:8px; background:var(--gray-100); border-radius:4px; overflow:hidden;">
                            <div style="height:100%; width:{{ $score }}%; background:{{ $score >= 70 ? 'var(--success)' : ($score >= 40 ? 'var(--warning)' : 'var(--danger)') }}; border-radius:4px; transition:width 0.5s;"></div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- Strengths & Concerns --}}
        @if(!empty($summary['strengths']) || !empty($summary['concerns']))
        <div class="grid-2" style="margin-bottom:24px;">
            @if(!empty($summary['strengths']))
            <div class="card">
                <div class="card-header">
                    <svg width="20" height="20" fill="none" stroke="var(--success)" stroke-width="2" viewBox="0 0 24 24"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14z"/><path d="M7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
                    <h3>Strengths</h3>
                </div>
                <div class="card-body">
                    <ul style="margin:0; padding-left:20px;">
                        @foreach($summary['strengths'] as $s)
                            <li style="margin-bottom:6px;">{{ $s }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
            @if(!empty($summary['concerns']))
            <div class="card">
                <div class="card-header">
                    <svg width="20" height="20" fill="none" stroke="var(--warning)" stroke-width="2" viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <h3>Concerns</h3>
                </div>
                <div class="card-body">
                    <ul style="margin:0; padding-left:20px;">
                        @foreach($summary['concerns'] as $c)
                            <li style="margin-bottom:6px;">{{ $c }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Narrative --}}
        @if(!empty($summary['narrative']))
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <h3>Hiring Narrative</h3>
            </div>
            <div class="card-body">
                <p style="line-height:1.7;">{{ $summary['narrative'] }}</p>
            </div>
        </div>
        @endif

        {{-- Key Moments --}}
        @if(!empty($summary['key_moments']))
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <h3>Key Moments</h3>
            </div>
            <div class="card-body">
                @foreach($summary['key_moments'] as $moment)
                    <div style="padding:10px 0; border-bottom:1px solid var(--gray-100);">
                        {{ is_array($moment) ? ($moment['description'] ?? json_encode($moment)) : $moment }}
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
@else
    {{-- No AI summary yet — show generate button or no-data message --}}
    <div id="ir-ai-placeholder" class="card" style="margin-bottom:24px;">
        <div class="card-body" style="text-align:center; padding:40px;">
            @if(!empty($summary['_insufficient_data']) || ($session->transcripts->isEmpty() && $session->questions->isEmpty()))
                <svg width="40" height="40" fill="none" stroke="var(--gray-300)" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px;"><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><circle cx="12" cy="12" r="10"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <p style="font-weight:500; margin-bottom:8px;">Not enough data for AI summary</p>
                <p class="text-muted" style="font-size:13px;">No transcript or question data was captured during this interview. A manual summary is recommended below.</p>
            @else
                <svg width="40" height="40" fill="none" stroke="var(--primary)" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 16px;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                @if(!empty($summary['_error']))
                    <p style="font-weight:500; margin-bottom:8px; color:var(--danger);">AI summary generation failed</p>
                    <p class="text-muted" style="font-size:13px; margin-bottom:16px;">{{ $summary['narrative'] ?? 'An error occurred. You can retry or write a manual summary below.' }}</p>
                @else
                    <p style="font-weight:500; margin-bottom:8px;">AI summary not yet generated</p>
                    <p class="text-muted" style="font-size:13px; margin-bottom:16px;">{{ $session->transcripts->count() }} transcript segments and {{ $session->questions->count() }} questions available for analysis.</p>
                @endif
                @if($session->status === 'completed')
                    <button type="button" id="btn-generate-summary" class="btn btn-primary" style="font-size:15px; padding:10px 28px;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:middle;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        Generate AI Summary
                    </button>
                @endif
            @endif
        </div>
    </div>

    {{-- Progress bar (hidden, shown by JS) --}}
    <div id="ir-ai-progress" class="card" style="margin-bottom:24px; display:none;">
        <div class="card-body ai-progress">
            <div class="ai-progress-spinner" style="margin:0 auto 16px;"><div class="spinner"></div></div>
            <div id="ir-ai-progress-percent" class="ai-progress-percent">0%</div>
            <div class="ai-progress-bar" style="margin:0 auto;"><div id="ir-ai-progress-fill" class="ai-progress-fill" style="width:0%;"></div></div>
            <div id="ir-ai-progress-phase" class="ai-progress-phase">Starting AI analysis...</div>
        </div>
    </div>
@endif

{{-- Interviewer's Own Summary (always visible) --}}
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        <h3>Your Summary</h3>
        <span id="ir-manual-save-status" class="text-muted" style="margin-left:auto; font-size:12px;"></span>
    </div>
    <div class="card-body">
        <textarea id="ir-manual-summary" class="form-control" rows="4" placeholder="Write your own summary, observations, or notes about this interview..."
            data-url="{{ route('interviews.saveManualSummary', $session) }}"
            data-csrf="{{ csrf_token() }}"
            style="font-size:14px;">{{ $summary['manual_notes'] ?? '' }}</textarea>
        <div style="margin-top:8px; display:flex; justify-content:space-between; align-items:center;">
            <span class="text-muted" style="font-size:12px;">Auto-saves as you type</span>
            <button type="button" id="btn-save-manual-summary" class="btn btn-outline btn-sm">Save</button>
        </div>
    </div>
</div>
</div>

{{-- Questions & Evaluations --}}
@if($session->questions->isNotEmpty())
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <h3>Interview Questions</h3>
    </div>
    <div class="card-body" style="padding:0;">
        @foreach($session->questions as $q)
        <div style="padding:16px 20px; border-bottom:1px solid var(--gray-100);">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <span class="badge badge-outline">{{ ucfirst($q->question_type) }}</span>
                <span class="badge" style="background:{{ $q->difficulty === 'hard' ? 'var(--danger-light)' : ($q->difficulty === 'medium' ? 'var(--warning-light)' : 'var(--success-light)') }}; color:{{ $q->difficulty === 'hard' ? 'var(--danger)' : ($q->difficulty === 'medium' ? 'var(--warning)' : 'var(--success)') }};">{{ ucfirst($q->difficulty) }}</span>
                @if($q->skill_area)
                    <span class="badge" style="background:var(--primary-light); color:var(--primary);">{{ $q->skill_area }}</span>
                @endif
                <span class="badge" style="margin-left:auto; background:{{ $q->status === 'answered' ? 'var(--success-light)' : 'var(--gray-100)' }}; color:{{ $q->status === 'answered' ? 'var(--success)' : 'var(--gray-500)' }};">{{ ucfirst($q->status) }}</span>
            </div>
            <p style="margin:0 0 8px; font-weight:500;">{{ $q->question_text }}</p>
            @if($q->answer_text)
                <div style="margin-top:8px; padding:10px; background:var(--gray-50); border-radius:var(--radius); font-size:13px;">
                    <strong>Answer:</strong> {{ $q->answer_text }}
                </div>
            @endif
            @if($q->evaluation)
                <div style="margin-top:8px; padding:10px; background:var(--primary-light); border-radius:var(--radius); font-size:13px;">
                    <strong>Score: {{ $q->evaluation['score'] ?? '-' }}/100</strong>
                    @if(!empty($q->evaluation['depth']))
                        — <em>{{ ucfirst($q->evaluation['depth']) }}</em>
                    @endif
                    @if(!empty($q->evaluation['feedback']))
                        <p style="margin:6px 0 0;">{{ $q->evaluation['feedback'] }}</p>
                    @endif
                </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Full Transcript --}}
@if($session->transcripts->isNotEmpty())
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <h3>Full Transcript</h3>
        <span class="text-muted" style="margin-left:auto; font-size:13px;">{{ $session->transcripts->count() }} segments</span>
    </div>
    <div class="card-body" style="max-height:500px; overflow-y:auto;">
        @foreach($session->transcripts as $t)
        <div class="ir-summary-transcript-entry {{ $t->speaker === 'interviewer' ? 'ir-summary-transcript-entry--you' : '' }}">
            <div class="ir-summary-transcript-meta">
                <strong>{{ $t->speaker === 'interviewer' ? 'You' : 'Candidate' }}</strong>
                <span class="text-muted">{{ gmdate('H:i:s', $t->offset_seconds) }}</span>
            </div>
            <p style="margin:4px 0 0;">{{ $t->text }}</p>
        </div>
        @endforeach
    </div>
</div>
@endif

@endsection
