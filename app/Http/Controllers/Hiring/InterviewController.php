<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateInterviewSummaryJob;
use App\Mail\InterviewerAccountCreated;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InterviewController extends Controller
{
    /**
     * List interviews — interviewers see only theirs, HR/admin see all org sessions.
     */
    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();
        $query = InterviewSession::where('organization_id', $orgId)
            ->with(['candidate', 'application.jobPosting', 'interviewer']);

        // Interviewer-only users see just their own sessions
        if (Auth::user()->hasRole('interviewer') && !Auth::user()->hasAnyRole(['hr_manager', 'hiring_manager', 'org_admin', 'super_admin'])) {
            $query->where('interviewer_id', Auth::id());
        }

        $sessions = $query->latest('scheduled_at')->get();

        $upcoming = $sessions->where('status', 'scheduled');
        $active = $sessions->where('status', 'in_progress');
        $completed = $sessions->where('status', 'completed');

        return view('interviews.index', compact('upcoming', 'active', 'completed'));
    }

    /**
     * Show form to assign interview (HR/admin only).
     */
    public function create()
    {
        $orgId = Auth::user()->currentOrganizationId();

        // Get candidates with active applications in interview-eligible stages
        $applications = JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $orgId))
            ->whereIn('stage', ['applied', 'hr_screening', 'ai_shortlisted', 'technical_round_1', 'technical_round_2'])
            ->with(['candidate', 'jobPosting'])
            ->get();

        return view('interviews.create', compact('applications'));
    }

    /**
     * Search employees for interviewer assignment (AJAX).
     */
    public function searchEmployees(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();
        $query = $request->input('q', '');

        $employees = Employee::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->first_name . ' ' . $e->last_name,
                'email' => $e->email,
                'designation' => $e->designation,
                'has_account' => $e->user_id !== null,
            ]);

        return response()->json($employees);
    }

    /**
     * Store a new interview session.
     * Auto-creates user account for the employee if they don't have one.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_application_id' => 'required|exists:job_applications,id',
            'employee_id' => 'required|exists:employees,id',
            'interview_type' => 'required|in:hr_screening,technical_round_1,technical_round_2',
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $orgId = Auth::user()->currentOrganizationId();

        $application = JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $orgId))
            ->findOrFail($validated['job_application_id']);

        $employee = Employee::where('organization_id', $orgId)->findOrFail($validated['employee_id']);

        // Auto-create user account if employee doesn't have one
        $interviewerUserId = $employee->user_id;
        if (!$interviewerUserId) {
            $plainPassword = Str::random(12);
            $user = User::create([
                'name' => trim($employee->first_name . ' ' . $employee->last_name),
                'email' => $employee->email,
                'password' => $plainPassword,
                'role' => 'interviewer',
                'organization_id' => $orgId,
                'is_active' => true,
            ]);
            UserRole::create(['user_id' => $user->id, 'role' => 'interviewer']);

            $employee->update(['user_id' => $user->id]);
            $interviewerUserId = $user->id;

            // Send credentials email
            try {
                Mail::to($employee->email)->send(new InterviewerAccountCreated(
                    $user->name,
                    $user->email,
                    $plainPassword,
                    url('/login')
                ));
            } catch (\Exception $e) {
                // Email failure should not block interview assignment
                \Log::warning("Failed to send interviewer account email to {$employee->email}: {$e->getMessage()}");
            }
        }

        $session = InterviewSession::create([
            'organization_id' => $orgId,
            'job_application_id' => $application->id,
            'candidate_id' => $application->candidate_id,
            'interviewer_id' => $interviewerUserId,
            'assigned_by' => Auth::id(),
            'interview_type' => $validated['interview_type'],
            'scheduled_at' => $validated['scheduled_at'],
            'notes' => $validated['notes'],
            'status' => 'scheduled',
        ]);

        $redirectTo = $request->input('_redirect');
        if ($redirectTo && str_starts_with($redirectTo, url('/'))) {
            return redirect($redirectTo)->with('success', 'Interview scheduled successfully.');
        }

        return redirect()->route('interviews.index')->with('success', 'Interview assigned successfully.');
    }

    /**
     * Show interview session page (3 states: scheduled/in_progress/completed).
     */
    public function show(InterviewSession $session)
    {
        $this->authorizeSession($session);

        $session->load([
            'candidate', 'application.jobPosting', 'interviewer',
            'transcripts', 'questions', 'feedback',
        ]);

        if ($session->status === 'completed') {
            return redirect()->route('interviews.summary', $session);
        }

        return view('interviews.show', compact('session'));
    }

    /**
     * Start the interview session (AJAX).
     */
    public function start(InterviewSession $session)
    {
        $this->authorizeSession($session);

        if ($session->status !== 'scheduled') {
            return response()->json(['error' => 'Session cannot be started.'], 422);
        }

        $session->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json(['status' => 'ok', 'started_at' => $session->started_at->toISOString()]);
    }

    /**
     * End the interview session (AJAX).
     */
    public function end(Request $request, InterviewSession $session)
    {
        $this->authorizeSession($session);

        if ($session->status !== 'in_progress') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Session is not active.'], 422);
            }
            return redirect()->route('interviews.summary', $session);
        }

        $session->update([
            'status' => 'completed',
            'ended_at' => now(),
            'duration_seconds' => $session->started_at ? abs(now()->diffInSeconds($session->started_at)) : 0,
        ]);

        // Dispatch async AI summary generation
        GenerateInterviewSummaryJob::dispatch($session);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok', 'redirect' => route('interviews.summary', $session)]);
        }
        return redirect()->route('interviews.summary', $session)->with('success', 'Interview ended successfully.');
    }

    /**
     * Manually trigger AI summary generation (AJAX).
     */
    public function generateSummary(Request $request, InterviewSession $session)
    {
        $this->authorizeSession($session);

        if ($session->status !== 'completed') {
            return response()->json(['error' => 'Interview must be completed first.'], 422);
        }

        if ($session->transcripts()->count() === 0) {
            return response()->json(['error' => 'No transcript data available.'], 422);
        }

        // Clear previous failed summary so polling sees fresh state
        if ($session->summary && !empty($session->summary['_error'])) {
            $session->update(['summary' => null]);
        }

        GenerateInterviewSummaryJob::dispatch($session);

        return response()->json(['status' => 'queued']);
    }

    /**
     * Polling endpoint: check if AI summary has been generated.
     */
    public function summaryStatus(InterviewSession $session)
    {
        $this->authorizeSession($session);

        if ($session->summary && empty($session->summary['_error'])) {
            return response()->json([
                'status' => 'completed',
                'summary' => $session->summary,
            ]);
        }

        if ($session->summary && !empty($session->summary['_error'])) {
            return response()->json([
                'status' => 'failed',
                'message' => $session->summary['narrative'] ?? 'AI summary generation failed.',
            ]);
        }

        return response()->json(['status' => 'processing']);
    }

    /**
     * Save manual interviewer summary notes.
     */
    public function saveManualSummary(Request $request, InterviewSession $session)
    {
        $this->authorizeSession($session);

        $validated = $request->validate([
            'manual_summary' => 'required|string|max:5000',
        ]);

        $existing = $session->summary ?? [];
        $existing['manual_notes'] = $validated['manual_summary'];
        $session->update(['summary' => $existing]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Post-interview summary page.
     */
    public function summary(InterviewSession $session)
    {
        $this->authorizeSession($session);

        $session->load([
            'candidate', 'application.jobPosting', 'interviewer',
            'transcripts', 'questions', 'feedback',
        ]);

        return view('interviews.summary', compact('session'));
    }

    /**
     * Complete interview — record outcome (advance/reject/waitlist).
     */
    public function complete(Request $request, InterviewSession $session)
    {
        $this->authorizeSession($session);

        if ($session->status !== 'completed') {
            return back()->with('error', 'Interview must be ended before recording an outcome.');
        }

        $validated = $request->validate([
            'outcome' => 'required|in:advanced,rejected,waitlisted',
        ]);

        $session->update(['outcome' => $validated['outcome']]);

        // Update application stage based on outcome
        $application = $session->application;
        if ($validated['outcome'] === 'advanced') {
            $nextStage = match ($session->interview_type) {
                'hr_screening' => 'technical_round_1',
                'technical_round_1' => 'technical_round_2',
                'technical_round_2' => 'offer',
                default => null,
            };
            if ($nextStage && $application) {
                $application->update(['stage' => $nextStage]);
            }
        } elseif ($validated['outcome'] === 'rejected' && $application) {
            $application->update(['stage' => 'rejected']);
        }
        // waitlisted: no stage change — candidate stays in current stage

        $outcomeLabels = ['advanced' => 'Advanced to next round', 'rejected' => 'Rejected', 'waitlisted' => 'Waitlisted'];
        return redirect()->route('interviews.summary', $session)
            ->with('success', 'Interview outcome recorded: ' . $outcomeLabels[$validated['outcome']]);
    }

    /**
     * Revert outcome — reset to pending decision and restore application stage.
     */
    public function revertOutcome(InterviewSession $session)
    {
        $this->authorizeSession($session);

        if (!$session->outcome) {
            return back()->with('error', 'No outcome to revert.');
        }

        $previousOutcome = $session->outcome;

        // Restore application stage to match the interview type (undo advancement/rejection)
        $application = $session->application;
        if ($application) {
            $stageForType = match ($session->interview_type) {
                'hr_screening' => 'hr_screening',
                'technical_round_1' => 'technical_round_1',
                'technical_round_2' => 'technical_round_2',
                default => null,
            };
            if ($stageForType) {
                $application->update(['stage' => $stageForType]);
            }
        }

        $session->update(['outcome' => null]);

        $outcomeLabels = ['advanced' => 'Advanced', 'rejected' => 'Rejected', 'waitlisted' => 'Waitlisted'];
        return redirect()->route('interviews.summary', $session)
            ->with('success', 'Outcome "' . ($outcomeLabels[$previousOutcome] ?? $previousOutcome) . '" has been reverted. You can now record a new decision.');
    }

    /**
     * Reopen interview — create a new scheduled session for the same candidate.
     */
    public function reopen(InterviewSession $session)
    {
        $this->authorizeSession($session);

        if ($session->status !== 'completed') {
            return back()->with('error', 'Only completed interviews can be reopened.');
        }

        if (in_array($session->outcome, ['advanced', 'rejected'])) {
            return back()->with('error', 'Cannot reopen an interview with a final outcome. Revert the decision first.');
        }

        $newSession = InterviewSession::create([
            'organization_id' => $session->organization_id,
            'job_application_id' => $session->job_application_id,
            'candidate_id' => $session->candidate_id,
            'interviewer_id' => $session->interviewer_id,
            'assigned_by' => Auth::id(),
            'interview_type' => $session->interview_type,
            'status' => 'scheduled',
            'notes' => 'Reopened from interview #' . $session->id,
        ]);

        return redirect()->route('interviews.show', $newSession)
            ->with('success', 'Interview reopened — a new session has been scheduled.');
    }

    /**
     * Cancel/delete a scheduled interview (HR/admin only).
     */
    public function destroy(InterviewSession $session)
    {
        if ($session->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        if ($session->status === 'in_progress') {
            return back()->with('error', 'Cannot delete an active interview session.');
        }

        $session->delete();
        return redirect()->route('interviews.index')->with('success', 'Interview cancelled.');
    }

    /**
     * Authorize that the current user can access this session.
     */
    private function authorizeSession(InterviewSession $session): void
    {
        if ($session->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        // Interviewer-only users can only see their own sessions
        if (Auth::user()->hasRole('interviewer') && !Auth::user()->hasAnyRole(['hr_manager', 'hiring_manager', 'org_admin', 'super_admin'])) {
            if ($session->interviewer_id !== Auth::id()) {
                abort(403);
            }
        }
    }
}
