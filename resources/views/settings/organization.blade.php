@extends('layouts.app')
@section('title', 'Workspace Settings')
@section('page-title', 'Workspace Settings')
@section('content')
<div class="page-header">
    <h1>Workspace Settings</h1>
</div>
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            General Settings
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('settings.organization.update') }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Organization Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $organization->name) }}" required>
            </div>
            <div class="form-group">
                <label>Domain</label>
                <input type="text" name="domain" class="form-control" value="{{ old('domain', $organization->domain) }}" placeholder="example.com">
            </div>
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Changes
            </button>
        </form>
    </div>
</div>

{{-- Active modules summary (read-only for org admin — super admin manages from All Workspaces) --}}
@php
    $_allModules = config('modules.modules', []);
    $_enabled    = $organization->enabled_modules ?: config('modules.legacy_default', []);
    $_template   = $organization->industry_template;
    $_templates  = config('modules.templates', []);
@endphp
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            Active Modules
        </span>
    </div>
    <div class="card-body">
        @if($_template)
            <p style="margin:0 0 12px;font-size:13px;color:var(--gray-600)">
                Industry template: <strong>{{ $_templates[$_template]['label'] ?? $_template }}</strong>
            </p>
        @endif
        <div style="display:flex;flex-wrap:wrap;gap:6px">
            @foreach($_allModules as $key => $mod)
            <span style="background:{{ in_array($key, $_enabled, true) ? 'var(--primary-50,#eff6ff)' : 'var(--gray-50)' }};color:{{ in_array($key, $_enabled, true) ? 'var(--primary)' : 'var(--gray-400)' }};border:1px solid {{ in_array($key, $_enabled, true) ? 'var(--primary-100,#dbeafe)' : 'var(--gray-200)' }};border-radius:6px;padding:6px 10px;font-size:13px;font-weight:500">
                {{ in_array($key, $_enabled, true) ? '✓' : '○' }} {{ $mod['label'] }}
            </span>
            @endforeach
        </div>
        <p style="margin:12px 0 0;font-size:12px;color:var(--gray-500)">
            To change which modules are active, contact your platform administrator.
        </p>
    </div>
</div>
@endsection
