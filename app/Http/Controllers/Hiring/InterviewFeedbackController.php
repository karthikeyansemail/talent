<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Models\InterviewFeedback;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InterviewFeedbackController extends Controller
{
    public function store(Request $request, JobApplication $application)
    {
        if ($application->jobPosting->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        $validated = $request->validate([
            'stage' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'strengths' => 'nullable|string',
            'weaknesses' => 'nullable|string',
            'recommendation' => 'required|in:strong_yes,yes,neutral,no,strong_no',
            'notes' => 'nullable|string',
        ]);

        $validated['job_application_id'] = $application->id;
        $validated['interviewer_id'] = Auth::id();

        InterviewFeedback::create($validated);
        return back()->with('success', 'Feedback submitted.');
    }

    public function destroy(InterviewFeedback $feedback)
    {
        if ($feedback->application->jobPosting->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }
        $feedback->delete();
        return back()->with('success', 'Feedback removed.');
    }
}
