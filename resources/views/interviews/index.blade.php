@extends('layouts.app')
@section('title', 'Interviews')

@section('content')
<div class="page-header" style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
    <h1 class="page-title">Interviews</h1>
    @if(Auth::user()->hasAnyRole(['hr_manager','hiring_manager','org_admin','super_admin']))
        <a href="{{ route('interviews.create') }}" class="btn btn-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
            Assign Interview
        </a>
    @endif
</div>

{{-- Main Tabs --}}
<div class="tabs" data-tabs>
    <button class="tab active" data-tab="tab-iv-active">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        In Progress
        @if($active->isNotEmpty())
            <span class="badge badge-success" style="font-size:11px; padding:2px 7px; min-width:auto;">{{ $active->count() }}</span>
        @endif
    </button>
    <button class="tab" data-tab="tab-iv-upcoming">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        Upcoming
        <span class="badge" style="font-size:11px; padding:2px 7px; min-width:auto; background:var(--gray-100); color:var(--gray-600);">{{ $upcoming->count() }}</span>
    </button>
    <button class="tab" data-tab="tab-iv-completed">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
        Completed
        <span class="badge" style="font-size:11px; padding:2px 7px; min-width:auto; background:var(--gray-100); color:var(--gray-600);">{{ $completed->count() }}</span>
    </button>
</div>

{{-- Tab: In Progress --}}
<div class="tab-content active" id="tab-iv-active">
    @if($active->isEmpty())
        <div class="card">
            <div class="card-body" style="padding:48px; text-align:center;">
                <svg width="40" height="40" fill="none" stroke="var(--gray-300)" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                <p class="text-muted">No interviews in progress right now.</p>
            </div>
        </div>
    @else
        <div class="interview-grid">
            @foreach($active as $session)
                <div class="interview-card interview-card--active">
                    <div class="interview-card__status">
                        <span class="pulse-dot pulse-dot--live"></span> Live
                    </div>
                    <h4 class="interview-card__candidate">{{ $session->candidate->first_name }} {{ $session->candidate->last_name }}</h4>
                    <p class="interview-card__job">{{ $session->application->jobPosting->title ?? 'N/A' }}</p>
                    <div class="interview-card__meta">
                        <span class="badge badge-outline">{{ str_replace('_', ' ', ucfirst($session->interview_type)) }}</span>
                        <span class="text-muted">Interviewer: {{ $session->interviewer->name ?? 'Unassigned' }}</span>
                    </div>
                    <div class="interview-card__actions">
                        <a href="{{ route('interviews.show', $session) }}" class="btn btn-primary btn-sm">Open Session</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Tab: Upcoming --}}
<div class="tab-content" id="tab-iv-upcoming">
    @if($upcoming->isEmpty())
        <div class="card">
            <div class="card-body" style="padding:48px; text-align:center;">
                <svg width="40" height="40" fill="none" stroke="var(--gray-300)" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <p class="text-muted">No upcoming interviews scheduled.</p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body" style="padding:0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Job Position</th>
                            <th>Stage</th>
                            <th>Interviewer</th>
                            <th>Scheduled</th>
                            <th style="width:120px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcoming as $session)
                        <tr>
                            <td>
                                <strong>{{ $session->candidate->first_name }} {{ $session->candidate->last_name }}</strong>
                                <br><small class="text-muted">{{ $session->candidate->email }}</small>
                            </td>
                            <td>{{ $session->application->jobPosting->title ?? 'N/A' }}</td>
                            <td><span class="badge badge-outline">{{ str_replace('_', ' ', ucfirst($session->interview_type)) }}</span></td>
                            <td>{{ $session->interviewer->name ?? 'Unassigned' }}</td>
                            <td>
                                @if($session->scheduled_at)
                                    {{ $session->scheduled_at->format('M d, Y') }}<br>
                                    <small class="text-muted">{{ $session->scheduled_at->format('g:i A') }}</small>
                                @else
                                    <span class="text-muted">Not set</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('interviews.show', $session) }}" class="action-link">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                    Open
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

