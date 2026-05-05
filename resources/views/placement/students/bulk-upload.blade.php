@extends('layouts.app')
@section('title', 'Bulk Upload Students')
@section('page-title', 'Bulk Upload Students')
@section('content')
<div class="page-header">
    <h1>Bulk Upload Students</h1>
    <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
        Upload a CSV with student details. Duplicates (matching email) are skipped.
    </p>
</div>

{{-- Step 1: Download template --}}
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Step 1 — Download CSV Template
        </span>
    </div>
    <div class="card-body">
        <p style="margin:0 0 12px;color:var(--text-muted);font-size:13px">
            Template includes 4 example rows across different departments. Required: <code>first_name</code>, <code>email</code>. All other columns are optional.
        </p>
        <a href="{{ route('placement.students.template') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download students-template.csv
        </a>
        <div style="margin-top:14px;padding:12px 14px;background:var(--bg-muted);border-radius:8px;font-size:12px;color:var(--text-muted);font-family:monospace;line-height:1.6;overflow-x:auto;white-space:nowrap">
            first_name, last_name, email, enrollment_number, department, course, batch_year, phone, skills
        </div>
        @if($departments->count() > 0)
        <div style="margin-top:14px;font-size:12px;color:var(--text-muted)">
            <strong>Recognized department names</strong> (use exactly as written in CSV's <code>department</code> column):
            <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:6px">
                @foreach($departments as $d)<span class="tag" style="font-size:11px">{{ $d->name }}</span>@endforeach
            </div>
            <div style="margin-top:6px">Unmatched department names are left blank (you can fix them after upload).</div>
        </div>
        @endif
    </div>
</div>

{{-- Step 2: Upload --}}
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Step 2 — Upload Filled CSV
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('placement.students.bulkStore') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>CSV File *</label>
                <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
                <small class="text-muted">Max 5 MB. UTF-8 encoded.</small>
            </div>
            <div class="form-group">
                <label>Default Department (optional)</label>
                <select name="default_department_id" class="form-control" style="max-width:380px">
                    <option value="">— No default —</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ $preselectDept == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
                <small class="text-muted">Used for rows where the CSV's <code>department</code> column is empty or unrecognized. Useful when you upload one CSV per department.</small>
            </div>
            <div style="padding:10px 14px;background:#fff7ed;border-left:3px solid #f97316;border-radius:6px;font-size:13px;color:var(--text);margin-bottom:16px">
                <strong>Note:</strong> Students with email already on file are skipped. Skills column accepts <code>;</code> or <code>,</code> as separator.
                Department in CSV is matched by exact name; unmatched names fall back to the default above.
            </div>
            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">Upload &amp; Import</button>
                <a href="{{ route('placement.students.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
