<?php

namespace App\Http\Controllers\ResourceAllocation;

use App\Http\Controllers\Controller;
use App\Jobs\MatchProjectResourcesJob;
use App\Models\Project;
use App\Models\ProjectSprintSheet;
use App\Services\SpreadsheetParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        $project->load(['creator', 'resourceMatches.employee.department', 'sprintSheets']);
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

    public function uploadSprintSheets(Request $request, Project $project)
    {
        $this->authorizeOrg($project);

        $request->validate([
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'file|mimes:csv,xlsx,txt|max:5120',
        ]);

        $parser = new SpreadsheetParser();
        $uploaded = 0;
        $errors = [];

        foreach ($request->file('files') as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'txt') {
                $ext = 'csv';
            }

            $path = $file->store('sprint-sheets/' . $project->id, 'public');
            $fullPath = Storage::disk('public')->path($path);

            $sheet = ProjectSprintSheet::create([
                'project_id' => $project->id,
                'organization_id' => $project->organization_id,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'uploaded_by' => Auth::id(),
            ]);

            try {
                $rows = $parser->parse($fullPath, $ext);

                // Build a summary of the sprint data for AI context
                $summary = $this->buildSprintSummary($rows);

                $sheet->update([
                    'status' => 'parsed',
                    'row_count' => count($rows),
                    'parsed_summary' => $summary,
                ]);

                $uploaded++;
            } catch (\Exception $e) {
                $sheet->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
                $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        $message = "{$uploaded} spreadsheet(s) uploaded and parsed successfully.";
        if (!empty($errors)) {
            $message .= ' Errors: ' . implode('; ', $errors);
            return back()->with('warning', $message);
        }

        return back()->with('success', $message);
    }

    public function deleteSprintSheet(Project $project, ProjectSprintSheet $sprintSheet)
    {
        $this->authorizeOrg($project);

        if ($sprintSheet->project_id !== $project->id) {
            abort(403);
        }

        Storage::disk('public')->delete($sprintSheet->file_path);
        $sprintSheet->delete();

        return back()->with('success', 'Sprint spreadsheet removed.');
    }

    private function buildSprintSummary(array $rows): array
    {
        $employees = [];
        $statuses = [];
        $totalPoints = 0;
        $completedPoints = 0;
        $sprints = [];

        foreach ($rows as $row) {
            $email = $row['employee_email'] ?? $row['email'] ?? '';
            if ($email) {
                $employees[$email] = ($employees[$email] ?? 0) + 1;
            }

            $status = strtolower($row['status'] ?? '');
            if ($status) {
                $statuses[$status] = ($statuses[$status] ?? 0) + 1;
            }

            $points = (float) ($row['story_points'] ?? $row['points'] ?? 0);
            $totalPoints += $points;
            if (in_array($status, ['done', 'completed', 'closed', 'resolved'])) {
                $completedPoints += $points;
            }

            $sprint = $row['sprint_name'] ?? $row['sprint'] ?? '';
            if ($sprint) {
                $sprints[$sprint] = ($sprints[$sprint] ?? 0) + 1;
            }
        }

        return [
            'total_rows' => count($rows),
            'unique_employees' => count($employees),
            'employee_task_counts' => $employees,
            'status_distribution' => $statuses,
            'total_story_points' => $totalPoints,
            'completed_story_points' => $completedPoints,
            'sprints' => $sprints,
        ];
    }

    private function authorizeOrg(Project $project): void
    {
        if ($project->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }
}
