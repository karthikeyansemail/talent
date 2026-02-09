<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $orgId = Auth::user()->organization_id;
        $query = JobPosting::where('organization_id', $orgId)->with('department');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $jobs = $query->latest()->paginate(15);
        $departments = Department::where('organization_id', $orgId)->get();
        return view('jobs.index', compact('jobs', 'departments'));
    }

    public function create()
    {
        $departments = Department::where('organization_id', Auth::user()->organization_id)->get();
        return view('jobs.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'min_experience' => 'nullable|integer|min:0',
            'max_experience' => 'nullable|integer|min:0',
            'required_skills' => 'nullable|string',
            'nice_to_have_skills' => 'nullable|string',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'location' => 'nullable|string|max:255',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,open',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;
        $validated['created_by'] = Auth::id();
        $validated['required_skills'] = $request->required_skills
            ? array_map('trim', explode(',', $request->required_skills))
            : [];
        $validated['nice_to_have_skills'] = $request->nice_to_have_skills
            ? array_map('trim', explode(',', $request->nice_to_have_skills))
            : [];

        $job = JobPosting::create($validated);
        return redirect()->route('jobs.show', $job)->with('success', 'Job posting created.');
    }

    public function show(JobPosting $job)
    {
        $this->authorizeOrg($job);
        $job->load(['department', 'creator', 'applications.candidate', 'applications.resume']);
        return view('jobs.show', compact('job'));
    }

    public function edit(JobPosting $job)
    {
        $this->authorizeOrg($job);
        $departments = Department::where('organization_id', Auth::user()->organization_id)->get();
        return view('jobs.edit', compact('job', 'departments'));
    }

    public function update(Request $request, JobPosting $job)
    {
        $this->authorizeOrg($job);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'min_experience' => 'nullable|integer|min:0',
            'max_experience' => 'nullable|integer|min:0',
            'required_skills' => 'nullable|string',
            'nice_to_have_skills' => 'nullable|string',
            'employment_type' => 'required|in:full_time,part_time,contract,intern',
            'location' => 'nullable|string|max:255',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
        ]);

        $validated['required_skills'] = $request->required_skills
            ? array_map('trim', explode(',', $request->required_skills))
            : [];
        $validated['nice_to_have_skills'] = $request->nice_to_have_skills
            ? array_map('trim', explode(',', $request->nice_to_have_skills))
            : [];

        $job->update($validated);
        return redirect()->route('jobs.show', $job)->with('success', 'Job posting updated.');
    }

    public function destroy(JobPosting $job)
    {
        $this->authorizeOrg($job);
        $job->delete();
        return redirect()->route('jobs.index')->with('success', 'Job posting deleted.');
    }

    public function updateStatus(Request $request, JobPosting $job)
    {
        $this->authorizeOrg($job);
        $request->validate(['status' => 'required|in:draft,open,on_hold,closed']);
        $job->update([
            'status' => $request->status,
            'closed_at' => $request->status === 'closed' ? now() : $job->closed_at,
        ]);
        return back()->with('success', 'Job status updated.');
    }

    private function authorizeOrg(JobPosting $job): void
    {
        if ($job->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }
}
