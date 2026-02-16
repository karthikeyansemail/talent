@extends('layouts.app')
@section('title', 'Create Job')
@section('page-title', 'Create Job Posting')
@section('content')
<div class="page-header"><h1>Create Job Posting</h1></div>

{{-- AI Auto-fill Section --}}
<div class="card" style="margin-bottom: 24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            AI Auto-fill
        </span>
    </div>
    <div class="card-body">
        <p class="text-muted" style="margin-bottom: 12px">Upload a job description document (PDF or DOCX) and AI will auto-fill the form fields below.</p>
        <div class="ai-upload-area" id="jdUploadArea" data-url="{{ route('jobs.parseDocument') }}">
            <input type="file" id="jdFileInput" accept=".pdf,.docx" style="display:none">
            <div class="upload-content" id="jdUploadContent">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="12" y2="12"/><line x1="15" y1="15" x2="12" y2="12"/></svg>
                <p style="margin-top:8px;font-weight:500">Drop a file here or click to upload</p>
                <p class="text-muted text-sm">PDF or DOCX, up to 10MB</p>
            </div>
            <div class="upload-loading hidden" id="jdUploadLoading">
                <div class="spinner" style="width:28px;height:28px;border-width:3px"></div>
                <p style="margin-top:10px;font-weight:500">AI is parsing the document...</p>
                <p class="text-muted text-sm">This may take a few seconds</p>
            </div>
            <div class="upload-success hidden" id="jdUploadSuccess">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <p style="margin-top:8px;font-weight:500;color:var(--success)">Fields populated successfully!</p>
                <p class="text-muted text-sm">Review and edit the auto-filled fields below</p>
            </div>
        </div>
        <div class="upload-error hidden" id="jdUploadError" style="margin-top:10px">
            <div class="alert alert-error" style="margin:0"><span id="jdErrorText"></span></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
            Job Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('jobs.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label>Job Title *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Job Description *</label>
                <textarea name="description" class="form-control" rows="5" required>{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label>Requirements</label>
                <textarea name="requirements" class="form-control" rows="3">{{ old('requirements') }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Min Experience (years)</label>
                    <input type="number" name="min_experience" class="form-control" value="{{ old('min_experience', 0) }}" min="0">
                </div>
                <div class="form-group">
                    <label>Max Experience (years)</label>
                    <input type="number" name="max_experience" class="form-control" value="{{ old('max_experience', 10) }}" min="0">
                </div>
            </div>

            <div class="form-group">
                <label>Required Skills</label>
                <div class="tag-input-wrapper">
                    <input type="hidden" name="required_skills" value="{{ old('required_skills') }}">
                    <input type="text" placeholder="Type skill and press Enter">
                </div>
            </div>

            <div class="form-group">
                <label>Nice-to-Have Skills</label>
                <div class="tag-input-wrapper">
                    <input type="hidden" name="nice_to_have_skills" value="{{ old('nice_to_have_skills') }}">
                    <input type="text" placeholder="Type skill and press Enter">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Employment Type *</label>
                    <select name="employment_type" class="form-control" required>
                        <option value="full_time" {{ old('employment_type') === 'full_time' ? 'selected' : '' }}>Full Time</option>
                        <option value="part_time" {{ old('employment_type') === 'part_time' ? 'selected' : '' }}>Part Time</option>
                        <option value="contract" {{ old('employment_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="intern" {{ old('employment_type') === 'intern' ? 'selected' : '' }}>Intern</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Min Salary</label>
                    <input type="number" name="salary_min" class="form-control" value="{{ old('salary_min') }}" step="0.01">
                </div>
                <div class="form-group">
                    <label>Max Salary</label>
                    <input type="number" name="salary_max" class="form-control" value="{{ old('salary_max') }}" step="0.01">
                </div>
            </div>

            <div class="form-group">
                <label>Status *</label>
                <select name="status" class="form-control" required>
                    <option value="draft">Draft</option>
                    <option value="open">Open</option>
                </select>
            </div>

            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create Job
                </button>
                <a href="{{ route('jobs.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
