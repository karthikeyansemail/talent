@extends('layouts.app')
@section('title', 'Edit Workspace')
@section('page-title', 'Edit Workspace')
@section('content')
<div class="page-header">
    <h1>Edit: {{ $organization->name }}</h1>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Workspace Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('settings.organizations.update', $organization) }}">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label>Organization Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $organization->name) }}" required>
                </div>
                <div class="form-group">
                    <label>Domain</label>
                    <input type="text" name="domain" class="form-control" value="{{ old('domain', $organization->domain) }}" placeholder="e.g. acme.com">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ $organization->is_active ? 'checked' : '' }} style="accent-color:var(--primary)">
                        Active
                    </label>
                    <small class="text-muted">Inactive workspaces cannot be accessed by their users.</small>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="hidden" name="is_premium" value="0">
                        <input type="checkbox" name="is_premium" value="1" {{ $organization->is_premium ? 'checked' : '' }} style="accent-color:var(--primary)">
                        Premium
                    </label>
                    <small class="text-muted">Enables Intelligence features (Signal Dashboard, Signal Config).</small>
                </div>
            </div>

            {{-- Subscription Plan (super admin only) --}}
            @if(Auth::user()->isSuperAdmin())
            <div style="margin-top:24px;border-top:1px solid var(--gray-200);padding-top:20px">
                <h3 style="font-size:14px;font-weight:600;color:var(--gray-700);margin:0 0 16px">Subscription</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Plan</label>
                        <select name="subscription_plan" class="form-control">
                            <option value="free" {{ ($organization->subscription_plan ?? 'free') === 'free' ? 'selected' : '' }}>Free</option>
                            <option value="cloud_enterprise" {{ ($organization->subscription_plan ?? '') === 'cloud_enterprise' ? 'selected' : '' }}>Cloud Enterprise</option>
                            <option value="self_hosted" {{ ($organization->subscription_plan ?? '') === 'self_hosted' ? 'selected' : '' }}>Self-Hosted Enterprise</option>
                        </select>
                        <small class="text-muted">Free: 3 jobs, 50 candidates, no AI, no resource allocation.</small>
                    </div>
                    <div class="form-group">
                        <label>Subscription Expires At</label>
                        <input type="date" name="subscription_expires_at" class="form-control"
                            value="{{ old('subscription_expires_at', optional($organization->subscription_expires_at)->format('Y-m-d')) }}">
                        <small class="text-muted">Leave blank for no expiry (self-hosted).</small>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Support Expires At</label>
                        <input type="date" name="support_expires_at" class="form-control"
                            value="{{ old('support_expires_at', optional($organization->support_expires_at)->format('Y-m-d')) }}">
                        <small class="text-muted">For self-hosted: when the 5-year support period ends.</small>
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;padding-bottom:4px">
                        <div style="background:var(--gray-50);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--gray-600)">
                            Current plan: <strong>{{ $organization->planLabel() }}</strong>
                            @if($organization->subscription_expires_at)
                                &nbsp;· expires {{ $organization->subscription_expires_at->format('d M Y') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div style="margin-top:8px;padding:12px 16px;background:var(--gray-50);border-radius:8px;font-size:13px;color:var(--gray-500)">
                {{ $organization->users_count }} user(s) in this workspace
            </div>

            <div class="flex gap-10" style="margin-top:20px">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Save Changes
                </button>
                <a href="{{ route('settings.organizations.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection
