@extends('layouts.app')
@section('title', 'Signal Configuration')
@section('page-title', 'Signal Configuration')
@section('content')
<div class="page-header">
    <h1>Signal Configuration</h1>
    <a href="{{ route('intelligence.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
</div>

<div class="tabs" data-tabs>
    <button class="tab active" data-tab="tab-sources">Signal Sources</button>
    <button class="tab" data-tab="tab-integrations">Communication Integrations</button>
    <button class="tab" data-tab="tab-sprint">Sprint Sheets</button>
</div>

{{-- Signal Sources Tab --}}
<div class="tab-content active" id="tab-sources">
    <div class="card" style="margin-bottom:24px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                Configured Signal Sources
            </span>
        </div>
        <table>
            <thead><tr><th>Type</th><th>Name</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($signalSources as $source)
            <tr>
                <td><span class="badge badge-blue">{{ $source->type }}</span></td>
                <td>{{ $source->name }}</td>
                <td>@include('components.stage-badge', ['stage' => $source->is_active ? 'active' : 'closed'])</td>
                <td>
                    <form method="POST" action="{{ route('intelligence.config.destroySource', $source) }}" style="display:inline">@csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this source?')">Remove</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4"><div class="empty-state"><p>No signal sources configured</p><p class="empty-hint">Add signal sources to start collecting employee performance data</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header">Add Signal Source</div>
        <div class="card-body">
            <form method="POST" action="{{ route('intelligence.config.storeSource') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label>Source Type *</label>
                        <select name="type" class="form-control" required>
                            <option value="">Select type...</option>
                            <option value="jira">Jira</option>
                            <option value="zoho_projects">Zoho Projects</option>
                            <option value="slack">Slack</option>
                            <option value="teams">Microsoft Teams</option>
                            <option value="sprint_sheet">Sprint Sheet</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Main Jira Board" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add Source</button>
            </form>
        </div>
    </div>
</div>

{{-- Communication Integrations Tab --}}
<div class="tab-content" id="tab-integrations">
    <div class="card" style="margin-bottom:24px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                Communication Integrations
            </span>
        </div>
        <table>
            <thead><tr><th>Type</th><th>Name</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($integrationConnections as $conn)
            <tr>
                <td><span class="badge badge-purple">{{ ucfirst($conn->type) }}</span></td>
                <td>{{ $conn->name }}</td>
                <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
                <td>
                    <form method="POST" action="{{ route('intelligence.config.destroyIntegration', $conn) }}" style="display:inline">@csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove?')">Remove</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="4"><div class="empty-state"><p>No communication integrations</p><p class="empty-hint">Connect Slack or Teams to gather collaboration signals</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header">Add Communication Integration</div>
        <div class="card-body">
            <form method="POST" action="{{ route('intelligence.config.storeIntegration') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label>Type *</label>
                        <select name="type" class="form-control" required>
                            <option value="">Select type...</option>
                            <option value="slack">Slack</option>
                            <option value="teams">Microsoft Teams</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Engineering Slack" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Webhook URL</label>
                    <input type="url" name="webhook_url" class="form-control" placeholder="https://hooks.slack.com/...">
                </div>
                <div class="form-group">
                    <label>API Token</label>
                    <input type="password" name="api_token" class="form-control">
                    <div class="form-hint">OAuth token for reading channel data</div>
                </div>
                <button type="submit" class="btn btn-primary">Add Integration</button>
            </form>
        </div>
    </div>
</div>

{{-- Sprint Sheets Tab --}}
<div class="tab-content" id="tab-sprint">
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Upload Sprint Sheet
            </span>
        </div>
        <div class="card-body">
            <div class="alert alert-info" style="margin-bottom:20px">
                <div>
                    <strong>Expected columns:</strong> employee_email, sprint_name, start_date, end_date, planned_points, completed_points, tasks_planned, tasks_completed
                </div>
            </div>

            <form method="POST" action="{{ route('intelligence.config.uploadSprintSheet') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>Sprint Sheet File *</label>
                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx" required>
                    <div class="form-hint">CSV or XLSX format, maximum 5MB</div>
                </div>
                <button type="submit" class="btn btn-primary">Upload & Process</button>
            </form>
        </div>
    </div>
</div>
@endsection
