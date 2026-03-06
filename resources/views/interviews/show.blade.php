@extends('layouts.app')
@section('title', 'Interview — ' . $session->candidate->first_name . ' ' . $session->candidate->last_name)

@section('content')

{{-- ===== STATE: COMPLETED — redirect to summary ===== --}}
@if($session->status === 'completed')
    <script>window.location.href = '{{ route("interviews.summary", $session) }}';</script>
@endif

{{-- ===== STATE: SCHEDULED — pre-interview view ===== --}}
@if($session->status === 'scheduled')
<div class="page-header" style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
    <a href="{{ route('interviews.index') }}" class="btn btn-outline btn-sm">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back
    </a>
    <h1 class="page-title">Interview Session</h1>
</div>

<div class="grid-2">
    {{-- Candidate Info --}}
    <div class="card">
        <div class="card-header">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <h3>Candidate</h3>
        </div>
        <div class="card-body">
            <div style="margin-bottom:12px;">
                <strong style="font-size:18px;">{{ $session->candidate->first_name }} {{ $session->candidate->last_name }}</strong>
                <p class="text-muted" style="margin:4px 0;">{{ $session->candidate->email }}</p>
            </div>
            @if($session->candidate->current_title)
                <p style="margin:4px 0;">{{ $session->candidate->current_title }}
                    @if($session->candidate->current_company) at {{ $session->candidate->current_company }} @endif
                </p>
            @endif
            @if($session->candidate->experience_years)
                <p class="text-muted">{{ $session->candidate->experience_years }} years experience</p>
            @endif
            @if($session->candidate->skills && count($session->candidate->skills))
                <div style="margin-top:12px; display:flex; flex-wrap:wrap; gap:6px;">
                    @foreach(array_slice($session->candidate->skills, 0, 10) as $skill)
                        <span class="badge badge-outline">{{ $skill }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Job & Session Info --}}
    <div class="card">
        <div class="card-header">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>
            <h3>Job Details</h3>
        </div>
        <div class="card-body">
            <div style="margin-bottom:12px;">
                <strong style="font-size:16px;">{{ $session->application->jobPosting->title ?? 'N/A' }}</strong>
            </div>
            <div class="flex-between" style="margin-bottom:8px;">
                <span class="text-muted">Stage</span>
                <span class="badge badge-info">{{ str_replace('_', ' ', ucfirst($session->interview_type)) }}</span>
            </div>
            <div class="flex-between" style="margin-bottom:8px;">
                <span class="text-muted">Interviewer</span>
                <span>{{ $session->interviewer->name ?? 'You' }}</span>
            </div>
            @if($session->scheduled_at)
            <div class="flex-between" style="margin-bottom:8px;">
                <span class="text-muted">Scheduled</span>
                <span>{{ $session->scheduled_at->format('M d, Y g:i A') }}</span>
            </div>
            @endif
            @if($session->notes)
            <div style="margin-top:16px; padding:12px; background:var(--gray-50); border-radius:var(--radius); font-size:13px;">
                <strong style="display:block; margin-bottom:4px;">Notes from HR:</strong>
                {{ $session->notes }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Required Skills --}}
@php $reqSkills = $session->application->jobPosting->required_skills ?? []; @endphp
@if(count($reqSkills))
<div class="card" style="margin-top:24px;">
    <div class="card-header">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
        <h3>Required Skills to Evaluate</h3>
    </div>
    <div class="card-body">
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            @foreach($reqSkills as $skill)
                <span class="badge" style="background:var(--primary-light); color:var(--primary);">{{ $skill }}</span>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Start Button --}}
<div style="text-align:center; margin-top:40px;">
    <p class="text-muted" style="margin-bottom:16px;">Start your meeting on Google Meet / Teams / Zoom, then click below to begin the AI assistant.</p>
    <button id="btn-start-interview" class="btn btn-primary" style="font-size:16px; padding:14px 40px;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="5 3 19 12 5 21 5 3"/></svg>
        Start Interview
    </button>
</div>

<script>
document.getElementById('btn-start-interview').addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<span class="spinner"></span> Starting...';
    fetch('{{ route("interviews.start", $session) }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(() => window.location.reload())
    .catch(() => { this.disabled = false; this.textContent = 'Start Interview'; alert('Failed to start. Please try again.'); });
});
</script>
@endif

