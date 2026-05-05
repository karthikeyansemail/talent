<?php

namespace App\Jobs;

use App\Models\AptitudeTest;
use App\Models\TestQuestion;
use App\Services\AiServiceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generates aptitude test questions via AI and persists them.
 * Runs in background so the HTTP request returns immediately and the
 * frontend can poll for completion (avoids PHP's 30s execution limit).
 */
class GenerateAptitudeTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public int $testId,
        public array $payload  // company_name, role_title, num_mcq, etc.
    ) {}

    public static function cacheKey(int $testId): string
    {
        return "aptitude_test_gen_{$testId}";
    }

    public function handle(): void
    {
        $cacheKey = self::cacheKey($this->testId);
        $test = AptitudeTest::find($this->testId);
        if (!$test) {
            Cache::put($cacheKey, ['status' => 'failed', 'error' => 'Test not found'], now()->addMinutes(5));
            return;
        }

        Cache::put($cacheKey, ['status' => 'running', 'phase' => 'Calling AI service…'], now()->addMinutes(10));

        try {
            $client = new AiServiceClient();
            $result = $client->generateAptitudeTest($this->payload, $test->organization_id);

            if (isset($result['error'])) {
                Cache::put($cacheKey, ['status' => 'failed', 'error' => $result['error']], now()->addMinutes(5));
                Log::warning('Aptitude test generation failed', ['test_id' => $this->testId, 'error' => $result['error']]);
                return;
            }

            Cache::put($cacheKey, ['status' => 'running', 'phase' => 'Saving questions…'], now()->addMinutes(10));

            DB::transaction(function () use ($test, $result) {
                // Update title/instructions if AI gave better ones (and officer didn't override)
                $updates = [];
                if (empty($test->instructions) && !empty($result['instructions'])) {
                    $updates['instructions'] = $result['instructions'];
                }
                if ($updates) $test->update($updates);

                foreach (($result['questions'] ?? []) as $i => $q) {
                    TestQuestion::create([
                        'aptitude_test_id'    => $test->id,
                        'order'               => $i + 1,
                        'type'                => $q['type'] ?? 'mcq',
                        'question_text'       => $q['question_text'] ?? '',
                        'context'             => $q['context'] ?? null,
                        'topic'               => $q['topic'] ?? null,
                        'difficulty'          => $q['difficulty'] ?? 'medium',
                        'marks'               => $q['marks'] ?? 1,
                        'options'             => $q['options'] ?? null,
                        'correct_option'      => $q['correct_option'] ?? null,
                        'ideal_answer'        => $q['ideal_answer'] ?? null,
                        'rubric_points'       => $q['rubric_points'] ?? null,
                        'expected_word_count' => $q['expected_word_count'] ?? null,
                    ]);
                }
            });

            $count = $test->questions()->count();
            Cache::put($cacheKey, [
                'status'    => 'complete',
                'question_count' => $count,
                'redirect'  => route('placement.tests.edit', $test->id),
            ], now()->addMinutes(10));

            Log::info("Aptitude test generated: test_id={$this->testId} questions={$count}");
        } catch (\Throwable $e) {
            Log::error('Aptitude test generation error: ' . $e->getMessage(), ['test_id' => $this->testId]);
            Cache::put($cacheKey, ['status' => 'failed', 'error' => $e->getMessage()], now()->addMinutes(5));
        }
    }
}
