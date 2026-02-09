<?php

namespace App\Http\Controllers\ResourceAllocation;

use App\Http\Controllers\Controller;
use App\Jobs\MatchProjectResourcesJob;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $orgId = Auth::user()->organization_id;
        $query = Project::where('organization_id', $orgId)->withCount('resourceMatches');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $projects = $query->latest()->paginate(15);
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'required_skills' => 'nullable|string',
            'required_technologies' => 'nullable|string',
            'complexity_level' => 'required|in:low,medium,high,critical',
            'domain_context' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:planning,active,completed,on_hold',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;
        $validated['created_by'] = Auth::id();
        $validated['required_skills'] = $request->required_skills
            ? array_map('trim', explode(',', $request->required_skills))
            : [];
        $validated['required_technologies'] = $request->required_technologies
            ? array_map('trim', explode(',', $request->required_technologies))
            : [];

        $project = Project::create($validated);
        return redirect()->route('projects.show', $project)->with('success', 'Project created.');
    }

    public function show(Project $project)
    {
        $this->authorizeOrg($project);
        $project->load(['creator', 'resourceMatches.employee.department']);
        return view('projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        $this->authorizeOrg($project);
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeOrg($project);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'required_skills' => 'nullable|string',
            'required_technologies' => 'nullable|string',
            'complexity_level' => 'required|in:low,medium,high,critical',
            'domain_context' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:planning,active,completed,on_hold',
        ]);

        $validated['required_skills'] = $request->required_skills
            ? array_map('trim', explode(',', $request->required_skills))
            : [];
        $validated['required_technologies'] = $request->required_technologies
            ? array_map('trim', explode(',', $request->required_technologies))
            : [];

        $project->update($validated);
        return redirect()->route('projects.show', $project)->with('success', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        $this->authorizeOrg($project);
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }

    public function findResources(Project $project)
    {
        $this->authorizeOrg($project);
        MatchProjectResourcesJob::dispatch($project);
        return back()->with('success', 'Resource matching queued. Results will appear shortly.');
    }

    private function authorizeOrg(Project $project): void
    {
        if ($project->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }
}
