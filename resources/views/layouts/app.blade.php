<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Talent Intelligence')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">Talent<span>Intel</span></div>
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>

            @if(in_array(auth()->user()->role, ['hr_manager','hiring_manager','org_admin','super_admin']))
            <div class="sidebar-heading">Hiring</div>
            <a href="{{ route('jobs.index') }}" class="sidebar-link {{ request()->routeIs('jobs.*') ? 'active' : '' }}">Jobs</a>
            <a href="{{ route('candidates.index') }}" class="sidebar-link {{ request()->routeIs('candidates.*') ? 'active' : '' }}">Candidates</a>
            <a href="{{ route('hiring.reports') }}" class="sidebar-link {{ request()->routeIs('hiring.reports') ? 'active' : '' }}">Reports</a>
            @endif

            @if(in_array(auth()->user()->role, ['resource_manager','org_admin','super_admin']))
            <div class="sidebar-heading">Resources</div>
            <a href="{{ route('employees.index') }}" class="sidebar-link {{ request()->routeIs('employees.*') ? 'active' : '' }}">Employees</a>
            <a href="{{ route('jira-connections.index') }}" class="sidebar-link {{ request()->routeIs('jira-connections.*') ? 'active' : '' }}">Jira</a>
            <a href="{{ route('projects.index') }}" class="sidebar-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">Projects</a>
            @endif

            @if(in_array(auth()->user()->role, ['org_admin','super_admin']))
            <div class="sidebar-heading">Settings</div>
            <a href="{{ route('settings.organization.edit') }}" class="sidebar-link {{ request()->routeIs('settings.organization.*') ? 'active' : '' }}">Organization</a>
            <a href="{{ route('settings.users.index') }}" class="sidebar-link {{ request()->routeIs('settings.users.*') ? 'active' : '' }}">Users</a>
            @endif
        </nav>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div>@yield('page-title')</div>
            <div class="topbar-right">
                <span class="badge badge-blue">{{ ucwords(str_replace('_', ' ', auth()->user()->role)) }}</span>
                <span>{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-secondary">Logout</button>
                </form>
            </div>
        </div>

        <div class="content">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }} <button class="alert-close">&times;</button></div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }} <button class="alert-close">&times;</button></div>
            @endif
            @if($errors->any())
                <div class="alert alert-error">
                    <ul style="margin:0;padding-left:18px">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                    <button class="alert-close">&times;</button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    @yield('scripts')
</body>
</html>
