@extends('layouts.app')
@section('title', 'Integrations')
@section('page-title', 'Integrations')
@section('content')
<div class="page-header"><h1>Integrations</h1></div>

{{-- Category Tabs --}}
<div class="tabs" data-tabs style="margin-bottom:0;border-bottom:none">
    <button class="tab active" data-tab="tab-project-mgmt" style="display:flex;flex-direction:column;align-items:flex-start;gap:2px;padding:12px 20px;height:auto;min-width:200px">
        <span style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
            Project Management
        </span>
        <span style="font-size:11.5px;font-weight:400;color:var(--gray-500);padding-left:24px">Jira · Zoho Projects · DevOps Boards</span>
    </button>
    <button class="tab" data-tab="tab-hr-systems" style="display:flex;flex-direction:column;align-items:flex-start;gap:2px;padding:12px 20px;height:auto;min-width:200px">
        <span style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            HR Systems
        </span>
        <span style="font-size:11.5px;font-weight:400;color:var(--gray-500);padding-left:24px">Zoho People · OrangeHRM</span>
    </button>
    <button class="tab" data-tab="tab-communication" style="display:flex;flex-direction:column;align-items:flex-start;gap:2px;padding:12px 20px;height:auto;min-width:200px">
        <span style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Workforce Comms
        </span>
        <span style="font-size:11.5px;font-weight:400;color:var(--gray-500);padding-left:24px">Slack · Microsoft Teams</span>
    </button>
    <button class="tab" data-tab="tab-code-signals" style="display:flex;flex-direction:column;align-items:flex-start;gap:2px;padding:12px 20px;height:auto;min-width:200px">
        <span style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            Source Code Signals
        </span>
        <span style="font-size:11.5px;font-weight:400;color:var(--gray-500);padding-left:24px">GitHub · Code Intelligence</span>
    </button>
</div>

