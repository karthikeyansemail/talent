@extends('layouts.app')
@section('title', 'Import Employees')
@section('page-title', 'Import Employees')
@section('content')
<div class="page-header">
    <h1>Import Employees</h1>
    <a href="{{ route('employees.index') }}" class="btn btn-secondary">Back to Employees</a>
</div>

@if(isset($preview))
{{-- Preview & Confirm --}}
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            Preview Import ({{ $totalRows }} rows)
        </span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    @foreach($headers as $h)
                    <th>{{ str_replace('_', ' ', ucfirst($h)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($preview as $row)
                <tr>
                    @foreach($headers as $h)
                    <td>{{ $row[$h] ?? '' }}</td>
                    @endforeach
                </tr>
                @endforeach
                @if($totalRows > 10)
                <tr><td colspan="{{ count($headers) }}" class="text-center text-muted">... and {{ $totalRows - 10 }} more rows</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    <div class="card-footer" style="display:flex;gap:12px;align-items:center">
        <form method="POST" action="{{ route('employees.import.confirm') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Confirm Import ({{ $totalRows }} employees)</button>
        </form>
        <a href="{{ route('employees.import') }}" class="btn btn-secondary">Cancel</a>
    </div>
</div>
@else
{{-- Upload Form --}}
<div class="tabs" data-tabs>
    <button class="tab active" data-tab="tab-spreadsheet-import">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Spreadsheet Upload
    </button>
    <button class="tab" data-tab="tab-hr-integration">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        HR Integration
    </button>
</div>

{{-- Spreadsheet Upload Tab --}}
<div class="tab-content active" id="tab-spreadsheet-import">
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload Employee Spreadsheet
            </span>
            <a href="{{ route('employees.import.template') }}" class="btn btn-sm btn-secondary">Download Template</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info" style="margin-bottom:20px">
                <div>
                    <strong>Required columns:</strong> first_name, last_name, email<br>
                    <strong>Optional columns:</strong> department, designation, skills (comma-separated)
                </div>
            </div>

            <form method="POST" action="{{ route('employees.import.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>Spreadsheet File *</label>
                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx" required>
                    <div class="form-hint">CSV or XLSX format, maximum 5MB</div>
                </div>
                <button type="submit" class="btn btn-primary">Upload & Preview</button>
            </form>
        </div>
    </div>
</div>

{{-- HR Integration Tab --}}
<div class="tab-content" id="tab-hr-integration">
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                Zoho People Integration
            </span>
        </div>
        <div class="card-body">
            @php
                $zohoConn = Auth::user()->organization->zohoPeopleConnections()->where('is_active', true)->first();
            @endphp

            @if($zohoConn)
            <div class="alert alert-success" style="margin-bottom:20px">
                <span>Connected to Zoho People portal: <strong>{{ $zohoConn->portal_name }}</strong>
                @if($zohoConn->last_synced_at)
                    &mdash; Last synced {{ $zohoConn->last_synced_at->diffForHumans() }}
                @endif
                </span>
            </div>
            <form method="POST" action="{{ route('employees.import.syncZohoPeople') }}">
                @csrf
                <button type="submit" class="btn btn-primary">Sync Employees from Zoho People</button>
            </form>
            @else
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                <p>No Zoho People connection configured</p>
                <p class="empty-hint">
                    @if(in_array(auth()->user()->role, ['org_admin', 'super_admin']))
                    <a href="{{ route('settings.integrations.index') }}">Configure in Settings &rarr; Integrations</a>
                    @else
                    Ask your organization admin to set up the Zoho People integration
                    @endif
                </p>
            </div>
            @endif
        </div>
    </div>
</div>
@endif
@endsection
