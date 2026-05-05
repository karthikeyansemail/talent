@extends('layouts.app')
@section('title', 'New Placement Drive')
@section('page-title', 'New Placement Drive')
@section('content')
<div class="page-header">
    <h1>New Placement Drive</h1>
    <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
        Upload the company hiring document and AI will extract role details. You can also fill the form manually below.
    </p>
</div>

{{-- AI Document Parser --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            AI Auto-Fill from Company Document
        </span>
    </div>
    <div class="card-body">
        <input type="file" id="parseDoc" accept=".pdf,.docx" style="display:none">
        <div style="display:flex;gap:12px;align-items:center">
            <button type="button" class="btn btn-secondary" id="uploadBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload PDF / DOCX
            </button>
            <span id="parseStatus" class="text-muted" style="font-size:13px">Optional — fill form manually if you don't have a document.</span>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('placement.drives.store') }}" id="driveForm">
    @csrf
    <input type="hidden" name="temp_doc_path" id="tempDocPath">
    <input type="hidden" name="extracted_text" id="extractedText">

    <div class="card">
        <div class="card-header"><span class="card-header-icon">Drive Details</span></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Company Name *</label>
                    <input type="text" name="company_name" id="company_name" class="form-control" value="{{ old('company_name') }}" required>
                </div>
                <div class="form-group">
                    <label>Role Title *</label>
                    <input type="text" name="role_title" id="role_title" class="form-control" value="{{ old('role_title') }}" placeholder="Software Engineer (Graduate Trainee)" required>
                </div>
            </div>

            <div class="form-group">
                <label>Description / Hiring Brief</label>
                <textarea name="description" id="description" class="form-control" rows="4" placeholder="Company background, role expectations, training plan...">{{ old('description') }}</textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Eligible Courses</label>
                    <input type="text" name="eligible_courses" id="eligible_courses" class="form-control" value="{{ old('eligible_courses') }}" placeholder="B.Tech CSE, B.Tech IT, MCA">
                    <small class="text-muted">Comma-separated</small>
                </div>
                <div class="form-group">
                    <label>Eligible Batch Years</label>
                    <input type="text" name="eligible_batch_years" class="form-control" value="{{ old('eligible_batch_years') }}" placeholder="2026, 2027">
                    <small class="text-muted">Comma-separated</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Min CGPA</label>
                    <input type="number" name="min_cgpa" step="0.01" min="0" max="10" class="form-control" value="{{ old('min_cgpa') }}" placeholder="7.50">
                </div>
                <div class="form-group">
                    <label>Package (LPA)</label>
                    <input type="number" name="package_lpa" min="0" class="form-control" value="{{ old('package_lpa') }}" placeholder="12">
                    <small class="text-muted">Lakhs per annum</small>
                </div>
            </div>

            <div class="form-group">
                <label>Required Skills</label>
                <input type="text" name="required_skills" id="required_skills" class="form-control" value="{{ old('required_skills') }}" placeholder="DSA, OOP, DBMS, Python or Java">
                <small class="text-muted">Comma-separated</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Drive Date</label>
                    <input type="date" name="drive_date" class="form-control" value="{{ old('drive_date') }}">
                </div>
                <div class="form-group">
                    <label>Test Format *</label>
                    <select name="test_format" class="form-control" required>
                        <option value="aptitude">Aptitude Test Only</option>
                        <option value="aptitude_plus_interview" selected>Aptitude + Interview</option>
                        <option value="technical">Technical Round Only</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Status *</label>
                <select name="status" class="form-control" style="max-width:280px" required>
                    <option value="draft">Draft (not visible to students yet)</option>
                    <option value="open" selected>Open</option>
                </select>
            </div>

            <div class="flex gap-10" style="margin-top:20px">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Create Drive
                </button>
                <a href="{{ route('placement.drives.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<script>
(function() {
    var btn = document.getElementById('uploadBtn');
    var input = document.getElementById('parseDoc');
    var status = document.getElementById('parseStatus');
    btn.addEventListener('click', function() { input.click(); });
    input.addEventListener('change', function() {
        if (!input.files.length) return;
        var fd = new FormData();
        fd.append('document', input.files[0]);
        status.textContent = 'Parsing document with AI…';
        status.style.color = 'var(--primary)';

        fetch('{{ route('placement.drives.parseDocument') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: fd
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                status.textContent = data.error;
                status.style.color = 'var(--danger)';
                return;
            }
            // Map AI-extracted fields into the form
            if (data.title || data.role_title)         document.getElementById('role_title').value = data.title || data.role_title;
            if (data.company_name || data.company)     document.getElementById('company_name').value = data.company_name || data.company;
            if (data.description)                       document.getElementById('description').value = data.description;
            if (Array.isArray(data.required_skills) && data.required_skills.length)
                document.getElementById('required_skills').value = data.required_skills.join(', ');
            if (data._temp_file_path)                   document.getElementById('tempDocPath').value = data._temp_file_path;
            if (data._extracted_text)                   document.getElementById('extractedText').value = data._extracted_text;
            status.textContent = '✓ Filled from document. Review and adjust if needed.';
            status.style.color = 'var(--success)';
        })
        .catch(err => {
            status.textContent = 'Parse failed: ' + err.message;
            status.style.color = 'var(--danger)';
        });
    });
})();
</script>
@endsection
