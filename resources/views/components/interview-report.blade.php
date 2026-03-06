{{-- Consolidated Interview Report — shows on application show page --}}
@if($interviewSessions->isNotEmpty())
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Interview Sessions
        </span>
        <span class="text-muted" style="margin-left:auto; font-size:13px;">{{ $interviewSessions->count() }} session{{ $interviewSessions->count() > 1 ? 's' : '' }}</span>
    </div>

    @foreach($interviewSessions->sortByDesc('created_at') as $session)
    @php $summary = $session->summary ?? []; @endphp
    <div style="padding:16px 24px; border-bottom:1px solid var(--gray-100);">
        {{-- Header row: type badge + outcome + meta --}}
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px; flex-wrap:wrap;">
            <span class="badge badge-info">{{ str_replace('_', ' ', ucfirst($session->interview_type)) }}</span>

            @if($session->status === 'scheduled')
                <span class="badge" style="background:var(--warning-light); color:var(--warning);">Scheduled</span>
            @elseif($session->status === 'in_progress')
                <span class="badge" style="background:var(--warning-light); color:var(--warning);">In Progress</span>
            @elseif($session->status === 'completed')
                @if($session->outcome === 'advanced')
                    <span class="badge" style="background:#dcfce7; color:#166534;">Advanced</span>
                @elseif($session->outcome === 'rejected')
                    <span class="badge" style="background:#fee2e2; color:#991b1b;">Rejected</span>
                @elseif($session->outcome === 'waitlisted')
                    <span class="badge" style="background:#fef3c7; color:#92400e;">Waitlisted</span>
                @else
                    <span class="badge" style="background:var(--gray-100); color:var(--gray-500);">Pending Decision</span>
                @endif
            @endif

            <span class="text-muted" style="margin-left:auto; font-size:12px;">
                {{ $session->interviewer->name ?? 'Unassigned' }}
                @if($session->started_at)
                    &middot; {{ $session->started_at->format('M d, Y') }}
                @elseif($session->scheduled_at)
                    &middot; {{ $session->scheduled_at->format('M d, Y') }}
                @endif
                @if($session->duration_seconds)
                    &middot; {{ floor($session->duration_seconds / 60) }}min
                @endif
            </span>
        </div>

        @if($session->status === 'completed' && !empty($summary) && empty($summary['_error']) && empty($summary['_insufficient_data']))
            {{-- AI Scores --}}
            @php
                $scores = array_filter([
                    'Technical' => $summary['technical_depth'] ?? null,
                    'Communication' => $summary['communication_score'] ?? null,
                    'Problem Solving' => $summary['problem_solving_score'] ?? null,
                ]);
            @endphp
            @if(!empty($scores))
            <div style="display:flex; gap:16px; margin-bottom:10px; flex-wrap:wrap;">
                @foreach($scores as $label => $score)
                <div style="display:flex; align-items:center; gap:6px;">
                    <span style="font-size:12px; color:var(--gray-500);">{{ $label }}:</span>
                    <span style="font-size:13px; font-weight:600; color:{{ $score >= 70 ? 'var(--success)' : ($score >= 40 ? 'var(--warning)' : 'var(--danger)') }}">{{ $score }}/100</span>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Strengths / Concerns --}}
            @if(!empty($summary['strengths']) || !empty($summary['concerns']))
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:10px;">
                @if(!empty($summary['strengths']))
                <div>
                    <div style="font-size:12px; font-weight:600; color:var(--success); margin-bottom:4px;">Strengths</div>
                    <ul style="margin:0; padding-left:16px; font-size:13px; color:var(--gray-600);">
                        @foreach((array)$summary['strengths'] as $s)
                        <li>{{ $s }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                @if(!empty($summary['concerns']))
                <div>
                    <div style="font-size:12px; font-weight:600; color:var(--danger); margin-bottom:4px;">Concerns</div>
                    <ul style="margin:0; padding-left:16px; font-size:13px; color:var(--gray-600);">
                        @foreach((array)$summary['concerns'] as $c)
                        <li>{{ $c }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
            @endif

            {{-- Narrative --}}
            @if(!empty($summary['narrative']))
            <p style="margin:0 0 8px; font-size:13px; color:var(--gray-600); line-height:1.6;">
                {{ \Illuminate\Support\Str::limit($summary['narrative'], 300) }}
            </p>
            @endif

            {{-- Manual notes --}}
            @if(!empty($summary['manual_notes']))
            <div style="margin-top:8px; padding:10px 12px; background:var(--gray-50); border-radius:var(--radius); border-left:3px solid var(--primary);">
                <div style="font-size:11px; font-weight:600; color:var(--gray-500); text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px;">Interviewer Notes</div>
                <p style="margin:0; font-size:13px; color:var(--gray-600); line-height:1.55;">{{ $summary['manual_notes'] }}</p>
            </div>
            @endif

            <div style="margin-top:8px;">
                <a href="{{ route('interviews.summary', $session) }}" class="action-link" style="font-size:12px;">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                    View Full Summary
                </a>
            </div>

        @elseif($session->status === 'completed')
            <p class="text-muted" style="font-size:13px; margin:0;">
                Interview completed — AI summary not yet available.
                <a href="{{ route('interviews.summary', $session) }}" style="color:var(--primary); font-weight:500;">View Summary</a>
            </p>
        @elseif($session->status === 'scheduled')
            <p class="text-muted" style="font-size:13px; margin:0;">
                Interview scheduled{{ $session->scheduled_at ? ' for ' . $session->scheduled_at->format('M d, Y g:i A') : '' }}.
            </p>
        @elseif($session->status === 'in_progress')
            <p class="text-muted" style="font-size:13px; margin:0;">
                Interview currently in progress.
                <a href="{{ route('interviews.show', $session) }}" style="color:var(--primary); font-weight:500;">Open Session</a>
            </p>
        @endif
    </div>
    @endforeach
</div>
@endif
