<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $branding['app_name'] ?? 'Nalam Compass')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    {!! $themeCss ?? '' !!}
</head>
<body>
    <div class="small-screen-blocker">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        <h2>Larger Screen Required</h2>
        <p>{{ $branding['app_name'] ?? 'Nalam Compass' }} requires a minimum screen width of 1300px. Please use a laptop or desktop with a larger display.</p>
    </div>
    <div class="sidebar">
        {{-- Brand --}}
        <div class="sidebar-brand">
            @if(!empty($branding['logo_path']))
            <div class="brand-icon">
                <img src="{{ asset('storage/' . $branding['logo_path']) }}" alt="{{ $branding['app_name'] }}" style="width:100%;height:100%;object-fit:contain;border-radius:8px">
            </div>
            @else
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
            </div>
            @endif
            <div class="brand-text">{{ $branding['app_name_short'] ?? 'Nalam' }}@if(!empty($branding['app_name_accent']))<span>{{ $branding['app_name_accent'] }}</span>@endif</div>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
                Dashboard
            </a>

            @if(in_array(auth()->user()->role, ['hr_manager','hiring_manager','management','org_admin','super_admin']))
            <div class="sidebar-heading">Hiring</div>
            <a href="{{ route('jobs.index') }}" class="sidebar-link {{ request()->routeIs('jobs.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg></span>
                Job Postings
            </a>
            <a href="{{ route('candidates.index') }}" class="sidebar-link {{ request()->routeIs('candidates.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                Candidates
            </a>
            <a href="{{ route('hiring.reports') }}" class="sidebar-link {{ request()->routeIs('hiring.reports') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
                Reports
            </a>
            @endif

            @if(in_array(auth()->user()->role, ['resource_manager','management','org_admin','super_admin']))
            <div class="sidebar-heading">Resources</div>
            <a href="{{ route('employees.index') }}" class="sidebar-link {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg></span>
                Employees
            </a>
            <a href="{{ route('projects.index') }}" class="sidebar-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></span>
                Projects
            </a>
            @endif

            @if(in_array(auth()->user()->role, ['resource_manager','management','org_admin','super_admin']) && auth()->user()->currentOrganization()?->is_premium)
            <div class="sidebar-heading">Intelligence</div>
            <a href="{{ route('intelligence.dashboard') }}" class="sidebar-link {{ request()->routeIs('intelligence.dashboard') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></span>
                Signal Dashboard
            </a>
            <a href="{{ route('intelligence.config') }}" class="sidebar-link {{ request()->routeIs('intelligence.config*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                Signal Config
            </a>
            @endif

            @if(in_array(auth()->user()->role, ['hr_manager','org_admin','super_admin']))
            <div class="sidebar-heading">Settings</div>
            @if(in_array(auth()->user()->role, ['org_admin','super_admin']))
            <a href="{{ route('settings.organization.edit') }}" class="sidebar-link {{ request()->routeIs('settings.organization.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
                Organization
            </a>
            <a href="{{ route('settings.departments.index') }}" class="sidebar-link {{ request()->routeIs('settings.departments.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></span>
                Departments
            </a>
            <a href="{{ route('settings.users.index') }}" class="sidebar-link {{ request()->routeIs('settings.users.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
                User Management
            </a>
            <a href="{{ route('settings.integrations.index') }}" class="sidebar-link {{ request()->routeIs('settings.integrations.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></span>
                Integrations
            </a>
            @endif
            <a href="{{ route('settings.scoring.index') }}" class="sidebar-link {{ request()->routeIs('settings.scoring.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
                Hiring Scoring Rules
            </a>
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('settings.sso.index') }}" class="sidebar-link {{ request()->routeIs('settings.sso.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                SSO
            </a>
            <a href="{{ route('settings.llm.edit') }}" class="sidebar-link {{ request()->routeIs('settings.llm.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 0-4 4c0 2 2 3 2 6H6a2 2 0 0 0-2 2v2h16v-2a2 2 0 0 0-2-2h-4c0-3 2-4 2-6a4 4 0 0 0-4-4z"/><path d="M9 18v1a3 3 0 0 0 6 0v-1"/></svg></span>
                LLM Configuration
            </a>
            <a href="{{ route('settings.organizations.index') }}" class="sidebar-link {{ request()->routeIs('settings.organizations.*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
                Organizations
            </a>
            <a href="{{ route('settings.platformBranding') }}" class="sidebar-link {{ request()->routeIs('settings.platformBranding*') ? 'active' : '' }}">
                <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg></span>
                Platform Branding
            </a>
            @endif
            @endif
        </nav>

        {{-- User Profile at Bottom --}}
        <div class="sidebar-user">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="margin:0">
                @csrf
                <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--sidebar-text);padding:4px;opacity:0.5;transition:opacity 0.15s" title="Sign out" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.5'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </form>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div class="topbar-left">
                <span class="page-title-bar">@yield('page-title')</span>
            </div>
            <div class="topbar-right">
                @if(auth()->user()->isSuperAdmin())
                <div class="org-switcher">
                    <form action="{{ route('org.switch') }}" method="POST" style="display:inline-flex;align-items:center;gap:8px;margin:0">
                        @csrf
                        <select name="organization_id" onchange="this.form.submit()" class="org-switcher-select">
                            @foreach(\App\Models\Organization::orderBy('name')->get() as $switchOrg)
                            <option value="{{ $switchOrg->id }}" {{ auth()->user()->currentOrganizationId() == $switchOrg->id ? 'selected' : '' }}>{{ $switchOrg->name }}</option>
                            @endforeach
                        </select>
                    </form>
                    @if(session('viewing_organization_id'))
                    <form action="{{ route('org.reset') }}" method="POST" style="display:inline;margin:0">
                        @csrf
                        <button type="submit" class="btn btn-sm" title="Return to your org" style="padding:4px 10px;font-size:12px;background:var(--gray-100);border:1px solid var(--gray-300);border-radius:6px;cursor:pointer;color:var(--gray-600)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 1 1 0 8h-1"/></svg>
                            Reset
                        </button>
                    </form>
                    @endif
                </div>
                @else
                <span class="badge badge-blue">{{ auth()->user()->currentOrganization()?->name ?? 'System' }}</span>
                @endif
            </div>
        </div>

        <div class="content">
            @if(session('success'))
                <div class="alert alert-success">
                    <span>{{ session('success') }}</span>
                    <button class="alert-close">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">
                    <span>{{ session('error') }}</span>
                    <button class="alert-close">&times;</button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    <div>
                        <ul style="margin:0;padding-left:18px">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                    <button class="alert-close">&times;</button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @yield('scripts')
</body>
</html>
