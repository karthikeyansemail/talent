@extends('layouts.app')
@section('title', 'Create Project')
@section('page-title', 'Create Project')
@section('content')
<div class="page-header"><h1>Create Project</h1></div>

{{-- AI Auto-fill from Requirement Document --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 0-4 4c0 2 2 3 2 6H6a2 2 0 0 0-2 2v2h16v-2a2 2 0 0 0-2-2h-4c0-3 2-4 2-6a4 4 0 0 0-4-4z"/><path d="M9 18v1a3 3 0 0 0 6 0v-1"/></svg>
            AI Auto-fill from Requirement Document
        </span>
    </div>
    <div class="card-body">
        <div id="projectUploadArea" class="file-upload-area" data-url="{{ route('projects.parseDocument') }}">
            <input type="file" id="projectFileInput" accept=".pdf,.docx" style="display:none">
            <div id="projectUploadContent">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                <p style="margin:8px 0 4px;font-weight:500;color:var(--gray-700)">Upload a requirement document</p>
                <p style="margin:0;font-size:12.5px;color:var(--gray-500)">Drop a PDF or DOCX file here, or click to browse. AI will extract project details.</p>
            </div>
            <div id="projectUploadLoading" class="hidden">
                <div class="spinner"></div>
                <p style="margin:8px 0 0;font-size:13px;color:var(--gray-500)">AI is analyzing your document...</p>
            </div>
            <div id="projectUploadSuccess" class="hidden">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <p style="margin:8px 0 0;font-weight:500;color:var(--success)">Fields populated! Review and adjust below.</p>
            </div>
            <div id="projectUploadError" class="hidden" style="margin-top:8px">
                <p style="margin:0;font-size:12.5px;color:var(--danger)" id="projectErrorText"></p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            Project Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('projects.store') }}">
            @csrf
            <div class="form-group"><label>Project Name *</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea></div>
            <div class="form-group"><label>Required Skills</label>
                <div class="tag-input-wrapper"><input type="hidden" name="required_skills" value="{{ old('required_skills') }}"><input type="text" placeholder="Type skill and press Enter"></div>
            </div>
            <div class="form-group"><label>Required Technologies</label>
                <div class="tag-input-wrapper"><input type="hidden" name="required_technologies" value="{{ old('required_technologies') }}"><input type="text" placeholder="Type technology and press Enter"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Complexity *</label>
                    <select name="complexity_level" class="form-control">@foreach(['low','medium','high','critical'] as $c)<option value="{{ $c }}" {{ old('complexity_level','medium')===$c?'selected':'' }}>{{ ucfirst($c) }}</option>@endforeach</select>
                </div>
                <div class="form-group"><label>Status *</label>
                    <select name="status" class="form-control">@foreach(['planning','active'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select>
                </div>
            </div>
            <div class="form-group"><label>Domain Context</label><textarea name="domain_context" class="form-control" rows="2">{{ old('domain_context') }}</textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}"></div>
                <div class="form-group"><label>End Date</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}"></div>
            </div>
            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create Project
                </button>
                <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
