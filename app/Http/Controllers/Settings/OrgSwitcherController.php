<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrgSwitcherController extends Controller
{
    public function switch(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        $request->validate([
            'organization_id' => 'required|exists:organizations,id',
        ]);

        session(['viewing_organization_id' => (int) $request->organization_id]);

        $org = Organization::find($request->organization_id);
        return back()->with('success', 'Switched to ' . $org->name . '.');
    }

    public function reset()
    {
        session()->forget('viewing_organization_id');
        return back()->with('success', 'Returned to your own organization.');
    }
}
