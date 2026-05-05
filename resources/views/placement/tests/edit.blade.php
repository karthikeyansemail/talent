@extends('layouts.app')
@section('title', 'Edit: ' . $test->title)
@section('page-title', 'Edit Test')
@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1>{{ $test->title }}</h1>
        <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
            {{ $test->drive->company_name }} — {{ $test->drive->role_title }} · {{ $test->questions->count() }} question{{ $test->questions->count() === 1 ? '' : 's' }} · @include('components.stage-badge', ['stage' => $test->status])
        </p>
    </div>
    <div class="flex gap-10">
        @if($test->status === 'draft')
            <form method="POST" action="{{ route('placement.tests.publish', $test) }}" style="margin:0">
                @csrf
                <button type="submit" class="btn btn-primary" {{ $test->questions->count() === 0 ? 'disabled' : '' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Publish
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('placement.tests.unpublish', $test) }}" style="margin:0">
                @csrf
                <button type="submit" class="btn btn-secondary">Unpublish</button>
            </form>
        @endif
        <form method="POST" action="{{ route('placement.tests.destroy', $test) }}" style="margin:0">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this test and all its questions?')">Delete</button>
        </form>
    </div>
</div>

@if($test->status === 'published')
<div style="padding:14px 18px;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:10px;margin-bottom:20px">
    <div style="font-weight:600;color:var(--text-strong);margin-bottom:6px">Public Student URL — share this link</div>
    <div style="display:flex;gap:8px;align-items:center">
        <input type="text" readonly value="{{ url('placement/test/' . $test->public_token) }}" id="publicUrl" class="form-control" style="font-family:monospace;font-size:13px" onclick="this.select()">
        <button type="button" class="btn btn-sm btn-secondary" onclick="navigator.clipboard.writeText(document.getElementById('publicUrl').value).then(() => this.textContent='Copied!')">Copy</button>
    </div>
</div>
@endif

