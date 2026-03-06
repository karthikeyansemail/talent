<?php

namespace App\Services;

use App\Models\AiProcessingLog;
use Illuminate\Support\Facades\Http;

class AiServiceClient
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('ai.service_url'), '/');
        $this->timeout = config('ai.timeout', 120);
    }

    public function analyzeResume(array $data, ?int $orgId = null): array
    {
        return $this->call('/analyze-resume', $data, $orgId);
    }

    public function extractResumeSignals(array $data, ?int $orgId = null): array
    {
        return $this->call('/extract-resume-signals', $data, $orgId);
    }

    public function extractJiraSignals(array $data, ?int $orgId = null): array
    {
        return $this->call('/extract-jira-signals', $data, $orgId);
    }

    public function matchProjectResources(array $data, ?int $orgId = null): array
    {
        return $this->call('/match-project-resources', $data, $orgId);
    }

    public function parseJobDescription(array $data, ?int $orgId = null): array
    {
        return $this->call('/parse-job-description', $data, $orgId);
    }

    public function parseResumeProfile(array $data, ?int $orgId = null): array
    {
        return $this->call('/parse-resume-profile', $data, $orgId);
    }

    public function parseProjectRequirements(array $data, ?int $orgId = null): array
    {
        return $this->call('/parse-project-requirements', $data, $orgId);
    }

    public function analyzeSignals(array $data, ?int $orgId = null): array
    {
        return $this->call('/analyze-signals', $data, $orgId);
    }

    public function analyzeWorkPulse(\App\Models\Employee $employee): array
    {
        $tasks = $employee->tasks->map(fn($t) => [
            'summary'      => $t->title ?? '',
            'type'         => $t->task_type ?? 'Task',
            'status'       => $t->status ?? '',
            'priority'     => $t->priority ?? 'Medium',
            'story_points' => $t->story_points,
            'created_at'   => $t->source_created_at?->toDateString() ?? '',
            'completed_at' => $t->completed_at?->toDateString(),
            'labels'       => $t->labels ?? [],
        ])->values()->toArray();

        $commSignals = $employee->relationLoaded('signals')
            ? $employee->signals->map(fn($s) => [
                'source'       => $s->source_type,
                'metric_key'   => $s->metric_key,
                'metric_value' => (float) $s->metric_value,
                'metric_unit'  => $s->metric_unit ?? '',
                'period'       => $s->period,
            ])->values()->toArray()
            : [];

        $sprintRecords = $employee->relationLoaded('sprintSheets')
            ? $employee->sprintSheets->map(fn($s) => [
                'sprint_name'      => $s->sprint_name,
                'planned_points'   => $s->planned_points,
                'completed_points' => $s->completed_points,
                'tasks_planned'    => $s->tasks_planned,
                'tasks_completed'  => $s->tasks_completed,
                'start_date'       => $s->start_date?->toDateString() ?? '',
                'end_date'         => $s->end_date?->toDateString() ?? '',
            ])->values()->toArray()
            : [];

        return $this->call('/work-pulse/analyze', [
            'employee_name'  => $employee->full_name,
            'designation'    => $employee->designation ?? '',
            'department'     => $employee->department?->name ?? '',
            'tasks'          => $tasks,
            'comm_signals'   => $commSignals,
            'sprint_records' => $sprintRecords,
        ], $employee->organization_id);
    }

    public function generateInterviewQuestions(array $data, ?int $orgId = null): array
    {
        return $this->call('/generate-interview-questions', $data, $orgId);
    }

    public function evaluateInterviewAnswer(array $data, ?int $orgId = null): array
    {
        return $this->call('/evaluate-interview-answer', $data, $orgId);
    }

    public function generateInterviewSummary(array $data, ?int $orgId = null): array
    {
        return $this->call('/generate-interview-summary', $data, $orgId);
    }

    public function healthCheck(): array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/health');
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => 'unreachable', 'error' => $e->getMessage()];
        }
    }

    private function call(string $endpoint, array $data, ?int $orgId = null): array
    {
        // Sanitize payload for logging — PDF text may contain non-UTF-8 bytes
        $loggableData = json_decode(
            json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE),
            true
        ) ?? [];

        try {
            $log = AiProcessingLog::create([
                'organization_id' => $orgId,
                'endpoint' => $endpoint,
                'request_payload' => $loggableData,
                'status' => 'processing',
            ]);
        } catch (\Exception $e) {
            // Logging should never block the actual API call
            $log = null;
        }

        $start = microtime(true);

        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . $endpoint, $data);

            $elapsed = (int)((microtime(true) - $start) * 1000);
            $result = $response->json();

            $log?->update([
                'response_payload' => $result,
                'status' => $response->successful() ? 'completed' : 'failed',
                'error_message' => $response->successful() ? null : 'HTTP ' . $response->status(),
                'processing_time_ms' => $elapsed,
            ]);

            return $result ?? [];
        } catch (\Exception $e) {
            $elapsed = (int)((microtime(true) - $start) * 1000);
            $log?->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processing_time_ms' => $elapsed,
            ]);
            return ['error' => $e->getMessage()];
        }
    }
}
