<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::where('organization_id', Auth::user()->currentOrganizationId())->latest()->paginate(15);
        return view('settings.users.index', compact('users'));
    }

    public function create()
    {
        return view('settings.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:org_admin,hr_manager,hiring_manager,resource_manager,management,employee',
        ]);

        $validated['organization_id'] = Auth::user()->currentOrganizationId();
        $validated['is_active'] = true;

        User::create($validated);
        return redirect()->route('settings.users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        if ($user->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }
        return view('settings.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:org_admin,hr_manager,hiring_manager,resource_manager,management,employee',
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8']);
            $validated['password'] = $request->password;
        }

        $user->update($validated);
        return redirect()->route('settings.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('settings.users.index')->with('success', 'User deleted.');
    }

    public function toggleActive(User $user)
    {
        if ($user->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'User status updated.');
    }
}
