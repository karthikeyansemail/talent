@extends('layouts.app')
@section('title', 'Edit Job')
@section('page-title', 'Edit Job')
@section('content')
<div class="page-header"><h1>Edit: {{ $job->title }}</h1></div>

{{-- AI Auto-fill Section --}}
<div class="card" style="margin-bottom: 24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            AI Auto-fill
        </span>
    </div>
    <div class="card-body">
        <p class="text-muted" style="margin-bottom: 12px">Upload a job description document (PDF or DOCX) to re-parse and update the fields below.</p>
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

@if($job->jd_file_name)
<div class="alert alert-info" style="margin-bottom: 24px; display:flex; align-items:center; gap:10px">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    <span>Current JD file: <strong>{{ $job->jd_file_name }}</strong> &mdash; <a href="{{ route('jobs.downloadJd', $job) }}">Download</a></span>
</div>
@endif

<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Job Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('jobs.update', $job) }}">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label>Job Title *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $job->title) }}" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id', $job->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Job Description *</label>
                <textarea name="description" class="form-control" rows="5" required>{{ old('description', $job->description) }}</textarea>
            </div>

            <div class="form-group">
                <label>Key Responsibilities</label>
                <textarea name="key_responsibilities" class="form-control" rows="4" placeholder="Specific duties and day-to-day tasks...">{{ old('key_responsibilities', $job->key_responsibilities) }}</textarea>
            </div>

            <div class="form-group">
                <label>Requirements</label>
                <textarea name="requirements" class="form-control" rows="3">{{ old('requirements', $job->requirements) }}</textarea>
            </div>

            <div class="form-group">
                <label>Expectations</label>
                <textarea name="expectations" class="form-control" rows="3" placeholder="Performance expectations and success criteria...">{{ old('expectations', $job->expectations) }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Min Experience</label>
                    <input type="number" name="min_experience" class="form-control" value="{{ old('min_experience', $job->min_experience) }}" min="0">
                </div>
                <div class="form-group">
                    <label>Max Experience</label>
                    <input type="number" name="max_experience" class="form-control" value="{{ old('max_experience', $job->max_experience) }}" min="0">
                </div>
            </div>
            <div class="form-group">
                <label>Required Skills</label>
                <div class="tag-input-wrapper">
                    <input type="hidden" name="required_skills" value="{{ old('required_skills', is_array($job->required_skills) ? implode(',', $job->required_skills) : '') }}">
                    <input type="text" placeholder="Type skill and press Enter">
                </div>
            </div>
            <div class="form-group">
                <label>Nice-to-Have Skills</label>
                <div class="tag-input-wrapper">
                    <input type="hidden" name="nice_to_have_skills" value="{{ old('nice_to_have_skills', is_array($job->nice_to_have_skills) ? implode(',', $job->nice_to_have_skills) : '') }}">
                    <input type="text" placeholder="Type skill and press Enter">
                </div>
            </div>

            <div class="form-group">
                <label>Skill Experience Details</label>
                <textarea name="skill_experience_details" class="form-control" rows="3" placeholder="e.g. React: 3-5 years&#10;Node.js: 2+ years&#10;AWS: 1+ year">{{ old('skill_experience_details', $job->skill_experience_details) }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Employment Type</label>
                    <select name="employment_type" class="form-control">
                        @foreach(['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','intern'=>'Intern'] as $val=>$lbl)
                        <option value="{{ $val }}" {{ old('employment_type', $job->employment_type) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" class="form-control" value="{{ old('location', $job->location) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Min Salary</label>
                    <input type="number" name="salary_min" class="form-control" value="{{ old('salary_min', $job->salary_min) }}" step="0.01">
                </div>
                <div class="form-group">
                    <label>Max Salary</label>
                    <input type="number" name="salary_max" class="form-control" value="{{ old('salary_max', $job->salary_max) }}" step="0.01">
                </div>
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Internal notes...">{{ old('notes', $job->notes) }}</textarea>
            </div>

            {{-- Hidden fields for JD file temp storage --}}
            <input type="hidden" name="_temp_file_path" id="jdTempFilePath">
            <input type="hidden" name="_temp_file_name" id="jdTempFileName">
            <input type="hidden" name="_temp_file_type" id="jdTempFileType">
            <input type="hidden" name="_jd_extracted_text" id="jdExtractedText">

            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Update Job
                </button>
                <a href="{{ route('jobs.show', $job) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
