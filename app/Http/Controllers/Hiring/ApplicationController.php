<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeResumeJob;
use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'resume_id' => 'required|exists:resumes,id',
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

        return back()->with('success', 'Application stage updated.');
    }

    public function triggerAiAnalysis(JobApplication $application)
    {
        if ($application->jobPosting->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }

        AnalyzeResumeJob::dispatch($application);
        return back()->with('success', 'AI analysis queued. Results will appear shortly.');
    }

    private function authorizeOrg(JobPosting $job): void
    {
        if ($job->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }
}