{{-- Test settings --}}
<form method="POST" action="{{ route('placement.tests.update', $test) }}">
    @csrf @method('PUT')
    <div class="card">
        <div class="card-header"><span class="card-header-icon">Test Settings</span></div>
        <div class="card-body">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $test->title) }}" required>
            </div>
            <div class="form-group">
                <label>Instructions for students</label>
                <textarea name="instructions" class="form-control" rows="3">{{ old('instructions', $test->instructions) }}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Time Limit (minutes) *</label>
                    <input type="number" name="time_limit_minutes" min="5" max="240" class="form-control" value="{{ old('time_limit_minutes', $test->time_limit_minutes) }}" required>
                </div>
                <div class="form-group">
                    <label>Passing Score (%) *</label>
                    <input type="number" name="passing_score_pct" min="0" max="100" class="form-control" value="{{ old('passing_score_pct', $test->passing_score_pct) }}" required>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control" required>
                        @foreach(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $v => $l)
                            <option value="{{ $v }}" {{ $test->status === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </div>
</form>

{{-- Questions list with inline edit --}}
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <span class="card-header-icon">Questions ({{ $test->questions->count() }})</span>
        <div class="flex gap-10">
            <form method="POST" action="{{ route('placement.tests.questions.add', $test) }}" style="margin:0">
                @csrf
                <input type="hidden" name="type" value="mcq">
                <button type="submit" class="btn btn-sm btn-secondary">+ Add MCQ</button>
            </form>
            <form method="POST" action="{{ route('placement.tests.questions.add', $test) }}" style="margin:0">
                @csrf
                <input type="hidden" name="type" value="descriptive">
                <button type="submit" class="btn btn-sm btn-secondary">+ Add Descriptive</button>
            </form>
        </div>
    </div>
    <div class="card-body" style="padding:0">
        @forelse($test->questions as $q)
            <div style="border-bottom:1px solid var(--border);padding:18px 22px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <span style="background:var(--bg-muted);padding:3px 8px;border-radius:6px;font-size:11px;font-weight:600">Q{{ $q->order }}</span>
                        <span class="badge {{ $q->type === 'mcq' ? 'badge-primary' : 'badge-purple' }}">{{ $q->type === 'mcq' ? 'MCQ' : 'Descriptive' }}</span>
                        @if($q->topic)<span class="text-sm text-muted">· {{ $q->topic }}</span>@endif
                        <span class="text-sm text-muted">· {{ $q->difficulty }} · {{ $q->marks }} mark{{ $q->marks === 1 ? '' : 's' }}</span>
                    </div>
                    <div class="flex gap-10">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('q-edit-{{ $q->id }}').style.display = (document.getElementById('q-edit-{{ $q->id }}').style.display === 'none' ? 'block' : 'none')">Edit</button>
                        <form method="POST" action="{{ route('placement.tests.questions.destroy', [$test, $q]) }}" style="margin:0">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this question?')">Delete</button>
                        </form>
                    </div>
                </div>

                {{-- Read-only summary --}}
                <div style="font-size:14px;color:var(--text);line-height:1.5">{{ $q->question_text }}</div>
                @if($q->context)
                    <pre style="margin-top:8px;padding:10px 12px;background:var(--code-bg);border-radius:6px;font-size:12px;overflow-x:auto">{{ $q->context }}</pre>
                @endif

                @if($q->type === 'mcq')
                    <div style="margin-top:10px">
                        @foreach(($q->options ?? []) as $i => $opt)
                            <div style="padding:4px 0;font-size:13px;color:{{ $i === $q->correct_option ? 'var(--success)' : 'var(--text-muted)' }};font-weight:{{ $i === $q->correct_option ? '600' : '400' }}">
                                {{ chr(65 + $i) }}. {{ $opt }} {{ $i === $q->correct_option ? ' ✓' : '' }}
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="margin-top:10px;padding:10px 12px;background:var(--bg-muted);border-radius:6px;font-size:12.5px;color:var(--text-muted)">
                        <strong>Ideal:</strong> {{ \Illuminate\Support\Str::limit($q->ideal_answer, 200) }}
                        @if(!empty($q->rubric_points))
                            <div style="margin-top:6px"><strong>Rubric:</strong> {{ implode(' · ', $q->rubric_points) }}</div>
                        @endif
                    </div>
                @endif

                {{-- Inline edit form (hidden by default) --}}
                <div id="q-edit-{{ $q->id }}" style="display:none;margin-top:14px;padding:14px;background:var(--bg-muted);border-radius:8px">
                    <form method="POST" action="{{ route('placement.tests.questions.update', [$test, $q]) }}">
                        @csrf @method('PUT')
                        <div class="form-group">
                            <label>Question text *</label>
                            <textarea name="question_text" class="form-control" rows="2" required>{{ $q->question_text }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Context (optional code snippet / scenario)</label>
                            <textarea name="context" class="form-control" rows="3">{{ $q->context }}</textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Topic</label>
                                <input type="text" name="topic" class="form-control" value="{{ $q->topic }}">
                            </div>
                            <div class="form-group">
                                <label>Difficulty</label>
                                <select name="difficulty" class="form-control">
                                    @foreach(['easy', 'medium', 'hard'] as $d)<option value="{{ $d }}" {{ $q->difficulty === $d ? 'selected' : '' }}>{{ ucfirst($d) }}</option>@endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Marks</label>
                                <input type="number" name="marks" min="0" max="20" class="form-control" value="{{ $q->marks }}">
                            </div>
                        </div>

                        @if($q->type === 'mcq')
                            <div class="form-group">
                                <label>Options (mark correct one)</label>
                                @foreach(($q->options ?? ['', '', '', '']) as $i => $opt)
                                    <div style="display:flex;gap:10px;align-items:center;margin-bottom:6px">
                                        <label style="display:flex;align-items:center;gap:6px;margin:0;font-size:13px">
                                            <input type="radio" name="correct_option" value="{{ $i }}" {{ $i === $q->correct_option ? 'checked' : '' }}> {{ chr(65 + $i) }}
                                        </label>
                                        <input type="text" name="options[]" class="form-control" value="{{ $opt }}" placeholder="Option {{ chr(65 + $i) }}" style="flex:1">
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="form-group">
                                <label>Ideal answer (gold standard)</label>
                                <textarea name="ideal_answer" class="form-control" rows="4">{{ $q->ideal_answer }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Rubric points (one per line)</label>
                                @php $rubric = $q->rubric_points ?? []; while(count($rubric) < 5) $rubric[] = ''; @endphp
                                @foreach($rubric as $rp)
                                    <input type="text" name="rubric_points[]" class="form-control" value="{{ $rp }}" placeholder="Specific point the student should mention" style="margin-bottom:6px">
                                @endforeach
                            </div>
                            <div class="form-group">
                                <label>Expected word count</label>
                                <input type="number" name="expected_word_count" min="10" max="1000" class="form-control" style="max-width:200px" value="{{ $q->expected_word_count ?? 100 }}">
                            </div>
                        @endif

                        <div class="flex gap-10">
                            <button type="submit" class="btn btn-primary btn-sm">Save Question</button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('q-edit-{{ $q->id }}').style.display='none'">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state" style="padding:30px">
                <p>No questions yet</p>
                <p class="empty-hint">Click "+ Add MCQ" or "+ Add Descriptive" to add manually, or generate via AI from the test creation page.</p>
            </div>
        @endforelse
    </div>
</div>

<div style="margin-top:16px">
    <a href="{{ route('placement.tests.index') }}" class="btn btn-secondary">← Back to Tests</a>
</div>
@endsection
