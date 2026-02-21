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
        <span style="font-size:11.5px;font-weight:400;color:var(--gray-500);padding-left:24px">Jira · Zoho Projects · Spreadsheets</span>
    </button>
    <button class="tab" data-tab="tab-hr-systems" style="display:flex;flex-direction:column;align-items:flex-start;gap:2px;padding:12px 20px;height:auto;min-width:200px">
        <span style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            HR Systems
        </span>
        <span style="font-size:11.5px;font-weight:400;color:var(--gray-500);padding-left:24px">Zoho People · OrangeHRM · more</span>
    </button>
    <button class="tab" data-tab="tab-communication" style="display:flex;flex-direction:column;align-items:flex-start;gap:2px;padding:12px 20px;height:auto;min-width:200px">
        <span style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:14px">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Workforce Comms
        </span>
        <span style="font-size:11.5px;font-weight:400;color:var(--gray-500);padding-left:24px">Slack · Microsoft Teams</span>
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

    {{-- Coming Soon: DevOps --}}
    <div style="display:flex;align-items:center;gap:10px;margin:0 0 14px">
        <div style="width:32px;height:32px;background:var(--gray-300);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </div>
        <div>
            <div style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:15px;color:var(--gray-500)">DevOps Tools <span style="font-size:10px;font-weight:600;background:var(--gray-200);color:var(--gray-500);padding:2px 7px;border-radius:10px;text-transform:uppercase;letter-spacing:.04em">Coming Soon</span></div>
            <div style="font-size:12px;color:var(--gray-400)">GitHub, GitLab, Jenkins, GitHub Actions — commit frequency, deployment data, pipeline metrics</div>
        </div>
    </div>
    <div class="card" style="margin-bottom:8px;border:1.5px dashed var(--gray-200);background:var(--gray-50)">
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                @foreach([['GitHub','Commit history, PR activity, code review participation'],['GitLab','Pipeline runs, MR stats, commit data per developer'],['Jenkins / GitHub Actions','Build frequency, deployment success rate per team']] as [$tool,$desc])
                <div style="border:1px solid var(--gray-200);border-radius:8px;padding:14px;background:white">
                    <div style="font-weight:600;font-size:13px;color:var(--gray-600);margin-bottom:4px">{{ $tool }}</div>
                    <div style="font-size:12px;color:var(--gray-400);line-height:1.5">{{ $desc }}</div>
                </div>
                @endforeach
            </div>
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

    {{-- Coming Soon: OrangeHRM + others --}}
    <div style="display:flex;align-items:center;gap:10px;margin:0 0 14px">
        <div style="width:32px;height:32px;background:var(--gray-300);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
            <div style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:15px;color:var(--gray-500)">More HR Systems <span style="font-size:10px;font-weight:600;background:var(--gray-200);color:var(--gray-500);padding:2px 7px;border-radius:10px;text-transform:uppercase;letter-spacing:.04em">Coming Soon</span></div>
            <div style="font-size:12px;color:var(--gray-400)">Connect additional HR platforms to sync employees and departments automatically</div>
        </div>
    </div>
    <div class="card" style="border:1.5px dashed var(--gray-200);background:var(--gray-50)">
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                @foreach([['OrangeHRM','Open-source HR platform — employee profiles, leave management, departments'],['BambooHR','Employee records, org chart, performance data sync'],['Workday','Enterprise HCM — employee data, org structure, payroll integration']] as [$tool,$desc])
                <div style="border:1px solid var(--gray-200);border-radius:8px;padding:14px;background:white">
                    <div style="font-weight:600;font-size:13px;color:var(--gray-600);margin-bottom:4px">{{ $tool }}</div>
                    <div style="font-size:12px;color:var(--gray-400);line-height:1.5">{{ $desc }}</div>
                </div>
                @endforeach
            </div>
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

    {{-- Coming Soon: Slack --}}
    <div style="display:flex;align-items:center;gap:10px;margin:24px 0 14px">
        <div style="width:32px;height:32px;background:#4a154b;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div>
            <div style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:15px;color:var(--gray-800)">Slack <span style="font-size:10px;font-weight:600;background:#ede9fe;color:#6d28d9;padding:2px 7px;border-radius:10px;text-transform:uppercase;letter-spacing:.04em">Planned</span></div>
            <div style="font-size:12px;color:var(--gray-500)">OAuth-based read-only integration to capture communication behaviour metrics</div>
        </div>
    </div>
    <div class="card" style="margin-bottom:32px;border:1.5px dashed var(--gray-200);background:var(--gray-50)">
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                <div>
                    <div style="font-weight:600;font-size:13px;color:var(--gray-700);margin-bottom:8px">What will be captured</div>
                    <ul style="margin:0;padding-left:18px;font-size:12.5px;color:var(--gray-600);line-height:2">
                        <li>Message volume per employee per day / week</li>
                        <li>Average response time to direct messages</li>
                        <li>Number of unique collaborators per week</li>
                        <li>Active hours distribution (morning / afternoon / evening)</li>
                        <li>Cross-team communication reach</li>
                        <li>Channel participation frequency</li>
                    </ul>
                </div>
                <div>
                    <div style="font-weight:600;font-size:13px;color:var(--gray-700);margin-bottom:8px">Integration approach</div>
                    <ul style="margin:0;padding-left:18px;font-size:12.5px;color:var(--gray-600);line-height:2">
                        <li>Slack OAuth App — read-only workspace access</li>
                        <li>Periodic API pull (not real-time webhook)</li>
                        <li>Metrics aggregated before storage — raw data discarded</li>
                        <li>Employee email used for identity mapping</li>
                        <li>Per-org data isolation maintained</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Coming Soon: Microsoft Teams --}}
    <div style="display:flex;align-items:center;gap:10px;margin:0 0 14px">
        <div style="width:32px;height:32px;background:#464eb8;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="9" y1="10" x2="15" y2="10"/><line x1="12" y1="7" x2="12" y2="13"/></svg>
        </div>
        <div>
            <div style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:15px;color:var(--gray-800)">Microsoft Teams <span style="font-size:10px;font-weight:600;background:#ede9fe;color:#6d28d9;padding:2px 7px;border-radius:10px;text-transform:uppercase;letter-spacing:.04em">Planned</span></div>
            <div style="font-size:12px;color:var(--gray-500)">Microsoft Graph API integration for Teams communication behaviour analysis</div>
        </div>
    </div>
    <div class="card" style="border:1.5px dashed var(--gray-200);background:var(--gray-50)">
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                <div>
                    <div style="font-weight:600;font-size:13px;color:var(--gray-700);margin-bottom:8px">What will be captured</div>
                    <ul style="margin:0;padding-left:18px;font-size:12.5px;color:var(--gray-600);line-height:2">
                        <li>Chat activity volume and frequency per employee</li>
                        <li>Meeting participation and duration metrics</li>
                        <li>Response latency in team chats</li>
                        <li>Collaboration reach across channels and teams</li>
                        <li>After-hours activity signals</li>
                    </ul>
                </div>
                <div>
                    <div style="font-weight:600;font-size:13px;color:var(--gray-700);margin-bottom:8px">Integration approach</div>
                    <ul style="margin:0;padding-left:18px;font-size:12.5px;color:var(--gray-600);line-height:2">
                        <li>Azure AD App Registration (read-only Graph scopes)</li>
                        <li>Uses Microsoft Graph Reports API (pre-aggregated data)</li>
                        <li>No individual message content accessed</li>
                        <li>Employee UPN mapped to system employee records</li>
                        <li>Compliant with Microsoft Graph usage policies</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
