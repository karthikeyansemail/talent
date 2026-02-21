<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SsoSetting;
use Illuminate\Http\Request;

class SsoConfigController extends Controller
{
    public function index()
    {
        // Ensure all 3 providers exist
        foreach (['google', 'microsoft', 'okta'] as $p) {
            SsoSetting::firstOrCreate(['provider' => $p]);
        }

        $settings = SsoSetting::all()->keyBy('provider');

        return view('settings.sso.index', compact('settings'));
    }

    public function update(Request $request, string $provider)
    {
        abort_unless(in_array($provider, ['google', 'microsoft', 'okta']), 404);

        $setting = SsoSetting::firstOrCreate(['provider' => $provider]);

        $validated = $request->validate([
            'is_enabled'             => 'sometimes|boolean',
            'client_id'              => 'nullable|string|max:500',
            'client_secret'          => 'nullable|string|max:500',
            'extra_config.tenant_id' => 'nullable|string|max:200',
            'extra_config.okta_domain' => 'nullable|string|max:200',
        ]);

        $setting->is_enabled = $request->boolean('is_enabled');

        // Only update credentials if non-empty (preserve existing encrypted values)
        if (!empty($validated['client_id'])) {
            $setting->client_id = $validated['client_id'];
        }
        if (!empty($validated['client_secret'])) {
            $setting->client_secret = $validated['client_secret'];
        }

        // Extra config per provider
        $extraConfig = $setting->extra_config ?? [];
        if ($provider === 'microsoft' && isset($validated['extra_config']['tenant_id'])) {
            $extraConfig['tenant_id'] = $validated['extra_config']['tenant_id'];
        }
        if ($provider === 'okta' && isset($validated['extra_config']['okta_domain'])) {
            $extraConfig['okta_domain'] = $validated['extra_config']['okta_domain'];
        }
        $setting->extra_config = $extraConfig ?: null;

        $setting->save();

        return back()->with('success', ucfirst($provider) . ' SSO settings saved successfully.');
    }
}
