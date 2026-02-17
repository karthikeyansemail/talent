<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PlatformBrandingController extends Controller
{
    public function edit()
    {
        $settings = [
            'app_name' => PlatformSetting::get('app_name', 'Nalam Compass'),
            'app_name_short' => PlatformSetting::get('app_name_short', 'Nalam'),
            'app_name_accent' => PlatformSetting::get('app_name_accent', 'Compass'),
            'app_logo_path' => PlatformSetting::get('app_logo_path'),
        ];

        $organizations = Organization::orderBy('name')->get();

        return view('settings.platform-branding', compact('settings', 'organizations'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_name_short' => 'required|string|max:50',
            'app_name_accent' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $short = $request->app_name_short;
        $accent = $request->app_name_accent ?? '';
        $fullName = trim($short . ' ' . $accent);

        PlatformSetting::set('app_name', $fullName);
        PlatformSetting::set('app_name_short', $short);
        PlatformSetting::set('app_name_accent', $accent);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            $oldPath = PlatformSetting::get('app_logo_path');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('logo')->store('branding', 'public');
            PlatformSetting::set('app_logo_path', $path);
        }

        if ($request->boolean('remove_logo')) {
            $oldPath = PlatformSetting::get('app_logo_path');
            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }
            PlatformSetting::set('app_logo_path', null);
        }

        return back()->with('success', 'Platform branding updated successfully.');
    }

    public function updateOrgBranding(Request $request, Organization $organization)
    {
        $request->validate([
            'custom_app_name' => 'nullable|string|max:100',
            'org_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $settings = $organization->settings ?? [];

        $settings['custom_app_name'] = $request->custom_app_name ?: null;

        if ($request->hasFile('org_logo')) {
            // Delete old org logo if exists
            if (!empty($settings['custom_logo_path'])) {
                Storage::disk('public')->delete($settings['custom_logo_path']);
            }
            $path = $request->file('org_logo')->store('branding/orgs', 'public');
            $settings['custom_logo_path'] = $path;
        }

        if ($request->boolean('remove_org_logo')) {
            if (!empty($settings['custom_logo_path'])) {
                Storage::disk('public')->delete($settings['custom_logo_path']);
            }
            $settings['custom_logo_path'] = null;
        }

        $organization->update(['settings' => $settings]);

        return back()->with('success', "Branding updated for {$organization->name}.");
    }
}
