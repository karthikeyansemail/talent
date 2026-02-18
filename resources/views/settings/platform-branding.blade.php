@extends('layouts.app')
@section('title', 'Platform Branding')
@section('page-title', 'Platform Branding')
@section('content')
<div class="page-header">
    <h1>Platform Branding</h1>
</div>

{{-- Global Branding --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
            Global Platform Name &amp; Logo
        </span>
    </div>
    <div class="card-body">
        <p class="text-muted" style="margin-bottom:16px">These settings apply to all organizations unless they have a white-label override configured below.</p>
        <form method="POST" action="{{ route('settings.platformBranding.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label>App Name (First Part) *</label>
                    <input type="text" name="app_name_short" class="form-control" value="{{ old('app_name_short', $settings['app_name_short']) }}" required placeholder="Nalam">
                    <small class="text-muted">Displayed as the primary brand text in the sidebar</small>
                </div>
                <div class="form-group">
                    <label>App Name (Accent Part)</label>
                    <input type="text" name="app_name_accent" class="form-control" value="{{ old('app_name_accent', $settings['app_name_accent']) }}" placeholder="Compass">
                    <small class="text-muted">Displayed in accent color next to the primary name</small>
                </div>
            </div>
            <div style="margin-bottom:16px;padding:12px 16px;background:var(--gray-50);border-radius:8px;display:flex;align-items:center;gap:12px">
                <span class="text-muted" style="font-size:13px">Preview:</span>
                <span style="font-size:18px;font-weight:700;color:var(--sidebar-bg, #1e1b4b)">{{ $settings['app_name_short'] }}<span style="color:var(--primary)">{{ $settings['app_name_accent'] }}</span></span>
            </div>
            <div class="form-group">
                <label>Platform Logo</label>
                @if($settings['app_logo_path'])
                <div style="margin-bottom:10px;display:flex;align-items:center;gap:12px">
                    <img src="{{ asset('storage/' . $settings['app_logo_path']) }}" alt="Current logo" style="width:40px;height:40px;object-fit:contain;border-radius:8px;border:1px solid var(--gray-200)">
                    <span class="text-muted text-sm">Current logo</span>
                    <label style="display:flex;align-items:center;gap:4px;font-size:13px;color:var(--danger);cursor:pointer">
                        <input type="checkbox" name="remove_logo" value="1"> Remove
                    </label>
                </div>
                @endif
                <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/svg+xml">
                <small class="text-muted">PNG, JPG, or SVG. Max 2MB. Displayed as a 36x36px icon in the sidebar.</small>
            </div>
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Global Branding
            </button>
        </form>
    </div>
</div>

{{-- Organization Color Themes --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 0 0 20 4 4 0 0 1 0-8 4 4 0 0 0 0-8"/><circle cx="12" cy="12" r="2"/></svg>
            Organization Color Themes
        </span>
    </div>
    <div class="card-body">
        <p class="text-muted" style="margin-bottom:16px">Assign a color theme to each organization. Themes change the sidebar, buttons, and accent colors to match the organization's brand.</p>
        @foreach($organizations as $org)
        @php
            $currentTheme = ($org->settings ?? [])['theme'] ?? 'indigo_night';
        @endphp
        <div class="theme-org-row" style="margin-bottom:24px;padding:16px;background:var(--gray-50);border-radius:var(--radius-lg);border:1px solid var(--gray-200)">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                <div>
                    <strong style="font-size:14px">{{ $org->name }}</strong>
                    <span class="badge badge-gray" style="margin-left:8px;font-size:11px">{{ $palettes[$currentTheme]['name'] }}</span>
                </div>
            </div>
            <div class="theme-grid">
                @foreach($palettes as $key => $palette)
                <form method="POST" action="{{ route('settings.platformBranding.updateOrgTheme', $org) }}" style="margin:0">
                    @csrf @method('PUT')
                    <input type="hidden" name="theme" value="{{ $key }}">
                    <button type="submit" class="theme-card {{ $currentTheme === $key ? 'theme-card-active' : '' }}" title="{{ $palette['name'] }}">
                        <div class="theme-preview">
                            <div class="theme-sidebar-swatch" style="background:{{ $palette['colors']['--sidebar-bg'] }}">
                                <div class="theme-sidebar-lines">
                                    <div class="theme-swatch-dot" style="background:{{ $palette['colors']['--sidebar-active-text'] }}"></div>
                                    <div class="theme-swatch-line" style="background:{{ $palette['colors']['--sidebar-text'] }}"></div>
                                    <div class="theme-swatch-line" style="background:{{ $palette['colors']['--sidebar-text'] }};width:60%"></div>
                                </div>
                            </div>
                            <div class="theme-main-area">
                                <div class="theme-primary-bar" style="background:{{ $palette['colors']['--primary'] }}"></div>
                                <div class="theme-content-lines">
                                    <div class="theme-line"></div>
                                    <div class="theme-line short"></div>
                                </div>
                            </div>
                        </div>
                        <div class="theme-name">{{ $palette['name'] }}</div>
                        @if($currentTheme === $key)
                        <div class="theme-check">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        @endif
                    </button>
                </form>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Per-Organization White-Label --}}
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Organization White-Label Overrides
        </span>
    </div>
    <div class="card-body">
        <p class="text-muted" style="margin-bottom:16px">Override the platform branding for specific organizations. When set, users in that organization will see their custom name and logo instead of the global branding.</p>
        @if($organizations->isEmpty())
        <p class="text-muted">No organizations found.</p>
        @else
        <table>
            <thead>
                <tr>
                    <th>Organization</th>
                    <th>Custom Name</th>
                    <th>Custom Logo</th>
                    <th style="width:120px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($organizations as $org)
                @php
                    $orgSettings = $org->settings ?? [];
                    $customName = $orgSettings['custom_app_name'] ?? '';
                    $customLogo = $orgSettings['custom_logo_path'] ?? null;
                @endphp
                <tr>
                    <form method="POST" action="{{ route('settings.platformBranding.updateOrg', $org) }}" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <td>
                            <strong>{{ $org->name }}</strong>
                            @if($customName)
                            <br><span class="badge badge-blue" style="font-size:11px">White-labeled</span>
                            @endif
                        </td>
                        <td>
                            <input type="text" name="custom_app_name" class="form-control" value="{{ $customName }}" placeholder="Leave empty for default" style="min-width:180px">
                        </td>
                        <td>
                            @if($customLogo)
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                <img src="{{ asset('storage/' . $customLogo) }}" alt="Logo" style="width:28px;height:28px;object-fit:contain;border-radius:6px;border:1px solid var(--gray-200)">
                                <label style="font-size:12px;color:var(--danger);cursor:pointer;display:flex;align-items:center;gap:3px">
                                    <input type="checkbox" name="remove_org_logo" value="1"> Remove
                                </label>
                            </div>
                            @endif
                            <input type="file" name="org_logo" accept="image/png,image/jpeg,image/svg+xml" style="font-size:12px">
                        </td>
                        <td>
                            <button type="submit" class="btn btn-sm btn-secondary">Save</button>
                        </td>
                    </form>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
