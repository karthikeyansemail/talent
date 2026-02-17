@extends('layouts.app')
@section('title', 'Add Candidate')
@section('page-title', 'Add Candidate')
@section('content')
<div class="page-header"><h1>Add Candidate</h1></div>

{{-- AI Auto-fill Section --}}
<div class="card" style="margin-bottom: 24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            AI Auto-fill from Resume
        </span>
    </div>
    <div class="card-body">
        <p class="text-muted" style="margin-bottom: 12px">Upload a resume (PDF or DOCX) and AI will auto-fill the candidate details below. The resume will also be saved to the candidate profile.</p>
        <div class="ai-upload-area" id="resumeUploadArea" data-url="{{ route('candidates.parseResume') }}">
            <input type="file" id="resumeFileInput" accept=".pdf,.docx" style="display:none">
            <div class="upload-content" id="resumeUploadContent">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="12" y2="12"/><line x1="15" y1="15" x2="12" y2="12"/></svg>
                <p style="margin-top:8px;font-weight:500">Drop a resume here or click to upload</p>
                <p class="text-muted text-sm">PDF or DOCX, up to 10MB</p>
            </div>
            <div class="upload-loading hidden" id="resumeUploadLoading">
                <div class="spinner" style="width:28px;height:28px;border-width:3px"></div>
                <p style="margin-top:10px;font-weight:500">AI is reading the resume...</p>
                <p class="text-muted text-sm">This may take a few seconds</p>
            </div>
            <div class="upload-success hidden" id="resumeUploadSuccess">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <p style="margin-top:8px;font-weight:500;color:var(--success)">Profile populated successfully!</p>
                <p class="text-muted text-sm">Review and edit the auto-filled fields below</p>
            </div>
        </div>
        <div class="upload-error hidden" id="resumeUploadError" style="margin-top:10px">
            <div class="alert alert-error" style="margin:0"><span id="resumeErrorText"></span></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            Candidate Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('candidates.store') }}" id="candidateForm">
            @csrf
            <div class="form-row">
                <div class="form-group"><label>First Name *</label><input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required></div>
                <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="{{ old('phone') }}"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Current Company</label><input type="text" name="current_company" class="form-control" value="{{ old('current_company') }}"></div>
                <div class="form-group"><label>Current Title</label><input type="text" name="current_title" class="form-control" value="{{ old('current_title') }}"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Experience (years)</label><input type="number" name="experience_years" class="form-control" value="{{ old('experience_years') }}" step="0.5" min="0"></div>
                <div class="form-group"><label>Source *</label>
                    <select name="source" class="form-control">
                        <option value="upload">Upload</option><option value="referral">Referral</option><option value="direct">Direct</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Skills</label>
                <input type="text" name="skills" class="form-control tag-input" value="{{ old('skills') }}" placeholder="Type a skill and press Enter">
            </div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea></div>
            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create Candidate
                </button>
                <a href="{{ route('candidates.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
