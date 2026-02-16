@extends('layouts.app')
@section('title', 'Add Jira Connection')
@section('page-title', 'Add Jira Connection')
@section('content')
<div class="page-header"><h1>Add Jira Connection</h1></div>
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            Connection Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('jira-connections.store') }}">
            @csrf
            <div class="form-group">
                <label>Jira Base URL *</label>
                <input type="url" name="jira_base_url" class="form-control" value="{{ old('jira_base_url') }}" placeholder="https://yourcompany.atlassian.net" required>
            </div>
            <div class="form-group">
                <label>Jira Email *</label>
                <input type="email" name="jira_email" class="form-control" value="{{ old('jira_email') }}" required>
            </div>
            <div class="form-group">
                <label>API Token *</label>
                <input type="password" name="jira_api_token" class="form-control" required>
                <div class="form-hint">Generate at id.atlassian.com/manage-profile/security/api-tokens</div>
            </div>
            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Connection
                </button>
                <a href="{{ route('jira-connections.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
