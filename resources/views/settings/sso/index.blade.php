@extends('layouts.app')
@section('title', 'SSO Configuration')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Single Sign-On (SSO)</h1>
        <p class="page-subtitle">Configure OAuth providers to allow users to sign in with their existing accounts.</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

@php
$providers = [
    'google' => [
        'label' => 'Google',
        'description' => 'Allow users to sign in with their Google / Google Workspace account.',
        'fields' => [
            ['key' => 'client_id',     'label' => 'Client ID',     'placeholder' => 'Your Google OAuth Client ID'],
            ['key' => 'client_secret', 'label' => 'Client Secret', 'placeholder' => 'Your Google OAuth Client Secret', 'secret' => true],
        ],
        'extra' => [],
        'icon_color' => '#EA4335',
    ],
    'microsoft' => [
        'label' => 'Microsoft',
        'description' => 'Allow users to sign in with Microsoft accounts (Azure AD / Entra ID).',
        'fields' => [
            ['key' => 'client_id',     'label' => 'Application (Client) ID', 'placeholder' => 'Azure AD Application ID'],
            ['key' => 'client_secret', 'label' => 'Client Secret Value',     'placeholder' => 'Azure AD Client Secret', 'secret' => true],
        ],
        'extra' => [
            ['key' => 'tenant_id', 'label' => 'Tenant ID', 'placeholder' => 'Leave blank for multi-tenant (common)'],
        ],
        'icon_color' => '#00A4EF',
    ],
    'okta' => [
        'label' => 'Okta',
        'description' => 'Allow users to sign in via your Okta organisation.',
        'fields' => [
            ['key' => 'client_id',     'label' => 'Client ID',     'placeholder' => 'Okta OAuth Client ID'],
            ['key' => 'client_secret', 'label' => 'Client Secret', 'placeholder' => 'Okta OAuth Client Secret', 'secret' => true],
        ],
        'extra' => [
            ['key' => 'okta_domain', 'label' => 'Okta Domain', 'placeholder' => 'e.g. dev-123456.okta.com'],
        ],
        'icon_color' => '#007DC1',
    ],
];
@endphp

