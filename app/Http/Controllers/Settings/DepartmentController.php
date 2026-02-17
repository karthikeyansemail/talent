<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->organization_id;
        $departments = Department::where('organization_id', $orgId)
            ->withCount(['jobPostings', 'employees'])
            ->orderBy('name')
            ->get();

        return view('settings.departments', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;
        Department::create($validated);

        return back()->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department)
    {
        $this->authorizeOrg($department);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $department->update($validated);

        return back()->with('success', 'Department updated.');
    }

    public function destroy(Department $department)
    {
        $this->authorizeOrg($department);

        if ($department->jobPostings()->exists() || $department->employees()->exists()) {
            return back()->with('error', 'Cannot delete a department that has job postings or employees assigned to it.');
        }

        $department->delete();

        return back()->with('success', 'Department deleted.');
    }

    private function authorizeOrg(Department $department): void
    {
        if ($department->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }
}
