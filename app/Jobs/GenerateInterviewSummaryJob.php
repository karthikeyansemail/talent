<?php

namespace App\Jobs;

use App\Models\InterviewSession;
use App\Services\AiServiceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateInterviewSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;

    public function __construct(public InterviewSession $session) {}

    public function handle(): void
    {
        $session = $this->session->load([
            'application.jobPosting', 'candidate',
            'transcripts', 'questions',
        ]);

        // If no transcripts AND no questions, clearly state insufficient data
        if ($session->transcripts->isEmpty() && $session->questions->isEmpty()) {
            $session->update(['summary' => [
                'overall_rating' => 'neutral',
                'narrative' => 'No transcript or question data was captured during this interview. AI summary could not be generated. A manual summary is recommended.',
                'strengths' => [],
                'concerns' => [],
                '_insufficient_data' => true,
            ]]);
            return;
        }

        // If data is sparse (few transcripts, no questions), note it but still attempt AI analysis
        $isLowData = $session->transcripts->count() < 3 && $session->questions->isEmpty();

        $transcript = $session->transcripts->map(fn($t) => [
            'speaker' => $t->speaker,
            'text' => $t->text,
        ])->toArray();

        $questionsData = $session->questions->map(fn($q) => [
            'question' => $q->question_text,
            'answer' => $q->answer_text,
            'evaluation' => $q->evaluation,
            'skill_area' => $q->skill_area,
        ])->toArray();

        try {
            $client = new AiServiceClient();
            $result = $client->generateInterviewSummary([
                'job_title' => $session->application->jobPosting->title ?? '',
                'candidate_name' => $session->candidate->first_name . ' ' . $session->candidate->last_name,
                'interview_type' => $session->interview_type,
                'duration_minutes' => (int) ceil(($session->duration_seconds ?? 0) / 60),
                'transcript' => $transcript,
                'questions_and_evaluations' => $questionsData,
                'low_data' => $isLowData,
            ], $session->organization_id);

            if (!isset($result['error'])) {
                if ($isLowData) {
                    $result['_low_data'] = true;
                }
                $session->update(['summary' => $result]);
            } else {
                \Log::warning("AI summary generation returned error for session {$session->id}: " . ($result['error'] ?? 'unknown'));
                $session->update(['summary' => [
                    'overall_rating' => 'neutral',
                    'narrative' => 'AI summary generation failed. You can try again using the "Generate AI Summary" button.',
                    'strengths' => [],
                    'concerns' => [],
                    '_error' => true,
                ]]);
            }
        } catch (\Exception $e) {
            \Log::error("AI summary generation exception for session {$session->id}: {$e->getMessage()}");
            $session->update(['summary' => [
                'overall_rating' => 'neutral',
                'narrative' => 'AI summary generation failed due to a service error. You can try again using the "Generate AI Summary" button.',
                'strengths' => [],
                'concerns' => [],
                '_error' => true,
            ]]);
        }
    }
}
