<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'org_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $organization = Organization::create([
            'name' => $request->org_name,
            'slug' => Str::slug($request->org_name) . '-' . Str::random(4),
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'org_admin',
            'organization_id' => $organization->id,
            'is_active' => true,
        ]);
        $user->roles()->create(['role' => 'org_admin']);

        Auth::login($user);
        return redirect('/dashboard');
    }
}