{{-- ===== STATE: IN PROGRESS — live interview room ===== --}}
@if($session->status === 'in_progress')
<div id="interview-room"
     data-session-id="{{ $session->id }}"
     data-csrf="{{ csrf_token() }}"
     data-url-transcript="{{ url('interviews/' . $session->id . '/transcript') }}"
     data-url-questions="{{ url('interviews/' . $session->id . '/generate-questions') }}"
     data-url-evaluate="{{ url('interviews/' . $session->id . '/evaluate-answer') }}"
     data-url-question-status="{{ url('interviews/' . $session->id . '/questions') }}"
     data-url-state="{{ url('interviews/' . $session->id . '/state') }}"
     data-url-notes="{{ url('interviews/' . $session->id . '/notes') }}"
     data-url-end="{{ route('interviews.end', $session) }}"
     data-url-summary="{{ route('interviews.summary', $session) }}"
     data-url-transcribe="{{ config('ai.service_url') }}/transcribe-audio"
     data-started-at="{{ $session->started_at?->toISOString() }}">

    {{-- Topbar --}}
    <div class="ir-topbar">
        <div class="ir-topbar__left">
            <span class="pulse-dot pulse-dot--live"></span>
            <strong>{{ $session->candidate->first_name }} {{ $session->candidate->last_name }}</strong>
            <span class="text-muted" style="margin:0 8px;">|</span>
            <span>{{ $session->application->jobPosting->title ?? '' }}</span>
            <span class="badge badge-info" style="margin-left:8px;">{{ str_replace('_', ' ', ucfirst($session->interview_type)) }}</span>
        </div>
        <div class="ir-topbar__right">
            <span id="ir-timer" class="ir-timer">00:00:00</span>
            <button id="btn-end-session" class="btn btn-danger" style="padding:8px 20px; font-weight:600;">End Interview</button>
        </div>
    </div>

    {{-- Main Panels --}}
    <div class="ir-layout">
        {{-- LEFT: Transcript + Audio Controls --}}
        <div class="ir-left">
            {{-- Audio Controls --}}
            <div class="ir-audio-controls">
                <div class="ir-audio-controls__row">
                    <button id="btn-mic" class="ir-audio-btn ir-audio-btn--off" title="Toggle Microphone">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                        <span>Mic: OFF</span>
                    </button>
                    <button id="btn-system" class="ir-audio-btn ir-audio-btn--off" title="Capture System Audio (candidate's voice from meeting)">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="12" rx="2"/><path d="M1 18h22"/><path d="M8 22h8"/><path d="M12 18v4"/></svg>
                        <span>System Audio: OFF</span>
                    </button>
                </div>
                <div style="font-size:12px; color:var(--gray-500); padding-top:4px;">
                    Mic &rarr; Your voice &nbsp;|&nbsp; System Audio &rarr; Candidate's voice from meeting
                </div>
            </div>

            {{-- Transcript --}}
            <div class="ir-transcript-panel">
                <div class="ir-transcript-header">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <strong>Live Transcript</strong>
                    <span id="ir-transcript-status" class="text-muted" style="margin-left:auto; font-size:12px;">Waiting for audio...</span>
                </div>
                <div id="ir-transcript" class="ir-transcript">
                    <div class="ir-transcript-empty text-muted">
                        Enable microphone or system audio to start transcribing.
                    </div>
                </div>
                <div id="ir-interim" class="ir-interim" style="display:none;"></div>
            </div>
        </div>

        {{-- RIGHT: Context + AI --}}
        <div class="ir-right">
            {{-- Candidate Context --}}
            <div class="ir-panel">
                <div class="ir-panel__header">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Candidate
                </div>
                <div class="ir-panel__body" style="font-size:13px;">
                    <strong>{{ $session->candidate->first_name }} {{ $session->candidate->last_name }}</strong>
                    @if($session->candidate->current_title)
                        <br>{{ $session->candidate->current_title }}
                        @if($session->candidate->current_company) @ {{ $session->candidate->current_company }} @endif
                    @endif
                    @if($session->candidate->experience_years)
                        <br><span class="text-muted">{{ $session->candidate->experience_years }} yrs exp</span>
                    @endif
                    @if($session->candidate->skills && count($session->candidate->skills))
                        <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:4px;">
                            @foreach(array_slice($session->candidate->skills, 0, 8) as $skill)
                                <span class="badge badge-outline" style="font-size:11px;">{{ $skill }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- AI Questions --}}
            <div class="ir-panel" style="flex:1; display:flex; flex-direction:column;">
                <div class="ir-panel__header" style="display:flex; align-items:center; justify-content:space-between;">
                    <span>
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        AI Questions
                    </span>
                    <button id="btn-generate-questions" class="btn btn-primary btn-sm">Generate</button>
                </div>
                <div id="ir-questions" class="ir-panel__body ir-questions-list" style="flex:1; overflow-y:auto;">
                    <p class="text-muted" style="font-size:13px;">Click "Generate" to get AI-suggested interview questions based on the conversation.</p>
                </div>
            </div>

            {{-- Answer Evaluation --}}
            <div class="ir-panel">
                <div class="ir-panel__header">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
                    Answer Evaluation
                </div>
                <div id="ir-evaluation" class="ir-panel__body">
                    <p class="text-muted" style="font-size:13px;">Evaluations will appear here after you mark a question as answered.</p>
                </div>
            </div>

            {{-- Notes --}}
            <div class="ir-panel">
                <div class="ir-panel__header">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Notes
                </div>
                <div class="ir-panel__body">
                    <textarea id="ir-notes" class="form-control" rows="3" placeholder="Private notes..." style="font-size:13px;">{{ $session->notes }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/interview.js') }}"></script>
@endif

@endsection
