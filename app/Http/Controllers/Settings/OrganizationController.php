<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrganizationController extends Controller
{
    public function edit()
    {
        $organization = Auth::user()->currentOrganization();
        return view('settings.organization', compact('organization'));
    }

    public function update(Request $request)
    {
        $validCurrencies = array_keys(config('currencies', ['USD' => []]));

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'domain'   => 'nullable|string|max:255',
            'currency' => 'nullable|string|in:' . implode(',', $validCurrencies),
        ]);

        Auth::user()->currentOrganization()->update($validated);
        return back()->with('success', 'Organization updated.');
    }

    /**
     * Org admin chooses their org's color theme. Available to org_admin
     * (super admin can also change any org's theme via Platform Branding).
     */
    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme' => 'required|string|in:' . implode(',', array_keys(ThemeService::palettes())),
        ]);

        $org = Auth::user()->currentOrganization();
        if (!$org) {
            return back()->with('error', 'No organization context.');
        }

        $settings = $org->settings ?? [];
        $settings['theme'] = $request->theme;
        $org->update(['settings' => $settings]);

        return back()->with('success', 'Theme updated.');
    }
}
