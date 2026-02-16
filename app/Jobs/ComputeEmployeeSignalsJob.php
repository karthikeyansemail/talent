<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\EmployeeSignal;
use App\Models\SignalSnapshot;
use App\Services\AiServiceClient;
use App\Services\SignalComputer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComputeEmployeeSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Employee $employee,
        private string $period
    ) {}

    public function handle(): void
    {
        $orgId = $this->employee->organization_id;

        try {
            // Step 1: Compute raw signals from local data
            $computer = new SignalComputer();
            $rawSignals = $computer->computeForEmployee($this->employee, $this->period);

            // Step 2: Store raw signals
            foreach ($rawSignals as $signal) {
                EmployeeSignal::updateOrCreate(
                    [
                        'employee_id' => $this->employee->id,
                        'metric_key' => $signal['metric_key'],
                        'period' => $this->period,
                    ],
                    [
                        'organization_id' => $orgId,
                        'source_type' => $signal['source_type'],
                        'metric_value' => $signal['metric_value'],
                        'metric_unit' => $signal['metric_unit'],
                    ]
                );
            }

            // Step 3: Send to AI for meta-signal analysis
            if (!empty($rawSignals)) {
                $aiClient = new AiServiceClient();
                $aiResult = $aiClient->analyzeSignals([
                    'employee_name' => $this->employee->full_name,
                    'period' => $this->period,
                    'signals' => $rawSignals,
                ], $orgId);

                // Step 4: Store AI-computed snapshot
                SignalSnapshot::updateOrCreate(
                    [
                        'employee_id' => $this->employee->id,
                        'period' => $this->period,
                    ],
                    [
                        'organization_id' => $orgId,
                        'consistency_index' => $aiResult['consistency_index'] ?? null,
                        'recovery_signal' => $aiResult['recovery_signal'] ?? null,
                        'workload_pressure' => $aiResult['workload_pressure'] ?? null,
                        'context_switching_index' => $aiResult['context_switching_index'] ?? null,
                        'collaboration_density' => $aiResult['collaboration_density'] ?? null,
                        'raw_signals' => $rawSignals,
                        'ai_analysis' => $aiResult,
                        'ai_summary' => $aiResult['summary'] ?? null,
                    ]
                );
            }

            Log::info("Signal computation complete for employee {$this->employee->id}, period {$this->period}");
        } catch (\Exception $e) {
            Log::error("Signal computation failed for employee {$this->employee->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
