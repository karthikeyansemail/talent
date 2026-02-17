<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeResumeJob;
use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Resume;
use App\Services\AiServiceClient;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    public function index(JobPosting $job)
    {
        $this->authorizeOrg($job);

        $applications = $job->applications()
            ->with(['candidate', 'resume'])
            ->when(request('stage'), fn($q, $stage) => $q->where('stage', $stage))
            ->orderByDesc('ai_score')
            ->paginate(20);

        return view('applications.index', compact('job', 'applications'));
    }

    public function store(Request $request, JobPosting $job)
    {
        $this->authorizeOrg($job);

        $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'resume_id' => 'nullable|exists:resumes,id',
        ]);

        $existing = JobApplication::where('job_posting_id', $job->id)
            ->where('candidate_id', $request->candidate_id)
            ->first();

        if ($existing) {
            return back()->with('error', 'Candidate already applied to this job.');
        }

        $application = JobApplication::create([
            'job_posting_id' => $job->id,
            'candidate_id' => $request->candidate_id,
            'resume_id' => $request->resume_id,
            'stage' => 'applied',
            'applied_at' => now(),
        ]);

        return back()->with('success', 'Application created.');
    }

    public function show(JobApplication $application)
    {
        $application->load(['jobPosting', 'candidate', 'resume', 'feedback.interviewer']);
        if ($application->jobPosting->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
        return view('applications.show', compact('application'));
    }

    public function updateStage(Request $request, JobApplication $application)
    {
        if ($application->jobPosting->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }

        $request->validate([
            'stage' => 'required|in:applied,ai_shortlisted,hr_screening,technical_round_1,technical_round_2,offer,hired,rejected',
            'stage_notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);

        $application->update([
            'stage' => $request->stage,
            'stage_notes' => $request->stage_notes,
            'rejection_reason' => $request->stage === 'rejected' ? $request->rejection_reason : $application->rejection_reason,
        ]);

        if ($request->stage === 'hired') {
            $application->jobPosting->update(['status' => 'closed', 'closed_at' => now()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'stage' => $application->stage,
                'label' => ucwords(str_replace('_', ' ', $application->stage)),
            ]);
        }

        return back()->with('success', 'Application stage updated.');
    }

    public function triggerAiAnalysis(JobApplication $application)
    {
        if ($application->jobPosting->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }

        AnalyzeResumeJob::dispatch($application);

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'queued',
                'application_id' => $application->id,
            ]);
        }

        return back()->with('success', 'AI analysis queued. Results will appear shortly.');
    }

    public function analysisStatus(JobApplication $application)
    {
        if ($application->jobPosting->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }

        $since = request('since');
        $application = $application->fresh();
        $isComplete = $application->ai_analyzed_at && $since &&
                      $application->ai_analyzed_at->gt($since);

        if ($isComplete) {
            $html = view('components.ai-analysis-inline', [
                'application' => $application,
                'compact' => request()->boolean('compact'),
            ])->render();

            return response()->json([
                'status' => 'completed',
                'ai_score' => $application->ai_score,
                'ai_analyzed_at' => $application->ai_analyzed_at->toIso8601String(),
                'html' => $html,
            ]);
        }

        return response()->json(['status' => 'processing']);
    }

    public function bulkApply(Request $request, JobPosting $job)
    {
        $this->authorizeOrg($job);

        $request->validate([
            'resumes' => 'required|array|min:1|max:20',
            'resumes.*' => 'file|mimes:pdf,docx|max:10240',
        ]);

        $orgId = Auth::user()->organization_id;
        $extractor = new DocumentTextExtractor();
        $aiClient = new AiServiceClient();

        $applied = 0;
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

            $firstName = $parsed['first_name'] ?? $this->nameFromFileName($fileName, 'first');
            $lastName = $parsed['last_name'] ?? $this->nameFromFileName($fileName, 'last');
            $email = $parsed['email'] ?? DocumentTextExtractor::extractEmail($text);

            // Check if candidate with this email already exists in org
            $candidate = null;
            if ($email) {
                $candidate = Candidate::where('organization_id', $orgId)
                    ->where('email', $email)->first();
            }

            if (!$candidate) {
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
            }

            // Store resume file
            Storage::disk('public')->putFileAs(
                'resumes/' . $candidate->id,
                $file,
                $fileName
            );

            $resume = Resume::create([
                'candidate_id' => $candidate->id,
                'file_path' => 'resumes/' . $candidate->id . '/' . $fileName,
                'file_name' => $fileName,
                'file_type' => $ext,
                'extracted_text' => $text,
                'uploaded_by' => Auth::id(),
            ]);

            // Create application (skip if already applied to this job)
            $alreadyApplied = JobApplication::where('job_posting_id', $job->id)
                ->where('candidate_id', $candidate->id)->exists();

            if (!$alreadyApplied) {
                JobApplication::create([
                    'job_posting_id' => $job->id,
                    'candidate_id' => $candidate->id,
                    'resume_id' => $resume->id,
                    'stage' => 'applied',
                    'applied_at' => now(),
                ]);
                $applied++;
            } else {
                $skipped[] = "{$fileName} — {$candidate->full_name} already applied";
            }
        }

        $message = "{$applied} candidate(s) added and applied successfully.";
        if (count($skipped)) {
            $message .= ' ' . count($skipped) . ' skipped: ' . implode('; ', $skipped);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'applied' => $applied]);
        }

        return back()->with('success', $message);
    }

    private function nameFromFileName(string $fileName, string $part): string
    {
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $parts = preg_split('/[\s_\-]+/', $name);
        if (count($parts) >= 2) {
            return $part === 'first' ? ucfirst($parts[0]) : ucfirst($parts[1]);
        }
        return $part === 'first' ? ucfirst($name) : 'Unknown';
    }

    private function authorizeOrg(JobPosting $job): void
    {
        if ($job->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }
}
