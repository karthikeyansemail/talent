@extends('layouts.app')
@section('title', 'New Workspace')
@section('page-title', 'New Workspace')
@section('content')
<div class="page-header">
    <h1>New Workspace</h1>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Workspace Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('settings.organizations.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label>Organization Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g. Acme Corp">
                </div>
                <div class="form-group">
                    <label>Domain</label>
                    <input type="text" name="domain" class="form-control" value="{{ old('domain') }}" placeholder="e.g. acme.com">
                    <small class="text-muted">Optional. For reference only — does not restrict user emails.</small>
                </div>
            </div>

            <div style="border-top:1px solid var(--gray-200);margin:20px 0;padding-top:20px">
                <h3 style="margin:0 0 16px;font-size:15px;font-weight:600">Initial Admin Account</h3>
                <p class="text-muted" style="margin-bottom:16px">An org_admin user will be created for this organization.</p>
                <div class="form-row">
                    <div class="form-group">
                        <label>Admin Name *</label>
                        <input type="text" name="admin_name" class="form-control" value="{{ old('admin_name') }}" required placeholder="e.g. John Admin">
                    </div>
                    <div class="form-group">
                        <label>Admin Email *</label>
                        <input type="email" name="admin_email" class="form-control" value="{{ old('admin_email') }}" required placeholder="e.g. admin@acme.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Admin Password *</label>
                        <input type="password" name="admin_password" class="form-control" required placeholder="Minimum 6 characters">
                    </div>
                    <div class="form-group"></div>
                </div>
            </div>

            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create Workspace
                </button>
                <a href="{{ route('settings.organizations.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
