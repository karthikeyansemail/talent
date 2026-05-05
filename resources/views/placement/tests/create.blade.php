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

<form method="POST" action="{{ route('placement.tests.generate') }}">
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
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Generate with AI
                </button>
                <a href="{{ route('placement.tests.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>
@endif
@endsection
