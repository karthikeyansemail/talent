<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
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
}
