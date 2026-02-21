<?php

namespace App\Http\Controllers\ResourceAllocation;

use App\Http\Controllers\Controller;
use App\Jobs\MatchProjectResourcesJob;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectSprintSheet;
use App\Services\AiServiceClient;
use App\Services\DocumentTextExtractor;
use App\Services\SpreadsheetParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();
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

        $validated['organization_id'] = Auth::user()->currentOrganizationId();
        $validated['created_by'] = Auth::id();
        $validated['required_skills'] = $request->required_skills
            ? array_map('trim', explode(',', $request->required_skills))
            : [];
        $validated['required_technologies'] = $request->required_technologies
            ? array_map('trim', explode(',', $request->required_technologies))
            : [];

        $project = Project::create($validated);

        // Persist the charter document uploaded during AI auto-fill (if any)
        $this->saveCharterFromTemp($request, $project);

        return redirect()->route('projects.show', $project)->with('success', 'Project created.');
    }

    public function show(Project $project)
    {
        $this->authorizeOrg($project);
        $project->load(['creator', 'resourceMatches.employee.department', 'sprintSheets', 'documents']);
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

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'queued',
                'project_id' => $project->id,
            ]);
        }

        return back()->with('success', 'Resource matching queued. Results will appear shortly.');
    }

    public function matchStatus(Project $project)
    {
        $this->authorizeOrg($project);

        $since = request('since');
        $project->load('resourceMatches.employee.department');
        $matches = $project->resourceMatches->sortByDesc('match_score');

        $hasNewMatches = $matches->isNotEmpty() && $since &&
                         $matches->first()->updated_at->gt($since);

        if ($hasNewMatches) {
            $html = view('projects._resource-matches-table', [
                'project' => $project,
            ])->render();

            return response()->json([
                'status' => 'completed',
                'match_count' => $matches->count(),
                'html' => $html,
            ]);
        }

        return response()->json(['status' => 'processing']);
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

    // ──────────────────────────────────────────────────────────────────────────
    // Project Documents
    // ──────────────────────────────────────────────────────────────────────────

    public function uploadDocument(Request $request, Project $project)
    {
        $this->authorizeOrg($project);

        $request->validate([
            'document' => 'required|file|mimes:pdf,docx|max:10240',
            'label'    => 'nullable|string|max:255',
        ]);

        $file      = $request->file('document');
        $extension = strtolower($file->getClientOriginalExtension());
        $path      = $file->store('project-documents/' . $project->id, 'public');

        $extractor = new DocumentTextExtractor();
        $text      = $extractor->extract($file->getRealPath(), $extension);

        ProjectDocument::create([
            'project_id'        => $project->id,
            'organization_id'   => $project->organization_id,
            'document_type'     => 'supplemental',
            'label'             => $request->input('label') ?: 'Supplemental Document',
            'original_filename' => $file->getClientOriginalName(),
            'file_path'         => $path,
            'file_size'         => $file->getSize(),
            'file_type'         => $extension,
            'extracted_text'    => $text,
            'uploaded_by'       => Auth::id(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function deleteDocument(Project $project, ProjectDocument $document)
    {
        $this->authorizeOrg($project);

        if ($document->project_id !== $project->id) {
            abort(403);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }

    public function syncFromDocuments(Project $project)
    {
        $this->authorizeOrg($project);

        $documents = $project->documents()->whereNotNull('extracted_text')->get();

        if ($documents->isEmpty()) {
            return back()->with('error', 'No documents to sync from. Upload a requirement document first.');
        }

        // Concatenate all document text, labelled by document
        $combined = $documents->map(function ($doc) {
            return "=== {$doc->label} ({$doc->original_filename}) ===\n{$doc->extracted_text}";
        })->implode("\n\n");

        $aiClient = new AiServiceClient();
        $result   = $aiClient->parseProjectRequirements(['document_text' => $combined], $project->organization_id);

        if (isset($result['error'])) {
            return back()->with('error', 'AI service unavailable. Please try again.');
        }

        // Update project fields from re-parsed data
        $updates = [];
        if (!empty($result['description']))   { $updates['description']   = $result['description']; }
        if (!empty($result['domain_context'])) { $updates['domain_context'] = $result['domain_context']; }
        if (!empty($result['required_skills'])) {
            $updates['required_skills'] = is_array($result['required_skills'])
                ? $result['required_skills']
                : array_map('trim', explode(',', $result['required_skills']));
        }
        if (!empty($result['required_technologies'])) {
            $updates['required_technologies'] = is_array($result['required_technologies'])
                ? $result['required_technologies']
                : array_map('trim', explode(',', $result['required_technologies']));
        }
        if (!empty($result['complexity_level']) && in_array($result['complexity_level'], ['low', 'medium', 'high', 'critical'])) {
            $updates['complexity_level'] = $result['complexity_level'];
        }

        if (!empty($updates)) {
            $project->update($updates);
        }

        return back()->with('success', 'Project details synced from ' . $documents->count() . ' document(s).');
    }

    private function saveCharterFromTemp(Request $request, Project $project): void
    {
        $tempKey  = $request->input('charter_temp_key');
        $origName = $request->input('charter_original_name');
        $fileType = $request->input('charter_file_type', 'pdf');
        $fileSize = (int) $request->input('charter_file_size', 0);

        if (!$tempKey || !Str::isUuid($tempKey)) {
            return;
        }

        $tempFilePath = 'project-documents/temp/' . $tempKey . '.' . $fileType;
        $tempTextPath = 'project-documents/temp/' . $tempKey . '.txt';

        if (!Storage::disk('public')->exists($tempFilePath)) {
            return;
        }

        // Move to permanent storage
        $permanentPath = 'project-documents/' . $project->id . '/' . $tempKey . '.' . $fileType;
        Storage::disk('public')->move($tempFilePath, $permanentPath);

        $extractedText = Storage::disk('public')->exists($tempTextPath)
            ? Storage::disk('public')->get($tempTextPath)
            : null;

        Storage::disk('public')->delete($tempTextPath);

        ProjectDocument::create([
            'project_id'        => $project->id,
            'organization_id'   => $project->organization_id,
            'document_type'     => 'charter',
            'label'             => 'Requirement Document / Project Charter',
            'original_filename' => $origName ?? ('charter.' . $fileType),
            'file_path'         => $permanentPath,
            'file_size'         => $fileSize,
            'file_type'         => $fileType,
            'extracted_text'    => $extractedText,
            'uploaded_by'       => Auth::id(),
        ]);
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
        if ($project->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }
    }
}
