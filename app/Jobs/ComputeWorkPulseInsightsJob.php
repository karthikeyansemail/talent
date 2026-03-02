<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\EmployeeAiInsight;
use App\Services\AiServiceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ComputeWorkPulseInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;

    public function __construct(public Employee $employee) {}

    public static function cacheKey(int $employeeId): string
    {
        return "work_pulse_status_{$employeeId}";
    }

    public function handle(AiServiceClient $ai): void
    {
        $key = self::cacheKey($this->employee->id);

        Cache::put($key, ['status' => 'running', 'pct' => 10, 'phase' => 'Loading task data...'], now()->addMinutes(10));

        $this->employee->load(['tasks', 'department', 'signals', 'sprintSheets']);

        if ($this->employee->tasks->isEmpty()) {
            Cache::put($key, ['status' => 'failed', 'pct' => 0, 'phase' => 'No task data to analyze'], now()->addMinutes(5));
            Log::info("ComputeWorkPulseInsightsJob: no tasks for employee {$this->employee->id}, skipping.");
            return;
        }

        Cache::put($key, ['status' => 'running', 'pct' => 40, 'phase' => 'Running AI analysis...'], now()->addMinutes(10));

        $result = $ai->analyzeWorkPulse($this->employee);

        if (isset($result['error']) || empty($result['dimensions'])) {
            Cache::put($key, ['status' => 'failed', 'pct' => 0, 'phase' => 'AI analysis failed'], now()->addMinutes(5));
            Log::error("ComputeWorkPulseInsightsJob: AI call failed for employee {$this->employee->id}", [
                'result' => $result,
            ]);
            return;
        }

        Cache::put($key, ['status' => 'running', 'pct' => 85, 'phase' => 'Saving insights...'], now()->addMinutes(10));

        EmployeeAiInsight::updateOrCreate(
            ['employee_id' => $this->employee->id],
            [
                'organization_id'      => $this->employee->organization_id,
                'analyzed_at'          => now(),
                'management_narrative' => $result['management_narrative'] ?? '',
                'task_summary'         => $result['task_summary'] ?? '',
                'dimensions'           => $result['dimensions'] ?? [],
                'data_context'         => [
                    'total_tasks'  => $this->employee->tasks->count(),
                    'period_range' => ($this->employee->tasks->min('source_created_at') ?? '')
                                     . ' – ' . now()->toDateString(),
                ],
            ]
        );

        Cache::put($key, ['status' => 'completed', 'pct' => 100, 'phase' => 'Analysis complete!', 'completed_at' => now()->toIso8601String()], now()->addMinutes(10));

        Log::info("ComputeWorkPulseInsightsJob: completed for employee {$this->employee->id}");
    }
}
