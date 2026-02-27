@extends('layouts.app')
@section('title', $employee->full_name)
@section('page-title', 'Employee Details')
@section('content')

{{-- Profile Hero --}}
<div class="profile-hero">
    <div class="avatar-lg">{{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}</div>
    <div class="profile-info">
        <h1>{{ $employee->full_name }}</h1>
        <div class="profile-meta">
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
                {{ $employee->designation ?? 'No designation' }}
            </span>
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                {{ $employee->email }}
            </span>
            <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                {{ $employee->department?->name ?? 'No department' }}
            </span>
            <span class="status-indicator">
                <span class="status-dot {{ $employee->is_active ? 'active' : 'inactive' }}"></span>
                {{ $employee->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
    </div>
</div>

{{-- Action Bar --}}
<div class="action-bar">
    <a href="{{ route('employees.edit', $employee) }}" class="action-btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit Employee
    </a>
    <button type="button"
        class="action-btn action-primary jira-sync-btn"
        data-url="{{ route('employees.syncWorkData', $employee) }}"
        data-status-url="{{ route('employees.workDataSyncStatus', $employee) }}"
        data-signals-url="{{ route('employees.signalIntelligenceHtml', $employee) }}"
        data-csrf="{{ csrf_token() }}"
        title="Sync all connected data sources (tasks, signals)">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
        <span class="jira-sync-btn-text">Sync Work Data</span>
    </button>

    @if($employee->tasks->count() > 0)
    <button type="button"
        class="action-btn wp-analyze-btn"
        data-url="{{ route('employees.analyzeWorkPulse', $employee) }}"
        data-status-url="{{ route('employees.workPulseStatus', $employee) }}"
        data-signals-url="{{ route('employees.signalIntelligenceHtml', $employee) }}"
        data-csrf="{{ csrf_token() }}"
        title="Run AI Work Pulse analysis">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        <span class="wp-analyze-btn-text">AI Analyze</span>
    </button>
    @endif

    {{-- Jira sync progress bar (shown when syncing) --}}
    <div class="jira-sync-progress" id="jira-sync-progress" style="display:none">
        <div class="jira-sync-progress-bar">
            <div class="jira-sync-progress-fill" id="jira-sync-fill" style="width:0%"></div>
        </div>
        <div class="jira-sync-progress-info">
            <span class="jira-sync-pct" id="jira-sync-pct">0%</span>
            <span class="jira-sync-phase" id="jira-sync-phase">Starting...</span>
        </div>
    </div>

    {{-- Last synced timestamp — pushed to the far right --}}
    <span id="last-synced-label" style="margin-left:auto;font-size:11px;color:var(--gray-400);white-space:nowrap">
        @if($employee->work_data_synced_at)
            Data synced {{ $employee->work_data_synced_at->diffForHumans() }}
        @else
            Data never synced
        @endif
    </span>
</div>

{{-- Work Pulse AI analysis progress bar (shown when analyzing) --}}
<div id="wp-progress-wrap" style="display:none;align-items:center;gap:10px;padding:10px 24px;background:#f0f9ff;border-bottom:1px solid #bae6fd">
    <div style="flex:1;background:#e0f2fe;border-radius:999px;height:8px;overflow:hidden">
        <div id="wp-progress-fill" style="height:8px;border-radius:999px;background:var(--primary);transition:width .4s ease;width:5%"></div>
    </div>
    <span id="wp-progress-pct" style="font-size:11px;font-weight:700;color:var(--primary);min-width:32px;text-align:right">5%</span>
    <span id="wp-progress-phase" style="font-size:11px;color:var(--gray-500);min-width:160px">Queuing...</span>
</div>

{{-- Tabs --}}
<div data-tabs class="tabs">
    <button class="tab active" data-tab="tab-overview">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Overview
    </button>
    @if(in_array(auth()->user()->role, ['management','org_admin','super_admin']))
    <button class="tab" data-tab="tab-signals">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Work Pulse
    </button>
    @endif
</div>

{{-- Tab: Overview --}}
<div id="tab-overview" class="tab-content active">
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
                        <div class="row-content"><div class="row-label">Email</div><div class="row-value">{{ $employee->email }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></div>
                        <div class="row-content"><div class="row-label">Department</div><div class="row-value">{{ $employee->department?->name ?? 'N/A' }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg></div>
                        <div class="row-content"><div class="row-label">Designation</div><div class="row-value">{{ $employee->designation ?? 'N/A' }}</div></div>
                    </div>
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                        <div class="row-content"><div class="row-label">Status</div><div class="row-value"><span class="status-indicator"><span class="status-dot {{ $employee->is_active ? 'active' : 'inactive' }}"></span>{{ $employee->is_active ? 'Active' : 'Inactive' }}</span></div></div>
                    </div>
                    @if($employee->tasks->count())
                    <div class="detail-row">
                        <div class="row-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
                        <div class="row-content"><div class="row-label">Tasks Synced</div><div class="row-value">{{ $employee->tasks->count() }} tasks across {{ $employee->tasks->groupBy('source_type')->count() }} {{ Str::plural('source', $employee->tasks->groupBy('source_type')->count()) }}</div></div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Skill Profile Card --}}
        <div class="card">
            <div class="card-header">
                <span class="card-header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    Skill Profile
                </span>
            </div>
            <div class="card-body">
                @if($employee->skills_from_jira && isset($employee->skills_from_jira['extracted_skills']) && count($employee->skills_from_jira['extracted_skills']))
                    @foreach($employee->skills_from_jira['extracted_skills'] as $skill)
                    <div class="skill-bar">
                        <span class="label">{{ $skill['skill'] ?? 'N/A' }}</span>
                        <div class="bar"><div class="fill" style="width:{{ ($skill['confidence'] ?? 0) * 100 }}%"></div></div>
                        <span class="percent">{{ ucfirst($skill['depth'] ?? '') }}</span>
                    </div>
                    @endforeach
                @elseif($employee->skills_from_resume && is_array($employee->skills_from_resume) && count($employee->skills_from_resume))
                    <div class="tags" style="padding:4px 0">
                        @foreach($employee->skills_from_resume as $skill)
                        <span class="tag">{{ $skill }}</span>
                        @endforeach
                    </div>
                    <div class="empty-hint" style="margin-top:12px">Sync Jira tasks to get skill confidence scores</div>
                @else
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <p>No skill data available</p>
                        <div class="empty-hint">Sync Jira tasks to extract skills automatically</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>


@if(in_array(auth()->user()->role, ['management','org_admin','super_admin']))
{{-- Tab: Work Pulse --}}
<div id="tab-signals" class="tab-content">
    @include('employees.partials.signal-intelligence', ['employee' => $employee, 'signalInsights' => $signalInsights])
</div>
@endif {{-- end management role gate --}}

@section('scripts')
<script>
(function () {
    var btn = document.querySelector('.jira-sync-btn');
    if (!btn) return;

    var progressWrap = document.getElementById('jira-sync-progress');
    var fill         = document.getElementById('jira-sync-fill');
    var pctEl        = document.getElementById('jira-sync-pct');
    var phaseEl      = document.getElementById('jira-sync-phase');
    var btnText      = btn.querySelector('.jira-sync-btn-text');

    var pollTimer  = null;
    var syncing    = false;

    function setProgress(pct, phase) {
        if (fill)    fill.style.width = pct + '%';
        if (pctEl)   pctEl.textContent = Math.round(pct) + '%';
        if (phaseEl) phaseEl.textContent = phase;
    }

    function startSync() {
        if (syncing) return;
        syncing = true;

        btn.disabled = true;
        btnText.textContent = 'Syncing...';
        progressWrap.style.display = 'flex';
        setProgress(5, 'Queuing sync...');

        fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': btn.dataset.csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status === 'queued') {
                pollStatus();
            } else {
                resetBtn();
            }
        })
        .catch(function () {
            resetBtn();
            alert('Failed to start sync. Please try again.');
        });
    }

    function pollStatus() {
        var statusUrl = btn.dataset.statusUrl;
        pollTimer = setInterval(function () {
            fetch(statusUrl, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': btn.dataset.csrf }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'running') {
                    setProgress(data.pct || 50, data.phase || 'Processing...');
                } else if (data.status === 'completed') {
                    clearInterval(pollTimer);
                    setProgress(100, 'Updating signals...');
                    // Update "last synced" label immediately
                    var label = document.getElementById('last-synced-label');
                    if (label) label.textContent = 'Last synced just now';
                    // Fetch updated Work Pulse HTML and inject it directly
                    fetch(btn.dataset.signalsUrl, {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': btn.dataset.csrf
                        }
                    })
                    .then(function (r) { return r.text(); })
                    .then(function (html) {
                        var tabEl = document.getElementById('tab-signals');
                        if (tabEl) { tabEl.innerHTML = html; }
                        resetBtn();
                    })
                    .catch(function () {
                        // Fallback: full page reload if injection fails
                        window.location.reload();
                    });
                } else if (data.status === 'failed') {
                    clearInterval(pollTimer);
                    resetBtn();
                    phaseEl.textContent = data.phase || 'Sync failed.';
                    setTimeout(function () { progressWrap.style.display = 'none'; }, 3000);
                }
            })
            .catch(function () { /* ignore transient poll errors */ });
        }, 2500);
    }

    function resetBtn() {
        syncing = false;
        btn.disabled = false;
        btnText.textContent = 'Sync Work Data';
        progressWrap.style.display = 'none';
        if (pollTimer) clearInterval(pollTimer);
    }

    btn.addEventListener('click', startSync);
})();

