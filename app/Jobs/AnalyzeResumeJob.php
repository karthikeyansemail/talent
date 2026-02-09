<?php

namespace App\Jobs;

use App\Models\JobApplication;
use App\Services\AiServiceClient;
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
        $result = $client->analyzeResume([
            'resume_text' => $resume->extracted_text,
            'job_title' => $job->title,
            'job_description' => $job->description,
            'required_skills' => $job->required_skills ?? [],
            'min_experience' => $job->min_experience ?? 0,
            'max_experience' => $job->max_experience ?? 10,
        ], $job->organization_id);

        if (isset($result['overall_score'])) {
            $application->update([
                'ai_score' => $result['overall_score'],
                'ai_analysis' => $result,
                'ai_analyzed_at' => now(),
            ]);
        }
    }
}
