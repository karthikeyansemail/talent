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
            <div class="form-group">
                <label>Currency</label>
                <select name="currency" class="form-control" style="max-width:380px">
                    @foreach(config('currencies', []) as $code => $cfg)
                        <option value="{{ $code }}" {{ ($organization->currency ?? 'USD') === $code ? 'selected' : '' }}>
                            {{ $cfg['symbol'] }}  {{ $code }} — {{ $cfg['name'] }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted" style="display:block;margin-top:6px">
                    Used for monetary displays in Sales Pulse and other reports. Display only — no conversion is performed.
                </small>
            </div>
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Changes
            </button>
        </form>
    </div>
</div>

{{-- Color Theme picker — org admin can choose org's brand palette --}}
@php
    $_palettes      = \App\Services\ThemeService::palettes();
    $_currentTheme  = ($organization->settings['theme'] ?? 'indigo_night');
@endphp
<div class="card" style="margin-top:20px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 0 0 20 4 4 0 0 1 0-8 4 4 0 0 0 0-8"/><circle cx="12" cy="12" r="2"/></svg>
            Color Theme
        </span>
    </div>
    <div class="card-body">
        <p style="margin:0 0 16px;font-size:13px;color:var(--text-muted)">
            Pick a color palette for your workspace. Affects sidebar, buttons, and accent colors. Each user can independently toggle dark/light mode using the button at the bottom of the sidebar.
        </p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px">
            @foreach($_palettes as $key => $palette)
            <form method="POST" action="{{ route('settings.organization.theme') }}" style="margin:0">
                @csrf @method('PUT')
                <input type="hidden" name="theme" value="{{ $key }}">
                <button type="submit"
                    style="width:100%;padding:12px;border:2px solid {{ $_currentTheme === $key ? $palette['colors']['--primary'] : 'var(--border)' }};background:var(--bg-card);border-radius:10px;cursor:pointer;text-align:left;transition:transform 0.15s, border-color 0.15s"
                    onmouseover="this.style.transform='translateY(-2px)'"
                    onmouseout="this.style.transform='translateY(0)'"
                    title="{{ $palette['name'] }}">
                    {{-- Mini preview: sidebar swatch + content area --}}
                    <div style="display:flex;height:60px;border-radius:6px;overflow:hidden;border:1px solid var(--border);margin-bottom:10px">
                        <div style="width:30%;background:{{ $palette['colors']['--sidebar-bg'] }};display:flex;flex-direction:column;justify-content:center;padding:0 6px;gap:4px">
                            <div style="height:4px;background:{{ $palette['colors']['--sidebar-active-text'] }};border-radius:2px;width:80%"></div>
                            <div style="height:3px;background:{{ $palette['colors']['--sidebar-text'] }};border-radius:2px;width:60%;opacity:0.5"></div>
                            <div style="height:3px;background:{{ $palette['colors']['--sidebar-text'] }};border-radius:2px;width:70%;opacity:0.5"></div>
                        </div>
                        <div style="flex:1;background:var(--bg-card);position:relative">
                            <div style="position:absolute;top:8px;left:8px;right:8px;height:6px;background:{{ $palette['colors']['--primary'] }};border-radius:3px;width:40%"></div>
                            <div style="position:absolute;top:22px;left:8px;right:8px;height:3px;background:var(--gray-200);border-radius:2px"></div>
                            <div style="position:absolute;top:30px;left:8px;right:8px;height:3px;background:var(--gray-200);border-radius:2px;width:60%"></div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <span style="font-size:12.5px;font-weight:600;color:var(--text)">{{ $palette['name'] }}</span>
                        @if($_currentTheme === $key)
                        <span style="display:inline-flex;width:18px;height:18px;border-radius:50%;background:{{ $palette['colors']['--primary'] }};color:#fff;align-items:center;justify-content:center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        @endif
                    </div>
                </button>
            </form>
            @endforeach
        </div>
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
        @if(auth()->user()->isSuperAdmin())
        <div style="margin-top:14px">
            <a href="{{ route('settings.organizations.modules', $organization) }}" class="btn btn-sm btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                Manage Modules
            </a>
        </div>
        @else
        <p style="margin:12px 0 0;font-size:12px;color:var(--gray-500)">
            To change which modules are active, contact your platform administrator.
        </p>
        @endif
    </div>
</div>
@endsection