// ── Work Pulse AI Analysis polling ──────────────────────────────────────────
(function () {
    var btn = document.querySelector('.wp-analyze-btn');
    if (!btn) return;

    var progressWrap = document.getElementById('wp-progress-wrap');
    var fill    = document.getElementById('wp-progress-fill');
    var pctEl   = document.getElementById('wp-progress-pct');
    var phaseEl = document.getElementById('wp-progress-phase');
    var btnText = btn.querySelector('.wp-analyze-btn-text');
    var pollTimer = null, analyzing = false;

    function setProgress(pct, phase) {
        if (fill)    fill.style.width = pct + '%';
        if (pctEl)   pctEl.textContent = Math.round(pct) + '%';
        if (phaseEl) phaseEl.textContent = phase;
    }

    function startAnalysis() {
        if (analyzing) return;
        analyzing = true;
        btn.disabled = true;
        btnText.textContent = 'Analyzing...';
        progressWrap.style.display = 'flex';
        setProgress(5, 'Queuing analysis...');

        fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': btn.dataset.csrf,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'queued') {
                pollStatus();
            } else {
                resetBtn();
            }
        })
        .catch(function() {
            resetBtn();
            alert('Failed to start analysis. Please try again.');
        });
    }

    function pollStatus() {
        pollTimer = setInterval(function() {
            fetch(btn.dataset.statusUrl, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': btn.dataset.csrf }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.status === 'running') {
                    setProgress(data.pct || 50, data.phase || 'Processing...');
                } else if (data.status === 'completed') {
                    clearInterval(pollTimer);
                    setProgress(100, 'Refreshing insights...');
                    fetch(btn.dataset.signalsUrl, {
                        headers: {
                            'Accept': 'text/html',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': btn.dataset.csrf
                        }
                    })
                    .then(function(r) { return r.text(); })
                    .then(function(html) {
                        var tab = document.getElementById('tab-signals');
                        if (tab) tab.innerHTML = html;
                        resetBtn();
                    })
                    .catch(function() { window.location.reload(); });
                } else if (data.status === 'failed') {
                    clearInterval(pollTimer);
                    resetBtn();
                    if (phaseEl) phaseEl.textContent = data.phase || 'Analysis failed.';
                    setTimeout(function() { progressWrap.style.display = 'none'; }, 3000);
                }
            })
            .catch(function() { /* ignore transient poll errors */ });
        }, 2500);
    }

    function resetBtn() {
        analyzing = false;
        btn.disabled = false;
        btnText.textContent = 'AI Analyze';
        progressWrap.style.display = 'none';
        if (pollTimer) clearInterval(pollTimer);
    }

    btn.addEventListener('click', startAnalysis);
})();
</script>
@endsection

@endsection