{{-- Tab: Completed --}}
<div class="tab-content" id="tab-iv-completed">
    @if($completed->isEmpty())
        <div class="card">
            <div class="card-body" style="padding:48px; text-align:center;">
                <svg width="40" height="40" fill="none" stroke="var(--gray-300)" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 12px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
                <p class="text-muted">No completed interviews yet.</p>
            </div>
        </div>
    @else
        {{-- Outcome Filters --}}
        <div class="ir-outcome-filters" id="ir-filter-tabs">
            <button type="button" class="ir-outcome-filter ir-outcome-filter--active" data-filter="all">
                All <span class="ir-outcome-filter__count">{{ $completed->count() }}</span>
            </button>
            <button type="button" class="ir-outcome-filter" data-filter="advanced">
                <span class="ir-outcome-dot" style="background:#22c55e;"></span> Advanced <span class="ir-outcome-filter__count ir-filter-count" data-count="advanced">0</span>
            </button>
            <button type="button" class="ir-outcome-filter" data-filter="waitlisted">
                <span class="ir-outcome-dot" style="background:#f59e0b;"></span> Waitlisted <span class="ir-outcome-filter__count ir-filter-count" data-count="waitlisted">0</span>
            </button>
            <button type="button" class="ir-outcome-filter" data-filter="rejected">
                <span class="ir-outcome-dot" style="background:#ef4444;"></span> Rejected <span class="ir-outcome-filter__count ir-filter-count" data-count="rejected">0</span>
            </button>
            <button type="button" class="ir-outcome-filter" data-filter="pending">
                <span class="ir-outcome-dot" style="background:var(--gray-400);"></span> Pending <span class="ir-outcome-filter__count ir-filter-count" data-count="pending">0</span>
            </button>
        </div>

        <div class="card">
            <div class="card-body" style="padding:0;">
                <div id="ir-no-match" style="display:none; padding:40px; text-align:center;">
                    <p class="text-muted">No interviews match the selected filter.</p>
                </div>
                <table class="table" id="ir-completed-table">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Job Position</th>
                            <th>Stage</th>
                            <th>Interviewer</th>
                            <th>Outcome</th>
                            <th>Completed</th>
                            <th style="width:120px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($completed as $session)
                        <tr data-outcome="{{ $session->outcome ?? 'pending' }}">
                            <td>
                                <strong>{{ $session->candidate->first_name }} {{ $session->candidate->last_name }}</strong>
                            </td>
                            <td>{{ $session->application->jobPosting->title ?? 'N/A' }}</td>
                            <td><span class="badge badge-outline">{{ str_replace('_', ' ', ucfirst($session->interview_type)) }}</span></td>
                            <td>{{ $session->interviewer->name ?? 'Unassigned' }}</td>
                            <td>
                                @if($session->outcome === 'advanced')
                                    <span class="badge" style="background:#dcfce7; color:#166534;">Advanced</span>
                                @elseif($session->outcome === 'waitlisted')
                                    <span class="badge" style="background:#fef3c7; color:#92400e;">Waitlisted</span>
                                @elseif($session->outcome === 'rejected')
                                    <span class="badge" style="background:#fee2e2; color:#991b1b;">Rejected</span>
                                @else
                                    <span class="badge" style="background:var(--gray-100); color:var(--gray-500);">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($session->ended_at)
                                    {{ $session->ended_at->format('M d, Y g:i A') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <a href="{{ route('interviews.summary', $session) }}" class="action-link">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                        Summary
                                    </a>
                                    @if(!$session->outcome || $session->outcome === 'waitlisted')
                                    <form method="POST" action="{{ route('interviews.reopen', $session) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="action-link" style="background:none; border:none; cursor:pointer; padding:0; font:inherit;" onclick="return confirm('Reopen this interview? A new session will be scheduled.')">
                                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                                            Reopen
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
