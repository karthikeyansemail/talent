<?php

namespace App\Http\Controllers\Placement;

use App\Http\Controllers\Controller;
use App\Models\PlacementDrive;
use App\Services\AiServiceClient;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PlacementDriveController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();
        $drives = PlacementDrive::where('organization_id', $orgId)
            ->withCount('attempts')
            ->orderByDesc('drive_date')
            ->orderByDesc('created_at')
            ->get();

        return view('placement.drives.index', compact('drives'));
    }

    public function create()
    {
        return view('placement.drives.create');
    }

    /**
     * Parse a company hiring document via AI. Reuses the existing
     * /parse-job-description endpoint — its extracted fields (title,
     * skills, experience) map cleanly to drive fields. Drive-specific
     * fields (CGPA, batch eligibility) stay manual on the form.
     */
    public function parseDocument(Request $request): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        $file = $request->file('document');
        $ext  = strtolower($file->getClientOriginalExtension());

        $text = (new DocumentTextExtractor())->extract($file->getPathname(), $ext);
        if (empty(trim($text))) {
            return response()->json(['error' => 'Could not extract text from the document.'], 422);
        }

        $tempPath = $file->store('temp_drive_docs', 'public');

        $client = new AiServiceClient();
        $result = $client->parseJobDescription(['document_text' => $text], Auth::user()->currentOrganizationId());

        if (isset($result['error'])) {
            Storage::disk('public')->delete($tempPath);
            return response()->json(['error' => 'AI service unavailable. Fill fields manually.'], 503);
        }

        $result['_temp_file_path'] = $tempPath;
        $result['_extracted_text'] = $text;
        return response()->json($result);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name'         => 'required|string|max:255',
            'role_title'           => 'required|string|max:255',
            'description'          => 'nullable|string',
            'eligible_courses'     => 'nullable|string',  // comma-separated, normalized below
            'eligible_batch_years' => 'nullable|string',  // comma-separated years
            'min_cgpa'             => 'nullable|numeric|min:0|max:10',
            'required_skills'      => 'nullable|string',  // comma-separated
            'package_lpa'          => 'nullable|integer|min:0',
            'drive_date'           => 'nullable|date|after_or_equal:today',
            'test_format'          => 'required|in:aptitude,aptitude_plus_interview,technical',
            'status'               => 'required|in:draft,open',
            'temp_doc_path'        => 'nullable|string',
            'extracted_text'       => 'nullable|string',
        ]);

        $drive = PlacementDrive::create([
            'organization_id'      => Auth::user()->currentOrganizationId(),
            'company_name'         => $validated['company_name'],
            'role_title'           => $validated['role_title'],
            'description'          => $validated['description'] ?? null,
            'eligible_courses'     => $this->csvToArray($validated['eligible_courses'] ?? ''),
            'eligible_batch_years' => array_map('intval', $this->csvToArray($validated['eligible_batch_years'] ?? '')),
            'min_cgpa'             => $validated['min_cgpa'] ?? null,
            'required_skills'      => $this->csvToArray($validated['required_skills'] ?? ''),
            'package_lpa'          => $validated['package_lpa'] ?? null,
            'drive_date'           => $validated['drive_date'] ?? null,
            'test_format'          => $validated['test_format'],
            'status'               => $validated['status'],
            'source_doc_path'      => $request->input('temp_doc_path'),
            'source_doc_text'      => $request->input('extracted_text'),
            'created_by'           => Auth::id(),
        ]);

        return redirect()->route('placement.drives.show', $drive)
            ->with('success', "Drive created for {$drive->company_name}.");
    }

    public function show(PlacementDrive $drive)
    {
        $this->authorizeDrive($drive);
        $drive->load(['tests', 'attempts.candidate']);
        return view('placement.drives.show', compact('drive'));
    }

    public function edit(PlacementDrive $drive)
    {
        $this->authorizeDrive($drive);
        return view('placement.drives.edit', compact('drive'));
    }

    public function update(Request $request, PlacementDrive $drive)
    {
        $this->authorizeDrive($drive);

        $validated = $request->validate([
            'company_name'         => 'required|string|max:255',
            'role_title'           => 'required|string|max:255',
            'description'          => 'nullable|string',
            'eligible_courses'     => 'nullable|string',
            'eligible_batch_years' => 'nullable|string',
            'min_cgpa'             => 'nullable|numeric|min:0|max:10',
            'required_skills'      => 'nullable|string',
            'package_lpa'          => 'nullable|integer|min:0',
            'drive_date'           => 'nullable|date',
            'test_format'          => 'required|in:aptitude,aptitude_plus_interview,technical',
            'status'               => 'required|in:draft,open,closed,completed',
        ]);

        $drive->update([
            'company_name'         => $validated['company_name'],
            'role_title'           => $validated['role_title'],
            'description'          => $validated['description'] ?? null,
            'eligible_courses'     => $this->csvToArray($validated['eligible_courses'] ?? ''),
            'eligible_batch_years' => array_map('intval', $this->csvToArray($validated['eligible_batch_years'] ?? '')),
            'min_cgpa'             => $validated['min_cgpa'] ?? null,
            'required_skills'      => $this->csvToArray($validated['required_skills'] ?? ''),
            'package_lpa'          => $validated['package_lpa'] ?? null,
            'drive_date'           => $validated['drive_date'] ?? null,
            'test_format'          => $validated['test_format'],
            'status'               => $validated['status'],
        ]);

        return redirect()->route('placement.drives.show', $drive)->with('success', 'Drive updated.');
    }

    public function destroy(PlacementDrive $drive)
    {
        $this->authorizeDrive($drive);
        $name = $drive->company_name;
        $drive->delete();
        return redirect()->route('placement.drives.index')->with('success', "Drive '{$name}' deleted.");
    }

    private function authorizeDrive(PlacementDrive $drive): void
    {
        if ($drive->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }
    }

    private function csvToArray(string $csv): array
    {
        return collect(explode(',', $csv))
            ->map(fn($s) => trim($s))
            ->filter()
            ->values()
            ->all();
    }
}
