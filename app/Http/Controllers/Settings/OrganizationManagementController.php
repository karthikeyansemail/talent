<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationManagementController extends Controller
{
    public function index()
    {
        $organizations = Organization::withCount('users')
            ->orderBy('name')
            ->get();

        return view('settings.organizations.index', compact('organizations'));
    }

    public function create()
    {
        return view('settings.organizations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:6',
        ]);

        $org = Organization::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'domain' => $request->domain,
            'is_active' => true,
        ]);

        User::create([
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'password' => $request->admin_password,
            'role' => 'org_admin',
            'organization_id' => $org->id,
            'is_active' => true,
        ]);

        return redirect()->route('settings.organizations.index')
            ->with('success', "Organization \"{$org->name}\" created with admin account {$request->admin_email}.");
    }

    public function edit(Organization $organization)
    {
        $organization->loadCount('users');
        return view('settings.organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
        ];

        if (\Illuminate\Support\Facades\Auth::user()->isSuperAdmin()) {
            $rules['subscription_plan'] = 'nullable|in:free,cloud_enterprise,self_hosted';
            $rules['subscription_expires_at'] = 'nullable|date';
            $rules['support_expires_at'] = 'nullable|date';
        }

        $request->validate($rules);

        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'domain' => $request->domain,
            'is_active' => $request->boolean('is_active'),
            'is_premium' => $request->boolean('is_premium'),
        ];

        if (\Illuminate\Support\Facades\Auth::user()->isSuperAdmin()) {
            $data['subscription_plan'] = $request->subscription_plan ?? 'free';
            $data['subscription_expires_at'] = $request->subscription_expires_at ?: null;
            $data['support_expires_at'] = $request->support_expires_at ?: null;
        }

        $organization->update($data);

        return back()->with('success', "Organization \"{$organization->name}\" updated.");
    }
}
