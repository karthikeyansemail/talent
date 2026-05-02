@extends('layouts.app')
@section('title', 'Workspace Modules')
@section('page-title', 'Workspace Modules')
@section('content')
<div class="page-header">
    <h1>Modules: {{ $organization->name }}</h1>
    <p class="text-muted" style="margin:6px 0 0;font-size:14px">
        Pick an industry template (auto-enables relevant modules) or choose <strong>Custom</strong> to toggle each module independently.
        Disabled modules are completely hidden from this workspace's users.
    </p>
</div>

<form method="POST" action="{{ route('settings.organizations.updateModules', $organization) }}" id="modulesForm">
    @csrf @method('PUT')

    {{-- Industry template selector --}}
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                Industry Template
            </span>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
                @foreach($templates as $key => $tpl)
                <label style="display:flex;flex-direction:column;gap:6px;padding:14px;border:2px solid {{ ($organization->industry_template ?? 'software') === $key ? 'var(--primary)' : 'var(--gray-200)' }};border-radius:10px;cursor:pointer;background:{{ ($organization->industry_template ?? 'software') === $key ? 'var(--primary-50, #eff6ff)' : 'var(--white)' }}">
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="radio" name="industry_template" value="{{ $key }}"
                            {{ ($organization->industry_template ?? 'software') === $key ? 'checked' : '' }}
                            style="accent-color:var(--primary)">
                        <strong style="font-size:14px">{{ $tpl['label'] }}</strong>
                    </div>
                    <div style="font-size:12px;color:var(--gray-500);padding-left:24px">
                        @if($key === 'custom')
                            Pick modules manually below
                        @else
                            @foreach($tpl['modules'] as $modKey)<span style="display:inline-block;background:var(--gray-100);color:var(--gray-700);border-radius:4px;padding:2px 6px;margin:2px 2px 2px 0;font-size:11px">{{ $allModules[$modKey]['label'] ?? $modKey }}</span>@endforeach
                        @endif
                    </div>
                </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Custom module checkboxes (visible when 'custom' is selected) --}}
    <div class="card" id="customModulesCard" style="display:{{ ($organization->industry_template ?? 'software') === 'custom' ? 'block' : 'none' }}">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Modules
            </span>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px">
                @foreach($allModules as $key => $mod)
                <label style="display:flex;gap:10px;align-items:flex-start;padding:12px;border:1px solid var(--gray-200);border-radius:8px;cursor:pointer">
                    <input type="checkbox" name="modules[]" value="{{ $key }}"
                        {{ in_array($key, $enabled, true) ? 'checked' : '' }}
                        style="accent-color:var(--primary);margin-top:3px">
                    <div>
                        <div style="font-weight:600;font-size:14px;color:var(--gray-800)">{{ $mod['label'] }}</div>
                        <div style="font-size:12px;color:var(--gray-500);margin-top:2px">{{ $mod['description'] }}</div>
                    </div>
                </label>
                @endforeach
            </div>
            <div style="margin-top:12px;padding:10px 14px;background:#fff7ed;border-left:3px solid #f97316;border-radius:6px;font-size:13px;color:var(--gray-700)">
                <strong>Note:</strong> Each module works independently. Disabling Interviews keeps existing interview records but hides the menu and prevents new interviews.
                Plan-level limits (Free vs Cloud Enterprise) still apply on top of module toggles.
            </div>
        </div>
    </div>

    {{-- Currently enabled modules summary --}}
    <div class="card">
        <div class="card-header">
            <span class="card-header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Currently Active
            </span>
        </div>
        <div class="card-body">
            @if(empty($enabled))
                <p class="text-muted">No modules enabled yet. Pick a template or check modules above.</p>
            @else
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    @foreach($enabled as $modKey)
                    <span style="background:var(--primary-50,#eff6ff);color:var(--primary);border:1px solid var(--primary-100,#dbeafe);border-radius:6px;padding:6px 10px;font-size:13px;font-weight:500">
                        ✓ {{ $allModules[$modKey]['label'] ?? $modKey }}
                    </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="flex gap-10" style="margin-top:20px">
        <button type="submit" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Modules
        </button>
        <a href="{{ route('settings.organizations.edit', $organization) }}" class="btn btn-secondary">Back</a>
    </div>
</form>

<script>
(function() {
    var radios = document.querySelectorAll('input[name="industry_template"]');
    var customCard = document.getElementById('customModulesCard');
    radios.forEach(function(r) {
        r.addEventListener('change', function() {
            customCard.style.display = (r.value === 'custom') ? 'block' : 'none';
            // Visual update of selected card
            radios.forEach(function(other) {
                var lbl = other.closest('label');
                if (other.checked) {
                    lbl.style.borderColor = 'var(--primary)';
                    lbl.style.background = 'var(--primary-50, #eff6ff)';
                } else {
                    lbl.style.borderColor = 'var(--gray-200)';
                    lbl.style.background = 'var(--white)';
                }
            });
        });
    });
})();
</script>
@endsection
