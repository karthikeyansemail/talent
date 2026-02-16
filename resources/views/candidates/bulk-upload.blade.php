@extends('layouts.app')
@section('title', 'Add Multiple Candidates')
@section('page-title', 'Add Multiple Candidates')
@section('content')
<div class="page-header">
    <h1>Add Multiple Candidates</h1>
    <a href="{{ route('candidates.index') }}" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Back to Candidates
    </a>
</div>

<div class="card" style="margin-bottom: 24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            Bulk Resume Upload
        </span>
    </div>
    <div class="card-body">
        <p class="text-muted" style="margin-bottom: 16px">
            Upload multiple resumes (PDF or DOCX) and AI will automatically create candidate profiles from each file.
            You can upload up to 20 files at once. Each resume will be parsed to extract name, email, skills, and experience.
        </p>

        @if($errors->any())
        <div class="alert alert-error" style="margin-bottom: 16px">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('candidates.bulkStore') }}" enctype="multipart/form-data" id="bulkUploadForm">
            @csrf
            <input type="file" id="bulkFileInput" name="resumes[]" multiple accept=".pdf,.docx" style="display:none">

            <div class="ai-upload-area" id="bulkUploadArea">
                <div class="upload-content" id="bulkUploadContent">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary)"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/><polyline points="16 16 12 12 8 16"/></svg>
                    <p style="margin-top:8px;font-weight:500">Drop resumes here or click to select files</p>
                    <p class="text-muted text-sm">PDF or DOCX files, up to 10MB each, maximum 20 files</p>
                </div>
            </div>

            <div id="bulkFileList" class="bulk-file-list" style="display:none"></div>

            <div id="bulkFileCount" class="bulk-file-count" style="display:none">
                <span id="bulkFileCountText">0 files selected</span>
            </div>

            <div class="flex gap-10" style="margin-top: 20px">
                <button type="submit" class="btn btn-primary" id="bulkSubmitBtn" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
                    Upload & Create Candidates
                </button>
                <a href="{{ route('candidates.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
