<?php

namespace App\Http\Controllers\ResourceAllocation;

use App\Http\Controllers\Controller;
use App\Jobs\SyncJiraTasksJob;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();
        $query = Employee::where('organization_id', $orgId)->with('department');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $employees = $query->latest()->paginate(15);
        $departments = Department::where('organization_id', $orgId)->get();
        return view('employees.index', compact('employees', 'departments'));
    }

    public function create()
    {
        $departments = Department::where('organization_id', Auth::user()->currentOrganizationId())->get();
        return view('employees.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'department_id' => 'nullable|exists:departments,id',
            'designation' => 'nullable|string|max:255',
        ]);

        $validated['organization_id'] = Auth::user()->currentOrganizationId();
        $employee = Employee::create($validated);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee created.');
    }

    public function show(Employee $employee)
    {
        $this->authorizeOrg($employee);
        $employee->load(['department', 'jiraTasks', 'resourceMatches.project', 'resume']);
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $this->authorizeOrg($employee);
        $departments = Department::where('organization_id', Auth::user()->currentOrganizationId())->get();
        return view('employees.edit', compact('employee', 'departments'));
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorizeOrg($employee);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'department_id' => 'nullable|exists:departments,id',
            'designation' => 'nullable|string|max:255',
        ]);

        $employee->update($validated);
        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $this->authorizeOrg($employee);
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted.');
    }

    public function syncJiraTasks(Employee $employee)
    {
        $this->authorizeOrg($employee);
        SyncJiraTasksJob::dispatch($employee);
        return back()->with('success', 'Jira sync queued.');
    }

    private function authorizeOrg(Employee $employee): void
    {
        if ($employee->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }
    }
}
