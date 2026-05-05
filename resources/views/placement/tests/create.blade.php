@extends('layouts.app')
@section('title', 'Generate Aptitude Test')
@section('page-title', 'Generate Aptitude Test')
@section('content')
<div class="page-header">
    <h1>Generate Aptitude Test</h1>
    <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
        AI generates the questions based on the drive's role, skills, and your difficulty mix. You'll review + edit them before publishing.
    </p>
</div>

@if($drives->isEmpty())
<div class="card">
    <div class="card-body" style="text-align:center;padding:40px">
        <p style="color:var(--text-muted)">No active placement drives found.</p>
        <p style="font-size:13px;color:var(--text-subtle);margin-top:8px">Create a drive first, then come back to generate a test for it.</p>
        <a href="{{ route('placement.drives.create') }}" class="btn btn-primary" style="margin-top:14px">Create a Drive</a>
    </div>
</div>
@else

<form id="generateForm" method="POST" action="{{ route('placement.tests.generate') }}">
    @csrf
    <div class="card">
        <div class="card-header"><span class="card-header-icon">Test Settings</span></div>
        <div class="card-body">
            <div class="form-group">
                <label>Placement Drive *</label>
                <select name="placement_drive_id" class="form-control" required>
                    <option value="">Select a drive...</option>
                    @foreach($drives as $d)
                        <option value="{{ $d->id }}" {{ $selectedDriveId == $d->id ? 'selected' : '' }}>
                            {{ $d->company_name }} — {{ $d->role_title }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">AI uses this drive's role, skills, and eligible courses to design questions.</small>
            </div>

            <div class="form-group">
                <label>Test Title (optional)</label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Leave blank — AI suggests one">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Time Limit (minutes) *</label>
                    <input type="number" name="time_limit_minutes" min="5" max="240" class="form-control" value="{{ old('time_limit_minutes', 45) }}" required>
                </div>
                <div class="form-group">
                    <label>Passing Score (%) *</label>
                    <input type="number" name="passing_score_pct" min="0" max="100" class="form-control" value="{{ old('passing_score_pct', 60) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>MCQ Questions *</label>
                    <input type="number" name="num_mcq" min="0" max="50" class="form-control" value="{{ old('num_mcq', 10) }}" required>
                    <small class="text-muted">4-option multiple choice, auto-graded.</small>
                </div>
                <div class="form-group">
                    <label>Descriptive Questions *</label>
                    <input type="number" name="num_descriptive" min="0" max="15" class="form-control" value="{{ old('num_descriptive', 3) }}" required>
                    <small class="text-muted">Free-text paragraphs, AI-graded for understanding (much harder to cheat than MCQ).</small>
                </div>
            </div>

            <div class="form-group">
                <label>Overall Difficulty *</label>
                <select name="difficulty" class="form-control" style="max-width:280px" required>
                    <option value="easy" {{ old('difficulty') === 'easy' ? 'selected' : '' }}>Easy</option>
                    <option value="medium" {{ old('difficulty', 'medium') === 'medium' ? 'selected' : '' }}>Medium (recommended)</option>
                    <option value="hard" {{ old('difficulty') === 'hard' ? 'selected' : '' }}>Hard</option>
                </select>
                <small class="text-muted">AI varies difficulty around this center.</small>
            </div>

            <div style="padding:12px 14px;background:#fff7ed;border-left:3px solid #f97316;border-radius:6px;font-size:13px;color:var(--text);margin:16px 0">
                <strong>How it works:</strong> AI generates questions tailored to the drive's role + skills.
                You'll land on the editor where you can review every question, edit text/options/rubric,
                add or delete questions, then publish to get a public student URL.
            </div>

            <div class="flex gap-10" style="margin-top:20px">
                <button type="submit" id="generateBtn" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Generate with AI
                </button>
                <a href="{{ route('placement.tests.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>
@endif

{{-- Progress overlay shown while AI is generating --}}
<div id="genOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center">
    <div style="background:var(--bg-card);border-radius:14px;padding:32px 40px;max-width:480px;width:90%;text-align:center;box-shadow:var(--shadow-lg)">
        <div style="display:inline-flex;width:56px;height:56px;border-radius:50%;background:var(--primary-100);align-items:center;justify-content:center;margin-bottom:18px">
            <div style="width:36px;height:36px;border:3px solid var(--primary-200);border-top-color:var(--primary);border-radius:50%;animation:spin 0.9s linear infinite"></div>
        </div>
        <h3 style="margin:0 0 8px;font-size:18px;color:var(--text-strong)">Generating Aptitude Test</h3>
        <p id="genPhase" style="margin:0;color:var(--text-muted);font-size:14px;line-height:1.5">Queued for AI generation…</p>
        <div style="margin-top:18px;height:6px;background:var(--bg-muted);border-radius:3px;overflow:hidden">
            <div id="genBar" style="height:100%;background:var(--primary);width:5%;transition:width 0.3s ease"></div>
        </div>
        <p id="genHint" style="margin:14px 0 0;color:var(--text-subtle);font-size:12px">
            This usually takes 20-60 seconds depending on question count.
        </p>
        <p id="genError" style="display:none;margin:14px 0 0;color:var(--danger);font-size:13px"></p>
    </div>
</div>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>

<script>
(function() {
    var form = document.getElementById('generateForm');
    if (!form) return;
    var btn = document.getElementById('generateBtn');
    var overlay = document.getElementById('genOverlay');
    var phaseEl = document.getElementById('genPhase');
    var bar = document.getElementById('genBar');
    var errEl = document.getElementById('genError');

    var pct = 5;
    var phaseTimer = null;
    var pollTimer  = null;

    function bumpProgress(target, label) {
        // Smoothly grow toward target percentage
        var step = function() {
            if (pct < target) {
                pct = Math.min(target, pct + 1);
                bar.style.width = pct + '%';
                requestAnimationFrame(step);
            }
        };
        requestAnimationFrame(step);
        if (label) phaseEl.textContent = label;
    }

    function showError(msg) {
        errEl.textContent = msg;
        errEl.style.display = 'block';
        phaseEl.textContent = 'Generation failed';
        bar.style.background = 'var(--danger)';
        // Allow user to close after 3s
        setTimeout(function() {
            overlay.style.display = 'none';
            btn.disabled = false;
            btn.style.opacity = '1';
        }, 4000);
    }

    form.addEventListener('submit', function(ev) {
        ev.preventDefault();
        btn.disabled = true;
        btn.style.opacity = '0.6';
        overlay.style.display = 'flex';
        pct = 5; bar.style.width = '5%'; bar.style.background = 'var(--primary)';
        errEl.style.display = 'none';

        // Animated phase progression while we wait for the queue + AI
        var phases = [
            { delay:  500,  pct: 15, label: 'Sending request to AI service…' },
            { delay: 4000,  pct: 35, label: 'AI is composing questions…' },
            { delay: 12000, pct: 55, label: 'Crafting MCQ options + correct answers…' },
            { delay: 22000, pct: 75, label: 'Building descriptive prompts + grading rubrics…' },
            { delay: 35000, pct: 88, label: 'Final quality pass…' },
            { delay: 55000, pct: 95, label: 'Almost there…' },
        ];
        phases.forEach(function(p) {
            setTimeout(function() { bumpProgress(p.pct, p.label); }, p.delay);
        });

        var fd = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: fd
        })
        .then(function(r) {
            if (!r.ok) {
                return r.json().then(function(j) { throw new Error(j.error || j.message || ('HTTP ' + r.status)); });
            }
            return r.json();
        })
        .then(function(data) {
            if (data.status !== 'queued') {
                throw new Error(data.error || 'Failed to queue generation.');
            }
            // Start polling
            pollTimer = setInterval(function() {
                fetch(data.status_url, { headers: { 'Accept': 'application/json' } })
                .then(function(r) { return r.json(); })
                .then(function(s) {
                    if (s.status === 'complete') {
                        clearInterval(pollTimer);
                        bumpProgress(100, 'Done! Opening editor…');
                        setTimeout(function() { window.location = s.redirect; }, 600);
                    } else if (s.status === 'failed') {
                        clearInterval(pollTimer);
                        showError(s.error || 'AI generation failed. Make sure the AI service + queue worker are running.');
                    } else if (s.status === 'running' && s.phase) {
                        // Server-reported phase (e.g. "Saving questions...") overrides client animation
                        phaseEl.textContent = s.phase;
                    }
                })
                .catch(function() { /* ignore transient poll errors */ });
            }, 2000);
        })
        .catch(function(err) {
            showError(err.message);
        });
    });
})();
</script>
@endsection
