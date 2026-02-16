@extends('layouts.app')
@section('title', 'Integrations')
@section('page-title', 'Integrations')
@section('content')
<div class="page-header"><h1>Integrations</h1></div>

{{-- Tabs --}}
<div class="tabs" data-tabs>
    <button class="tab active" data-tab="tab-jira">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        Jira
    </button>
    <button class="tab" data-tab="tab-zoho-projects">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        Zoho Projects
    </button>
    <button class="tab" data-tab="tab-zoho-people">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Zoho People
    </button>
</div>

{{-- Jira Tab --}}
<div class="tab-content active" id="tab-jira">
    <div class="card" style="margin-bottom: 24px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                Jira Connections
            </span>
        </div>
        <table>
            <thead><tr><th>Base URL</th><th>Email</th><th>Status</th><th>Last Synced</th><th></th></tr></thead>
            <tbody>
            @forelse($jiraConnections as $conn)
            <tr>
                <td>{{ $conn->jira_base_url }}</td>
                <td>{{ $conn->jira_email }}</td>
                <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
                <td class="text-sm text-muted">{{ $conn->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                <td>
                    <div class="table-actions">
                        <form method="POST" action="{{ route('jira-connections.test', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-secondary">Test</button></form>
                        <form method="POST" action="{{ route('jira-connections.sync', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-primary">Sync</button></form>
                        <form method="POST" action="{{ route('jira-connections.destroy', $conn) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this connection?')">Remove</button></form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="empty-state"><p>No Jira connections</p><p class="empty-hint">Add a connection to start syncing employee tasks from Jira</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Add Jira Connection Form --}}
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Jira Connection
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
                <button type="submit" class="btn btn-primary">Add Jira Connection</button>
            </form>
        </div>
    </div>
</div>

{{-- Zoho Projects Tab --}}
<div class="tab-content" id="tab-zoho-projects">
    <div class="card" style="margin-bottom: 24px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Zoho Projects Connections
            </span>
        </div>
        <table>
            <thead><tr><th>Portal Name</th><th>Status</th><th>Last Synced</th><th></th></tr></thead>
            <tbody>
            @forelse($zohoConnections as $conn)
            <tr>
                <td>{{ $conn->portal_name }}</td>
                <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
                <td class="text-sm text-muted">{{ $conn->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                <td>
                    <div class="table-actions">
                        <form method="POST" action="{{ route('settings.integrations.zohoProjects.test', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-secondary">Test</button></form>
                        <form method="POST" action="{{ route('settings.integrations.zohoProjects.destroy', $conn) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this connection?')">Remove</button></form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4"><div class="empty-state"><p>No Zoho Projects connections</p><p class="empty-hint">Add a connection to sync project tasks from Zoho</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Zoho Projects Connection
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.integrations.zohoProjects.store') }}">
                @csrf
                <div class="form-group">
                    <label>Portal Name *</label>
                    <input type="text" name="portal_name" class="form-control" value="{{ old('portal_name') }}" placeholder="Your Zoho Projects portal name" required>
                </div>
                <div class="form-group">
                    <label>Auth Token *</label>
                    <input type="password" name="auth_token" class="form-control" required>
                    <div class="form-hint">Generate from Zoho API Console</div>
                </div>
                <button type="submit" class="btn btn-primary">Add Zoho Connection</button>
            </form>
        </div>
    </div>
</div>

{{-- Zoho People Tab --}}
<div class="tab-content" id="tab-zoho-people">
    <div class="card" style="margin-bottom: 24px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Zoho People Connections
            </span>
        </div>
        <table>
            <thead><tr><th>Portal Name</th><th>Status</th><th>Last Synced</th><th></th></tr></thead>
            <tbody>
            @forelse($zohoPeopleConnections as $conn)
            <tr>
                <td>{{ $conn->portal_name }}</td>
                <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
                <td class="text-sm text-muted">{{ $conn->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                <td>
                    <div class="table-actions">
                        <form method="POST" action="{{ route('settings.integrations.zohoPeople.test', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-secondary">Test</button></form>
                        <form method="POST" action="{{ route('settings.integrations.zohoPeople.sync', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-primary">Sync</button></form>
                        <form method="POST" action="{{ route('settings.integrations.zohoPeople.destroy', $conn) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this connection?')">Remove</button></form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4"><div class="empty-state"><p>No Zoho People connections</p><p class="empty-hint">Add a connection to sync employees from Zoho People HR</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Zoho People Connection
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.integrations.zohoPeople.store') }}">
                @csrf
                <div class="form-group">
                    <label>Portal Name *</label>
                    <input type="text" name="portal_name" class="form-control" value="{{ old('portal_name') }}" placeholder="Your Zoho People portal name" required>
                </div>
                <div class="form-group">
                    <label>Auth Token *</label>
                    <input type="password" name="auth_token" class="form-control" required>
                    <div class="form-hint">Generate from Zoho API Console</div>
                </div>
                <button type="submit" class="btn btn-primary">Add Zoho People Connection</button>
            </form>
        </div>
    </div>
</div>

@endsection
