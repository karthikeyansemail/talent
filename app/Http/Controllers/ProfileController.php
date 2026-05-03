<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'                  => 'required|string|max:255',
            'current_password'      => 'required_with:password',
            'password'              => 'nullable|string|min:8|confirmed',
        ]);

        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
            }
            $user->password = Hash::make($request->password);
        }

        $user->name = $request->name;
        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Persist the user's chosen theme (light/dark/auto). Called from the
     * sidebar toggle; failure is silently ignored on the client side.
     */
    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme_preference' => 'required|in:light,dark,auto',
        ]);

        $user = Auth::user();
        $user->theme_preference = $request->theme_preference;
        $user->save();

        return response()->json(['ok' => true, 'theme' => $user->theme_preference]);
    }
}