{{-- ===== PROJECT MANAGEMENT TAB ===== --}}
<div class="tab-content active" id="tab-project-mgmt">

    {{-- Section: Jira --}}
    <div style="display:flex;align-items:center;gap:10px;margin:24px 0 14px">
        <div style="width:32px;height:32px;background:#0052cc;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </div>
        <div>
            <div style="font-weight:600;font-size:15px;color:var(--gray-800)">Jira</div>
            <div style="font-size:12px;color:var(--gray-500)">Sync employee task data, sprint performance, and work history from Atlassian Jira</div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
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

    <div class="card" style="margin-bottom:32px">
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

    {{-- Section: Zoho Projects --}}
    <div style="display:flex;align-items:center;gap:10px;margin:0 0 14px">
        <div style="width:32px;height:32px;background:#e65c19;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div>
            <div style="font-weight:600;font-size:15px;color:var(--gray-800)">Zoho Projects</div>
            <div style="font-size:12px;color:var(--gray-500)">Sync project tasks and team activity from Zoho Projects</div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
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

    <div class="card" style="margin-bottom:32px">
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

    {{-- Section: Spreadsheet Import --}}
    <div style="display:flex;align-items:center;gap:10px;margin:0 0 14px">
        <div style="width:32px;height:32px;background:#16a34a;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <div>
            <div style="font-weight:600;font-size:15px;color:var(--gray-800)">Spreadsheet Import</div>
            <div style="font-size:12px;color:var(--gray-500)">Upload CSV/Excel files to bulk-import task data per project</div>
        </div>
    </div>

    <div class="card" style="margin-bottom:32px">
        <div class="card-body" style="display:flex;align-items:flex-start;gap:20px">
            <div style="flex:1">
                <p style="margin:0 0 12px;color:var(--gray-700);font-size:13.5px">Sprint and task spreadsheets are uploaded per project. Navigate to a project and use the <strong>Sprint Data</strong> tab to upload CSV/Excel files with task data for that project.</p>
                <p style="margin:0;color:var(--gray-500);font-size:12.5px">Expected columns: <code style="background:var(--gray-100);padding:1px 5px;border-radius:3px;font-size:12px">employee_email, task_key, summary, status, story_points, sprint_name, completed_at</code></p>
            </div>
            <a href="{{ route('projects.index') }}" class="btn btn-secondary" style="flex-shrink:0;white-space:nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Go to Projects
            </a>
        </div>
    </div>

    {{-- Section: Microsoft DevOps Boards --}}
    <div style="display:flex;align-items:center;gap:10px;margin:0 0 14px">
        <div style="width:32px;height:32px;background:#0078d4;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
        </div>
        <div>
            <div style="font-weight:600;font-size:15px;color:var(--gray-800)">Microsoft DevOps Boards</div>
            <div style="font-size:12px;color:var(--gray-500)">Sync work items, sprint boards, and team velocity from Azure DevOps Boards</div>
        </div>
    </div>

    @php $devopsConnections = $integrationConnections->get('devops_boards', collect()); @endphp
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                DevOps Boards Connections
            </span>
        </div>
        <table>
            <thead><tr><th>Organisation</th><th>Project</th><th>Status</th><th>Last Synced</th><th></th></tr></thead>
            <tbody>
            @forelse($devopsConnections as $conn)
            <tr>
                <td>{{ $conn->config['org_name'] ?? '—' }}</td>
                <td>{{ $conn->config['project_name'] ?? '—' }}</td>
                <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
                <td class="text-sm text-muted">{{ $conn->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                <td>
                    <div class="table-actions">
                        <form method="POST" action="{{ route('settings.integrations.devops.test', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-secondary">Test</button></form>
                        <form method="POST" action="{{ route('settings.integrations.devops.sync', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-primary">Sync</button></form>
                        <form method="POST" action="{{ route('settings.integrations.devops.destroy', $conn) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this connection?')">Remove</button></form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="empty-state"><p>No DevOps connections</p><p class="empty-hint">Add a connection below to sync work items from Azure DevOps Boards</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-bottom:32px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add DevOps Boards Connection
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.integrations.devops.store') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label>Azure DevOps Organisation *</label>
                        <input type="text" name="org_name" class="form-control" value="{{ old('org_name') }}" placeholder="your-org-name" required>
                        <div class="form-hint">From your URL: dev.azure.com/{org_name}</div>
                    </div>
                    <div class="form-group">
                        <label>Project Name *</label>
                        <input type="text" name="project_name" class="form-control" value="{{ old('project_name') }}" placeholder="Your-Project" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Personal Access Token *</label>
                    <input type="password" name="access_token" class="form-control" required>
                    <div class="form-hint">User Settings → Personal Access Tokens. Required scope: Work Items (Read)</div>
                </div>
                <button type="submit" class="btn btn-primary">Add DevOps Connection</button>
            </form>
        </div>
    </div>

    {{-- Section: GitHub Projects Boards --}}
    <div style="display:flex;align-items:center;gap:10px;margin:0 0 14px">
        <div style="width:32px;height:32px;background:#24292e;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
        </div>
        <div>
            <div style="font-weight:600;font-size:15px;color:var(--gray-800)">GitHub Projects Boards</div>
            <div style="font-size:12px;color:var(--gray-500)">Sync issues and project cards from GitHub Projects (GraphQL API)</div>
        </div>
    </div>

    @php $ghProjectsConnections = $integrationConnections->get('github_projects', collect()); @endphp
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                GitHub Projects Connections
            </span>
        </div>
        <table>
            <thead><tr><th>Organisation</th><th>Project #</th><th>Status</th><th>Last Synced</th><th></th></tr></thead>
            <tbody>
            @forelse($ghProjectsConnections as $conn)
            <tr>
                <td>{{ $conn->config['org_name'] ?? '—' }}</td>
                <td>#{{ $conn->config['project_number'] ?? '—' }}</td>
                <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
                <td class="text-sm text-muted">{{ $conn->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                <td>
                    <div class="table-actions">
                        <form method="POST" action="{{ route('settings.integrations.githubProjects.test', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-secondary">Test</button></form>
                        <form method="POST" action="{{ route('settings.integrations.githubProjects.sync', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-primary">Sync</button></form>
                        <form method="POST" action="{{ route('settings.integrations.githubProjects.destroy', $conn) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this connection?')">Remove</button></form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="empty-state"><p>No GitHub Projects connections</p><p class="empty-hint">Add a connection below to sync project board items from GitHub</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-bottom:8px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add GitHub Projects Connection
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.integrations.githubProjects.store') }}">
                @csrf
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label>GitHub Organisation *</label>
                        <input type="text" name="org_name" class="form-control" value="{{ old('org_name') }}" placeholder="your-org" required>
                    </div>
                    <div class="form-group">
                        <label>Project Number *</label>
                        <input type="number" name="project_number" class="form-control" value="{{ old('project_number') }}" placeholder="1" min="1" required>
                        <div class="form-hint">From the URL: /orgs/{org}/projects/{number}</div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Personal Access Token (Classic) *</label>
                    <input type="password" name="access_token" class="form-control" required>
                    <div class="form-hint">Required scopes: read:org, read:project, repo (for private repos)</div>
                </div>
                <button type="submit" class="btn btn-primary">Add GitHub Projects Connection</button>
            </form>
        </div>
    </div>

</div>

{{-- ===== HR SYSTEMS TAB ===== --}}
<div class="tab-content" id="tab-hr-systems">

    {{-- Section: Zoho People --}}
    <div style="display:flex;align-items:center;gap:10px;margin:24px 0 14px">
        <div style="width:32px;height:32px;background:#e65c19;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <div style="font-weight:600;font-size:15px;color:var(--gray-800)">Zoho People</div>
            <div style="font-size:12px;color:var(--gray-500)">Sync employees, departments, and HR data from Zoho People</div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
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

    <div class="card" style="margin-bottom:32px">
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

    {{-- Section: OrangeHRM --}}
    <div style="display:flex;align-items:center;gap:10px;margin:0 0 14px">
        <div style="width:32px;height:32px;background:#f97316;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <div style="font-weight:600;font-size:15px;color:var(--gray-800)">OrangeHRM</div>
            <div style="font-size:12px;color:var(--gray-500)">Open-source HR platform — sync employee profiles, departments, and designations</div>
        </div>
    </div>

    @php $orangehrmConnections = $integrationConnections->get('orangehrm', collect()); @endphp
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                OrangeHRM Connections
            </span>
        </div>
        <table>
            <thead><tr><th>Base URL</th><th>Status</th><th>Last Synced</th><th></th></tr></thead>
            <tbody>
            @forelse($orangehrmConnections as $conn)
            <tr>
                <td>{{ $conn->config['base_url'] ?? '—' }}</td>
                <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
                <td class="text-sm text-muted">{{ $conn->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                <td>
                    <div class="table-actions">
                        <form method="POST" action="{{ route('settings.integrations.orangehrm.test', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-secondary">Test</button></form>
                        <form method="POST" action="{{ route('settings.integrations.orangehrm.sync', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-primary">Sync</button></form>
                        <form method="POST" action="{{ route('settings.integrations.orangehrm.destroy', $conn) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this connection?')">Remove</button></form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4"><div class="empty-state"><p>No OrangeHRM connections</p><p class="empty-hint">Add a connection below to sync employees from OrangeHRM</p></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add OrangeHRM Connection
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.integrations.orangehrm.store') }}">
                @csrf
                <div class="form-group">
                    <label>OrangeHRM Base URL *</label>
                    <input type="url" name="base_url" class="form-control" value="{{ old('base_url') }}" placeholder="https://yourcompany.orangehrm.com" required>
                    <div class="form-hint">Your OrangeHRM instance URL (cloud or self-hosted)</div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div class="form-group">
                        <label>OAuth Client ID *</label>
                        <input type="text" name="client_id" class="form-control" value="{{ old('client_id') }}" required>
                    </div>
                    <div class="form-group">
                        <label>OAuth Client Secret *</label>
                        <input type="password" name="client_secret" class="form-control" required>
                    </div>
                </div>
                <div class="form-hint" style="margin-bottom:16px">Create an OAuth application in OrangeHRM → Admin → OAuth Clients. Grant type: Client Credentials.</div>
                <button type="submit" class="btn btn-primary">Add OrangeHRM Connection</button>
            </form>
        </div>
    </div>

</div>

{{-- ===== WORKFORCE COMMUNICATION TAB ===== --}}
<div class="tab-content" id="tab-communication">

    {{-- Privacy Notice --}}
    <div class="alert" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;margin-top:20px;display:flex;gap:12px;align-items:flex-start">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        <div>
            <div style="font-weight:600;font-size:13.5px;margin-bottom:3px">Privacy-First Communication Analytics</div>
            <div style="font-size:12.5px;line-height:1.6">Message content is <strong>never stored or read</strong>. Only behavioural metrics are captured — response time patterns, collaboration frequency, working hour signals, and cross-team communication reach. This helps with resource allocation decisions and performance insights without any privacy compromise.</div>
        </div>
    </div>

    {{-- Section: Slack --}}
    <div style="display:flex;align-items:center;gap:10px;margin:24px 0 14px">
        <div style="width:32px;height:32px;background:#4a154b;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div>
            <div style="font-weight:600;font-size:15px;color:var(--gray-800)">Slack</div>
            <div style="font-size:12px;color:var(--gray-500)">OAuth read-only integration — captures communication behaviour metrics (no message content stored)</div>
        </div>
    </div>

    @php $slackConnections = $integrationConnections->get('slack', collect()); @endphp
    @if($slackConnections->isNotEmpty())
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Connected Slack Workspaces
            </span>
        </div>
        <table>
            <thead><tr><th>Workspace</th><th>Status</th><th>Last Synced</th><th></th></tr></thead>
            <tbody>
            @foreach($slackConnections as $conn)
            <tr>
                <td>{{ $conn->name }}</td>
                <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
                <td class="text-sm text-muted">{{ $conn->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                <td>
                    <div class="table-actions">
                        <form method="POST" action="{{ route('settings.integrations.slack.sync', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-primary">Sync</button></form>
                        <form method="POST" action="{{ route('settings.integrations.slack.destroy', $conn) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Disconnect this Slack workspace?')">Disconnect</button></form>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="card" style="margin-bottom:32px">
        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap">
            <div>
                <div style="font-weight:600;font-size:14px;color:var(--gray-800);margin-bottom:4px">Connect a Slack Workspace</div>
                <div style="font-size:12.5px;color:var(--gray-500);line-height:1.6">You'll be redirected to Slack to authorise read-only access. Message content is never stored — only aggregate behaviour metrics.</div>
                @if(!env('SLACK_CLIENT_ID'))
                <div style="margin-top:8px;font-size:12px;color:var(--gray-400)">⚠ Requires SLACK_CLIENT_ID and SLACK_CLIENT_SECRET in .env to be configured.</div>
                @endif
            </div>
            <a href="{{ route('settings.integrations.oauth.slack') }}" class="btn btn-primary" style="flex-shrink:0;white-space:nowrap" @if(!env('SLACK_CLIENT_ID')) disabled title="Slack OAuth not configured" @endif>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Connect with Slack
            </a>
        </div>
    </div>

    {{-- Section: Microsoft Teams --}}
    <div style="display:flex;align-items:center;gap:10px;margin:0 0 14px">
        <div style="width:32px;height:32px;background:#464eb8;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="12" y1="7" x2="12" y2="13"/></svg>
        </div>
        <div>
            <div style="font-weight:600;font-size:15px;color:var(--gray-800)">Microsoft Teams</div>
            <div style="font-size:12px;color:var(--gray-500)">Microsoft Graph API integration for Teams communication behaviour analysis</div>
        </div>
    </div>

    @php $teamsConnections = $integrationConnections->get('teams', collect()); @endphp
    @if($teamsConnections->isNotEmpty())
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Connected Teams Tenants
            </span>
        </div>
        <table>
            <thead><tr><th>Tenant</th><th>Status</th><th>Last Synced</th><th></th></tr></thead>
            <tbody>
            @foreach($teamsConnections as $conn)
            <tr>
                <td>{{ $conn->name }}</td>
                <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
                <td class="text-sm text-muted">{{ $conn->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                <td>
                    <div class="table-actions">
                        <form method="POST" action="{{ route('settings.integrations.teams.sync', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-primary">Sync</button></form>
                        <form method="POST" action="{{ route('settings.integrations.teams.destroy', $conn) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Disconnect this Teams tenant?')">Disconnect</button></form>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="card">
        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap">
            <div>
                <div style="font-weight:600;font-size:14px;color:var(--gray-800);margin-bottom:4px">Connect a Microsoft Teams Tenant</div>
                <div style="font-size:12.5px;color:var(--gray-500);line-height:1.6">Uses Microsoft Graph Reports API (pre-aggregated) — no individual message content is accessed. Requires Azure AD App registration with <code style="font-size:11px;background:var(--gray-100);padding:1px 4px;border-radius:3px">Reports.Read.All</code> scope.</div>
                @if(!env('TEAMS_CLIENT_ID'))
                <div style="margin-top:8px;font-size:12px;color:var(--gray-400)">⚠ Requires TEAMS_CLIENT_ID, TEAMS_CLIENT_SECRET, and TEAMS_TENANT_ID in .env to be configured.</div>
                @endif
            </div>
            <a href="{{ route('settings.integrations.oauth.teams') }}" class="btn btn-primary" style="flex-shrink:0;white-space:nowrap" @if(!env('TEAMS_CLIENT_ID')) disabled title="Teams OAuth not configured" @endif>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Connect with Teams
            </a>
        </div>
    </div>

</div>

{{-- ===== SOURCE CODE SIGNALS TAB ===== --}}
<div class="tab-content" id="tab-code-signals">

    {{-- Philosophy notice --}}
    <div class="alert" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;margin-top:20px;display:flex;gap:12px;align-items:flex-start">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        <div>
            <div style="font-weight:600;font-size:13.5px;margin-bottom:3px">Minimal Footprint, Maximum Signal</div>
            <div style="font-size:12.5px;line-height:1.6">Source code integrations are designed with a minimal-data philosophy. Raw code and commit diffs are <strong>never stored</strong>. Only lightweight signals are extracted — contribution frequency, active technology areas, and consistency patterns. This gives meaningful appraisal and allocation signals without storing proprietary code or creating compliance risk.</div>
        </div>
    </div>

    {{-- Section: GitHub --}}
    <div style="display:flex;align-items:center;gap:10px;margin:24px 0 14px">
        <div style="width:32px;height:32px;background:#24292e;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
        </div>
        <div>
            <div style="font-weight:600;font-size:15px;color:var(--gray-800)">GitHub</div>
            <div style="font-size:12px;color:var(--gray-500)">Extract lightweight developer signals from commit history and repository activity</div>
        </div>
    </div>

    @php $githubConnections = $integrationConnections->get('github', collect()); @endphp
    @if($githubConnections->isNotEmpty())
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                GitHub Signal Connections
            </span>
        </div>
        <table>
            <thead><tr><th>Organisation</th><th>Status</th><th>Last Synced</th><th></th></tr></thead>
            <tbody>
            @foreach($githubConnections as $conn)
            <tr>
                <td>{{ $conn->config['org_name'] ?? '—' }}</td>
                <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
                <td class="text-sm text-muted">{{ $conn->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                <td>
                    <div class="table-actions">
                        <form method="POST" action="{{ route('settings.integrations.github.test', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-secondary">Test</button></form>
                        <form method="POST" action="{{ route('settings.integrations.github.sync', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-primary">Sync</button></form>
                        <form method="POST" action="{{ route('settings.integrations.github.destroy', $conn) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this connection?')">Remove</button></form>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="card" style="margin-bottom:32px">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add GitHub Connection
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.integrations.github.store') }}">
                @csrf
                <div class="form-group">
                    <label>GitHub Organisation Name *</label>
                    <input type="text" name="org_name" class="form-control" value="{{ old('org_name') }}" placeholder="your-org" required>
                </div>
                <div class="form-group">
                    <label>Personal Access Token *</label>
                    <input type="password" name="access_token" class="form-control" required>
                    <div class="form-hint">Classic PAT with scopes: repo (read), read:org. Only commit metadata is read — no code content.</div>
                </div>
                <button type="submit" class="btn btn-primary">Add GitHub Connection</button>
            </form>
        </div>
    </div>

    {{-- Signals info card (kept for context) --}}
    <div class="card" style="margin-bottom:32px;border:1px solid var(--gray-200);background:var(--gray-50)">
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px">
                <div>
                    <div style="font-weight:600;font-size:13px;color:var(--gray-700);margin-bottom:10px">Signals extracted per developer</div>
                    <ul style="margin:0;padding-left:18px;font-size:12.5px;color:var(--gray-600);line-height:2.1">
                        <li><strong>Commit frequency</strong> — commits per week / month trend</li>
                        <li><strong>Active days</strong> — how many working days had code activity</li>
                        <li><strong>Code area focus</strong> — repository folders / modules touched most</li>
                        <li><strong>Technology fingerprint</strong> — file types edited (languages, frameworks)</li>
                        <li><strong>Contribution size pattern</strong> — avg lines added/removed per commit (small vs large changesets)</li>
                        <li><strong>Review participation</strong> — PRs reviewed, comments left</li>
                        <li><strong>Collaboration reach</strong> — distinct teammates whose PRs were reviewed</li>
                    </ul>
                </div>
                <div>
                    <div style="font-weight:600;font-size:13px;color:var(--gray-700);margin-bottom:10px">How signals are used</div>
                    <ul style="margin:0;padding-left:18px;font-size:12.5px;color:var(--gray-600);line-height:2.1">
                        <li>Enrich resource allocation scoring with code-area expertise evidence</li>
                        <li>Surface consistency and output trends for appraisal context</li>
                        <li>Identify technology specialisations beyond what's in the employee profile</li>
                        <li>Show historical activity trajectory — improving, steady, or declining output</li>
                        <li>All signals shown as factual data — <em>no AI judgements</em></li>
                    </ul>
                    <div style="margin-top:16px;padding:12px;background:white;border:1px solid var(--gray-200);border-radius:8px">
                        <div style="font-weight:600;font-size:12px;color:var(--gray-700);margin-bottom:6px">What is NOT stored</div>
                        <ul style="margin:0;padding-left:16px;font-size:12px;color:var(--gray-500);line-height:1.9">
                            <li>Commit messages or diff content</li>
                            <li>Source code files or snippets</li>
                            <li>Branch names or repository URLs</li>
                            <li>Any personally-owned repository data</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div style="margin-top:24px;border-top:1px solid var(--gray-200);padding-top:20px">
                <div style="font-weight:600;font-size:13px;color:var(--gray-700);margin-bottom:10px">Integration approach</div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                    @foreach([
                        ['GitHub App (read-only)','Installed at organisation level — scopes: contents:read, metadata:read, pull_requests:read. No write access.'],
                        ['Developer mapping','GitHub commit email or username linked to system employee records. One-time mapping, employee-confirmed.'],
                        ['Periodic extraction','Runs nightly — extracts only aggregated counts and file-type stats. Raw data discarded after extraction.']
                    ] as [$title, $desc])
                    <div style="border:1px solid var(--gray-200);border-radius:8px;padding:14px;background:white">
                        <div style="font-weight:600;font-size:12.5px;color:var(--gray-700);margin-bottom:5px">{{ $title }}</div>
                        <div style="font-size:12px;color:var(--gray-500);line-height:1.6">{{ $desc }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Future: GitLab --}}
    <div style="display:flex;align-items:center;gap:10px;margin:0 0 14px">
        <div style="width:32px;height:32px;background:var(--gray-300);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 0 1-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 0 1 4.82 2a.43.43 0 0 1 .58 0 .42.42 0 0 1 .11.18l2.44 7.49h8.1l2.44-7.51A.42.42 0 0 1 18.6 2a.43.43 0 0 1 .58 0 .42.42 0 0 1 .11.18l2.44 7.51L23 13.45a.84.84 0 0 1-.35.94z"/></svg>
        </div>
        <div>
            <div style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:15px;color:var(--gray-500)">GitLab <span style="font-size:10px;font-weight:600;background:var(--gray-200);color:var(--gray-500);padding:2px 7px;border-radius:10px;text-transform:uppercase;letter-spacing:.04em">Planned</span></div>
            <div style="font-size:12px;color:var(--gray-400)">Same signal extraction as GitHub — commit frequency, code area focus, MR review activity</div>
        </div>
    </div>
    <div class="card" style="border:1.5px dashed var(--gray-200);background:var(--gray-50)">
        <div class="card-body" style="padding:14px 20px">
            <p style="margin:0;font-size:12.5px;color:var(--gray-500);line-height:1.7">GitLab integration will follow the same minimal-footprint approach as GitHub — using the GitLab REST API with read-only scopes to extract aggregated contribution signals per developer. Compatible with both GitLab.com and self-hosted GitLab instances.</p>
        </div>
    </div>

</div>

@endsection
