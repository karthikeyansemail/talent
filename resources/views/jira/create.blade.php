@extends('layouts.app')
@section('title', 'Add Jira Connection')
@section('content')
<div class="page-header"><h1>Add Jira Connection</h1></div>
<div class="card">
    <form method="POST" action="{{ route('jira-connections.store') }}">
        @csrf
        <div class="form-group"><label>Jira Base URL *</label><input type="url" name="jira_base_url" class="form-control" value="{{ old('jira_base_url') }}" placeholder="https://yourcompany.atlassian.net" required></div>
        <div class="form-group"><label>Jira Email *</label><input type="email" name="jira_email" class="form-control" value="{{ old('jira_email') }}" required></div>
        <div class="form-group"><label>API Token *</label><input type="password" name="jira_api_token" class="form-control" required><div class="form-hint">Generate at id.atlassian.com/manage-profile/security/api-tokens</div></div>
        <div class="flex gap-10">
            <button type="submit" class="btn btn-primary">Add Connection</button>
            <a href="{{ route('jira-connections.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