<div style="display:grid;gap:24px;max-width:860px">
    @foreach($providers as $providerKey => $meta)
    @php $setting = $settings[$providerKey] ?? null; @endphp
    <div class="card">
        <div class="card-body">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px">
                <div style="display:flex;align-items:center;gap:14px">
                    @if($providerKey === 'google')
                    <div style="width:40px;height:40px;border-radius:8px;background:#fff;border:1px solid var(--gray-200);display:flex;align-items:center;justify-content:center">
                        <svg width="22" height="22" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    </div>
                    @elseif($providerKey === 'microsoft')
                    <div style="width:40px;height:40px;border-radius:8px;background:#fff;border:1px solid var(--gray-200);display:flex;align-items:center;justify-content:center">
                        <svg width="22" height="22" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M11.4 11.4H0V0h11.4v11.4z" fill="#F25022"/><path d="M24 11.4H12.6V0H24v11.4z" fill="#7FBA00"/><path d="M11.4 24H0V12.6h11.4V24z" fill="#00A4EF"/><path d="M24 24H12.6V12.6H24V24z" fill="#FFB900"/></svg>
                    </div>
                    @elseif($providerKey === 'okta')
                    <div style="width:40px;height:40px;border-radius:8px;background:#007DC1;display:flex;align-items:center;justify-content:center">
                        <svg width="22" height="22" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="10" fill="#007DC1"/><circle cx="12" cy="12" r="4.2" fill="#fff"/></svg>
                    </div>
                    @endif
                    <div>
                        <h3 style="margin:0;font-size:16px;font-weight:600">{{ $meta['label'] }}</h3>
                        <p style="margin:4px 0 0;font-size:13px;color:var(--gray-500)">{{ $meta['description'] }}</p>
                    </div>
                </div>
                <span style="padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $setting?->is_enabled ? 'var(--success-light,#d1fae5)' : 'var(--gray-100)' }};color:{{ $setting?->is_enabled ? 'var(--success,#065f46)' : 'var(--gray-500)' }}">
                    {{ $setting?->is_enabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>

            <form method="POST" action="{{ route('settings.sso.update', $providerKey) }}">
                @csrf
                @method('PUT')

                {{-- Enable toggle --}}
                <div class="form-group" style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin:0">
                        <div style="position:relative;width:44px;height:24px" id="toggle-wrap-{{ $providerKey }}">
                            <input type="checkbox" name="is_enabled" value="1" {{ $setting?->is_enabled ? 'checked' : '' }}
                                   style="opacity:0;width:0;height:0;position:absolute"
                                   onchange="var w=document.getElementById('toggle-wrap-{{ $providerKey }}');w.querySelector('.toggle-track').style.background=this.checked?'var(--primary)':'var(--gray-300)';w.querySelector('.toggle-thumb').style.transform=this.checked?'translateX(20px)':'translateX(2px)'">
                            <div class="toggle-track" style="position:absolute;inset:0;border-radius:24px;background:{{ $setting?->is_enabled ? 'var(--primary)' : 'var(--gray-300)' }};transition:background 0.2s"></div>
                            <div class="toggle-thumb" style="position:absolute;top:2px;left:0;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.2);transition:transform 0.2s;transform:{{ $setting?->is_enabled ? 'translateX(20px)' : 'translateX(2px)' }}"></div>
                        </div>
                        <span style="font-size:14px;font-weight:500">Enable {{ $meta['label'] }} SSO</span>
                    </label>
                </div>

                {{-- Credential fields --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    @foreach($meta['fields'] as $field)
                    <div class="form-group" style="margin:0">
                        <label style="font-size:13px;font-weight:500;margin-bottom:6px;display:block">{{ $field['label'] }}</label>
                        <input
                            type="{{ ($field['secret'] ?? false) ? 'password' : 'text' }}"
                            name="{{ $field['key'] }}"
                            class="form-control @error($field['key'], $providerKey) is-invalid @enderror"
                            placeholder="{{ $field['placeholder'] }}"
                            autocomplete="off"
                            @if(!($field['secret'] ?? false)) value="{{ $setting?->{$field['key']} ?? '' }}" @endif
                        >
                        @error($field['key'], $providerKey)
                        <p style="font-size:12px;color:var(--danger,#dc2626);margin:4px 0 0">{{ $message }}</p>
                        @enderror
                        @if(($field['secret'] ?? false) && !$errors->{$providerKey}->has($field['key']))
                        <p style="font-size:11px;color:var(--gray-400);margin:4px 0 0">Leave blank to keep existing value</p>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Extra config fields (tenant_id, okta_domain) --}}
                @if(!empty($meta['extra']))
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
                    @foreach($meta['extra'] as $ef)
                    <div class="form-group" style="margin:0">
                        <label style="font-size:13px;font-weight:500;margin-bottom:6px;display:block">{{ $ef['label'] }}</label>
                        <input
                            type="text"
                            name="extra_config[{{ $ef['key'] }}]"
                            class="form-control @error('extra_config.'.$ef['key'], $providerKey) is-invalid @enderror"
                            placeholder="{{ $ef['placeholder'] }}"
                            value="{{ $setting?->extra_config[$ef['key']] ?? '' }}"
                        >
                        @error('extra_config.'.$ef['key'], $providerKey)
                        <p style="font-size:12px;color:var(--danger,#dc2626);margin:4px 0 0">{{ $message }}</p>
                        @enderror
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Redirect URI --}}
                <div class="form-group" style="margin-top:16px;margin-bottom:0">
                    <label style="font-size:13px;font-weight:500;margin-bottom:6px;display:block">Callback / Redirect URI <span style="font-size:11px;color:var(--gray-400);font-weight:400">(copy this into your OAuth app)</span></label>
                    <div style="display:flex;gap:8px">
                        <input type="text" class="form-control" readonly value="{{ url('/auth/' . $providerKey . '/callback') }}" style="background:var(--gray-50);font-family:monospace;font-size:13px" id="redirect-{{ $providerKey }}">
                        <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('redirect-{{ $providerKey }}').value).then(()=>{this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)})" style="white-space:nowrap;padding:0 14px;border:1px solid var(--gray-200);border-radius:6px;background:#fff;cursor:pointer;font-size:13px;color:var(--gray-600)">Copy</button>
                    </div>
                </div>

                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--gray-100)">
                    <button type="submit" class="btn btn-primary">Save {{ $meta['label'] }} Settings</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endsection
