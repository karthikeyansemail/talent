<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Resume;
use App\Services\AiServiceClient;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $orgId = Auth::user()->organization_id;
        $query = Candidate::where('organization_id', $orgId)->with(['applications.jobPosting']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by job posting
        if ($request->filled('job_id')) {
            $query->whereHas('applications', function ($q) use ($request) {
                $q->where('job_posting_id', $request->job_id);
            });
        }

        // Filter by experience range
        if ($request->filled('experience')) {
            $range = explode('-', $request->experience);
            if (count($range) === 2) {
                $query->whereBetween('experience_years', [(float) $range[0], (float) $range[1]]);
            }
        }

        // Filter by current title
        if ($request->filled('title')) {
            $query->where('current_title', $request->title);
        }

        // Filter by skill
        if ($request->filled('skill')) {
            $skill = $request->skill;
            $query->whereNotNull('skills')->where('skills', 'like', "%\"{$skill}\"%");
        }

        $sortable = ['first_name', 'email', 'current_title', 'experience_years', 'source', 'created_at'];
        $sort = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $direction = $request->direction === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction);

        $candidates = $query->paginate(15);

        // Data for filter dropdowns
        $jobs = JobPosting::where('organization_id', $orgId)->orderBy('title')->get(['id', 'title']);
        $titles = Candidate::where('organization_id', $orgId)
            ->whereNotNull('current_title')->where('current_title', '!=', '')
            ->distinct()->orderBy('current_title')->pluck('current_title');
        $allSkills = Candidate::where('organization_id', $orgId)
            ->whereNotNull('skills')->pluck('skills')->flatten()->unique()->sort()->values();

        return view('candidates.index', compact('candidates', 'sort', 'direction', 'jobs', 'titles', 'allSkills'));
    }

    public function create()
    {
        return view('candidates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'current_company' => 'nullable|string|max:255',
            'current_title' => 'nullable|string|max:255',
            'experience_years' => 'nullable|numeric|min:0|max:50',
            'skills' => 'nullable|string',
            'source' => 'required|in:upload,referral,direct',
            'notes' => 'nullable|string',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;
        $validated['skills'] = $request->skills
            ? array_map('trim', explode(',', $request->skills))
            : [];
        $candidate = Candidate::create($validated);

        // If a resume was uploaded via AI auto-fill, save it as a Resume record
        if ($request->filled('_temp_file_path')) {
            $tempPath = $request->input('_temp_file_path');
            $permanentPath = 'resumes/' . $candidate->id . '/' . basename($tempPath);

            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->move($tempPath, $permanentPath);

                Resume::create([
                    'candidate_id' => $candidate->id,
                    'file_path' => $permanentPath,
                    'file_name' => $request->input('_temp_file_name', 'resume'),
                    'file_type' => $request->input('_temp_file_type', 'pdf'),
                    'extracted_text' => $request->input('_extracted_text', ''),
                    'uploaded_by' => Auth::id(),
                ]);
            }
        }

        return redirect()->route('candidates.show', $candidate)->with('success', 'Candidate created.');
    }

    public function show(Candidate $candidate)
    {
        $this->authorizeOrg($candidate);
        $candidate->load(['resumes', 'applications.jobPosting']);

        // Get open/draft job postings that this candidate hasn't applied to yet
        $appliedJobIds = $candidate->applications->pluck('job_posting_id')->toArray();
        $availableJobs = JobPosting::where('organization_id', Auth::user()->organization_id)
            ->whereIn('status', ['open', 'draft'])
            ->whereNotIn('id', $appliedJobIds)
            ->orderBy('title')
            ->get(['id', 'title', 'status']);

        return view('candidates.show', compact('candidate', 'availableJobs'));
    }

    public function edit(Candidate $candidate)
    {
        $this->authorizeOrg($candidate);
        return view('candidates.edit', compact('candidate'));
    }

    public function update(Request $request, Candidate $candidate)
    {
        $this->authorizeOrg($candidate);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'current_company' => 'nullable|string|max:255',
            'current_title' => 'nullable|string|max:255',
            'experience_years' => 'nullable|numeric|min:0|max:50',
            'skills' => 'nullable|string',
            'source' => 'required|in:upload,referral,direct',
            'notes' => 'nullable|string',
        ]);

        $validated['skills'] = $request->skills
            ? array_map('trim', explode(',', $request->skills))
            : [];

        $candidate->update($validated);
        return redirect()->route('candidates.show', $candidate)->with('success', 'Candidate updated.');
    }

    public function search(Request $request): JsonResponse
    {
        $orgId = Auth::user()->organization_id;
        $q = $request->input('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $candidates = Candidate::where('organization_id', $orgId)
            ->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->with('resumes:id,candidate_id,file_name')
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'current_title', 'current_company', 'experience_years']);

        return response()->json($candidates->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->full_name,
                'email' => $c->email,
                'title' => $c->current_title,
                'company' => $c->current_company,
                'experience' => $c->experience_years,
                'resumes' => $c->resumes->map(fn($r) => ['id' => $r->id, 'name' => $r->file_name]),
            ];
        }));
    }

    public function applyToJobs(Request $request, Candidate $candidate)
    {
        $this->authorizeOrg($candidate);

        $request->validate([
            'job_ids' => 'required|array|min:1',
            'job_ids.*' => 'exists:job_postings,id',
            'resume_id' => 'nullable|exists:resumes,id',
        ]);

        $orgId = Auth::user()->organization_id;
        $resumeId = $request->resume_id ?: $candidate->resumes()->latest()->value('id');
        $applied = 0;
        $skipped = 0;

        foreach ($request->job_ids as $jobId) {
            // Verify job belongs to same org
            $job = JobPosting::where('id', $jobId)->where('organization_id', $orgId)->first();
            if (!$job) continue;

            // Skip if already applied
            if (JobApplication::where('job_posting_id', $jobId)->where('candidate_id', $candidate->id)->exists()) {
                $skipped++;
                continue;
            }

            JobApplication::create([
                'job_posting_id' => $jobId,
                'candidate_id' => $candidate->id,
                'resume_id' => $resumeId,
                'stage' => 'applied',
                'applied_at' => now(),
            ]);
            $applied++;
        }

        $message = "{$applied} application(s) created.";
        if ($skipped > 0) {
            $message .= " {$skipped} already existed.";
        }

        return back()->with('success', $message);
    }

    public function bulkCreate()
    {
        return view('candidates.bulk-upload');
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'resumes' => 'required|array|min:1|max:20',
            'resumes.*' => 'file|mimes:pdf,docx|max:10240',
        ]);

        $orgId = Auth::user()->organization_id;
        $extractor = new DocumentTextExtractor();
        $aiClient = new AiServiceClient();

        $created = 0;
        $skipped = [];

        foreach ($request->file('resumes') as $file) {
            $fileName = $file->getClientOriginalName();
            $ext = strtolower($file->getClientOriginalExtension());

            // Extract text from resume
            $text = $extractor->extract($file->getPathname(), $ext);

            if (empty(trim($text))) {
                $skipped[] = "{$fileName} — could not extract text";
                continue;
            }

            // AI parse resume to get candidate fields
            $parsed = [];
            try {
                $result = $aiClient->parseResumeProfile(['resume_text' => $text], $orgId);
                if (!isset($result['error'])) {
                    $parsed = $result;
                }
            } catch (\Exception $e) {
                // AI unavailable — continue with fallback
            }

            // Build candidate data from parsed fields (with fallbacks)
            $firstName = $parsed['first_name'] ?? $this->nameFromFileName($fileName, 'first');
            $lastName = $parsed['last_name'] ?? $this->nameFromFileName($fileName, 'last');
            $email = $parsed['email'] ?? DocumentTextExtractor::extractEmail($text);

            // Skip if duplicate email exists for this org
            if ($email && Candidate::where('organization_id', $orgId)->where('email', $email)->exists()) {
                $skipped[] = "{$fileName} — duplicate email ({$email})";
                continue;
            }

            // Build notes from AI summary
            $notesParts = [];
            if (!empty($parsed['summary'])) $notesParts[] = $parsed['summary'];
            if (empty($parsed['first_name'])) {
                $notesParts[] = 'Note: AI parsing could not extract candidate details. Please review and update manually.';
            }

            $candidate = Candidate::create([
                'organization_id' => $orgId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email ?? strtolower($firstName . '.' . $lastName . '@unknown.com'),
                'phone' => $parsed['phone'] ?? null,
                'current_company' => $parsed['current_company'] ?? null,
                'current_title' => $parsed['current_title'] ?? null,
                'experience_years' => $parsed['experience_years'] ?? null,
                'skills' => !empty($parsed['skills']) && is_array($parsed['skills']) ? $parsed['skills'] : [],
                'source' => 'upload',
                'notes' => $notesParts ? implode("\n\n", $notesParts) : null,
            ]);

            // Store resume file
            $permanentPath = 'resumes/' . $candidate->id . '/' . $fileName;
            Storage::disk('public')->putFileAs(
                'resumes/' . $candidate->id,
                $file,
                $fileName
            );

            Resume::create([
                'candidate_id' => $candidate->id,
                'file_path' => $permanentPath,
                'file_name' => $fileName,
                'file_type' => $ext,
                'extracted_text' => $text,
                'uploaded_by' => Auth::id(),
            ]);

            $created++;
        }

        $message = "{$created} candidate(s) created successfully.";
        if (count($skipped) > 0) {
            $message .= ' ' . count($skipped) . ' file(s) skipped: ' . implode('; ', $skipped);
        }

        return redirect()->route('candidates.index')->with('success', $message);
    }

    private function nameFromFileName(string $fileName, string $part): string
    {
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        // Try to split on common separators (space, underscore, hyphen)
        $parts = preg_split('/[\s_\-]+/', $name);
        if (count($parts) >= 2) {
            return $part === 'first' ? ucfirst($parts[0]) : ucfirst($parts[1]);
        }
        return $part === 'first' ? ucfirst($name) : 'Unknown';
    }

    public function destroy(Candidate $candidate)
    {
        $this->authorizeOrg($candidate);
        $candidate->delete();
        return redirect()->route('candidates.index')->with('success', 'Candidate deleted.');
    }

    private function authorizeOrg(Candidate $candidate): void
    {
        if ($candidate->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }
}
