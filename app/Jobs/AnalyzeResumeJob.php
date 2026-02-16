<?php

namespace App\Jobs;

use App\Models\JobApplication;
use App\Services\AiServiceClient;
use App\Services\ScoringEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeResumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 180;

    public function __construct(public JobApplication $application) {}

    public function handle(): void
    {
        $application = $this->application->load(['resume', 'jobPosting']);
        $resume = $application->resume;
        $job = $application->jobPosting;

        if (!$resume || !$resume->extracted_text) {
            return;
        }

        $client = new AiServiceClient();
        $payload = [
            'resume_text' => $resume->extracted_text,
            'job_title' => $job->title,
            'job_description' => $job->description,
            'required_skills' => $job->required_skills ?? [],
            'min_experience' => $job->min_experience ?? 0,
            'max_experience' => $job->max_experience ?? 10,
        ];

        // Use new signal extraction endpoint
        $result = $client->extractResumeSignals($payload, $job->organization_id);

        if (isset($result['error'])) {
            // Fallback to legacy endpoint if signal extraction fails
            $result = $client->analyzeResume($payload, $job->organization_id);
            if (isset($result['overall_score'])) {
                $application->update([
                    'ai_score' => $result['overall_score'],
                    'ai_analysis' => $result,
                    'ai_analyzed_at' => now(),
                ]);
            }
            return;
        }

        // Extract numeric signals for scoring engine
        $signalKeys = ScoringEngine::signalKeys();
        $signals = [];
        foreach ($signalKeys as $key) {
            if (isset($result[$key])) {
                $signals[$key] = (float) $result[$key];
            }
        }

        // Compute score using configurable weights
        $engine = new ScoringEngine();
        $scored = $engine->computeScore($signals, $job->organization_id);

        $application->update([
            'ai_score' => $scored['score'],
            'ai_signals' => $signals,
            'ai_score_version' => $scored['version'],
            'ai_analysis' => $result,
            'ai_analyzed_at' => now(),
        ]);
    }
}
