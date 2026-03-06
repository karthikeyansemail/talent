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

        $wantsEnabled = $request->boolean('is_enabled');

        // Only update credentials if non-empty (preserve existing encrypted values)
        if (!empty($validated['client_id'])) {
            $setting->client_id = $validated['client_id'];
        }
        if (!empty($validated['client_secret'])) {
            $setting->client_secret = $validated['client_secret'];
        }

        // Extra config per provider (use array_key_exists so clearing a field to blank actually removes it)
        $extraConfig = $setting->extra_config ?? [];
        if ($provider === 'microsoft' && $request->has('extra_config.tenant_id')) {
            $extraConfig['tenant_id'] = $validated['extra_config']['tenant_id'] ?? null;
        }
        if ($provider === 'okta' && $request->has('extra_config.okta_domain')) {
            $extraConfig['okta_domain'] = $validated['extra_config']['okta_domain'] ?? null;
        }
        $setting->extra_config = $extraConfig ?: null;

        // Validate credentials exist before allowing enable
        if ($wantsEnabled) {
            $errors = [];

            if (empty($setting->client_id)) {
                $errors['client_id'] = 'Client ID is required to enable ' . ucfirst($provider) . ' SSO.';
            }
            if (empty($setting->client_secret)) {
                $errors['client_secret'] = 'Client Secret is required to enable ' . ucfirst($provider) . ' SSO.';
            }
            if ($provider === 'okta' && empty($extraConfig['okta_domain'])) {
                $errors['extra_config.okta_domain'] = 'Okta Domain is required to enable Okta SSO.';
            }

            if (!empty($errors)) {
                $setting->is_enabled = false;
                $setting->save();

                return back()->withErrors($errors, $provider)->withInput();
            }
        }

        $setting->is_enabled = $wantsEnabled;
        $setting->save();

        return back()->with('success', ucfirst($provider) . ' SSO settings saved successfully.');
    }
}
