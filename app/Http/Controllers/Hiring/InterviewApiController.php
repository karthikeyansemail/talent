<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use App\Models\InterviewTranscript;
use App\Services\AiServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InterviewApiController extends Controller
{
    /**
     * Save a batch of transcript segments.
     */
    public function storeTranscript(Request $request, InterviewSession $session)
    {
        $this->authorizeSession($session);

        $request->validate([
            'segments' => 'required|array|min:1|max:50',
            'segments.*.speaker' => 'required|in:interviewer,candidate',
            'segments.*.text' => 'required|string|max:5000',
            'segments.*.offset_seconds' => 'required|integer|min:0',
            'segments.*.confidence' => 'nullable|numeric|min:0|max:1',
        ]);

        $count = 0;
        foreach ($request->segments as $seg) {
            InterviewTranscript::create([
                'interview_session_id' => $session->id,
                'speaker' => $seg['speaker'],
                'text' => $seg['text'],
                'offset_seconds' => $seg['offset_seconds'],
                'confidence' => $seg['confidence'] ?? null,
            ]);
            $count++;
        }

        return response()->json(['status' => 'ok', 'saved' => $count]);
    }

    /**
     * Generate AI questions based on conversation context.
     */
    public function generateQuestions(Request $request, InterviewSession $session)
    {
        $this->authorizeSession($session);

        $job = $session->application->jobPosting;
        $candidate = $session->candidate;

        // Get recent transcript for context
        $recentTranscripts = $session->transcripts()
            ->orderByDesc('offset_seconds')
            ->limit(30)
            ->get()
            ->reverse()
            ->values();

        $existingQuestions = $session->questions()->pluck('question_text')->toArray();

        $client = new AiServiceClient();
        $result = $client->generateInterviewQuestions([
            'job_title' => $job->title,
            'job_description' => $job->description ?? '',
            'required_skills' => $job->required_skills ?? [],
            'candidate_name' => $candidate->first_name . ' ' . $candidate->last_name,
            'candidate_experience_years' => $candidate->experience_years,
            'conversation_so_far' => $recentTranscripts->map(fn($t) => [
                'speaker' => $t->speaker,
                'text' => $t->text,
            ])->toArray(),
            'questions_already_asked' => $existingQuestions,
            'interview_type' => $session->interview_type,
        ], $session->organization_id);

        if (isset($result['error'])) {
            return response()->json(['error' => 'AI service unavailable. Please try again.'], 503);
        }

        // Save suggested questions
        $saved = [];
        foreach ($result['questions'] ?? [] as $q) {
            $question = InterviewQuestion::create([
                'interview_session_id' => $session->id,
                'question_text' => $q['question'] ?? $q['question_text'] ?? '',
                'question_type' => $q['type'] ?? $q['question_type'] ?? 'follow_up',
                'difficulty' => $q['difficulty'] ?? 'medium',
                'skill_area' => $q['skill_area'] ?? null,
                'status' => 'suggested',
                'suggested_at_offset' => $request->input('current_offset', 0),
            ]);
            $saved[] = $question;
        }

        return response()->json(['questions' => $saved]);
    }

    /**
     * Evaluate a candidate's answer to a question.
     */
    public function evaluateAnswer(Request $request, InterviewSession $session)
    {
        $this->authorizeSession($session);

        $request->validate([
            'question_id' => 'required|exists:interview_questions,id',
            'answer_text' => 'required|string',
        ]);

        $question = InterviewQuestion::where('id', $request->question_id)
            ->where('interview_session_id', $session->id)
            ->firstOrFail();

        $job = $session->application->jobPosting;

        $client = new AiServiceClient();
        $result = $client->evaluateInterviewAnswer([
            'question' => $question->question_text,
            'answer' => $request->answer_text,
            'skill_area' => $question->skill_area,
            'job_title' => $job->title,
            'required_skills' => $job->required_skills ?? [],
        ], $session->organization_id);

        if (isset($result['error'])) {
            return response()->json(['error' => 'AI service unavailable.'], 503);
        }

        $question->update([
            'answer_text' => $request->answer_text,
            'evaluation' => $result,
            'status' => 'answered',
        ]);

        return response()->json(['evaluation' => $result]);
    }

    /**
     * Update question status (asked/answered/skipped).
     */
    public function updateQuestionStatus(Request $request, InterviewSession $session, InterviewQuestion $question)
    {
        $this->authorizeSession($session);

        if ($question->interview_session_id !== $session->id) {
            abort(404);
        }

        $request->validate([
            'status' => 'required|in:asked,answered,skipped',
        ]);

        $data = ['status' => $request->status];
        if ($request->status === 'asked') {
            $data['asked_at_offset'] = $request->input('current_offset', 0);
        }

        $question->update($data);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Polling endpoint — return current session state.
     */
    public function sessionState(Request $request, InterviewSession $session)
    {
        $this->authorizeSession($session);

        $sinceTranscriptId = $request->input('since_transcript_id', 0);

        return response()->json([
            'status' => $session->status,
            'duration_seconds' => $session->started_at
                ? now()->diffInSeconds($session->started_at)
                : 0,
            'new_transcripts' => $session->transcripts()
                ->where('id', '>', $sinceTranscriptId)
                ->get(),
            'questions' => $session->questions()
                ->orderByDesc('id')
                ->get(),
            'summary' => $session->summary,
        ]);
    }

    /**
     * Update interviewer notes.
     */
    public function updateNotes(Request $request, InterviewSession $session)
    {
        $this->authorizeSession($session);

        $request->validate(['notes' => 'nullable|string|max:5000']);
        $session->update(['notes' => $request->notes]);

        return response()->json(['status' => 'ok']);
    }

    private function authorizeSession(InterviewSession $session): void
    {
        if ($session->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        if (Auth::user()->hasRole('interviewer') && !Auth::user()->hasAnyRole(['hr_manager', 'hiring_manager', 'org_admin', 'super_admin'])) {
            if ($session->interviewer_id !== Auth::id()) {
                abort(403);
            }
        }
    }
}
