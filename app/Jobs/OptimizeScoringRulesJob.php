<?php

namespace App\Jobs;

use App\Models\JobApplication;
use App\Models\ScoringOptimizationRun;
use App\Models\ScoringRule;
use App\Models\ScoringRuleVersion;
use App\Services\ScoringEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OptimizeScoringRulesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    private const LEARNING_RATE = 0.15;
    private const MIN_SAMPLES = 20;

    public function __construct(public int $organizationId) {}

    public function handle(): void
    {
        $orgId = $this->organizationId;
        $currentVersion = ScoringRuleVersion::where('organization_id', $orgId)->max('version') ?? 1;

        try {
            // 1. Gather data: applications with ai_signals AND at least one rated feedback
            $applications = JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $orgId))
                ->whereNotNull('ai_signals')
                ->with(['feedback' => fn($q) => $q->whereNotNull('rating')])
                ->get()
                ->filter(fn($app) => $app->feedback->isNotEmpty());

            if ($applications->count() < self::MIN_SAMPLES) {
                ScoringOptimizationRun::create([
                    'organization_id' => $orgId,
                    'version_before' => $currentVersion,
                    'applications_analyzed' => $applications->count(),
                    'status' => 'skipped',
                    'error_message' => 'Insufficient samples: ' . $applications->count() . ' (need ' . self::MIN_SAMPLES . ')',
                ]);
                return;
            }

            // 2. Build parallel arrays of signals and ground truth
            $signalKeys = ScoringEngine::signalKeys();
            $signalData = []; // [signalKey => [values...]]
            $groundTruth = []; // normalized rating 0-100

            foreach ($signalKeys as $key) {
                $signalData[$key] = [];
            }

            foreach ($applications as $app) {
                $signals = $app->ai_signals;
                $avgRating = $app->feedback->avg('rating');
                $normalizedRating = ($avgRating - 1) / 4 * 100;
                $groundTruth[] = $normalizedRating;

                foreach ($signalKeys as $key) {
                    $signalData[$key][] = (float) ($signals[$key] ?? 50);
                }
            }

            // 3. Load current rules/weights
            $rules = ScoringRule::where('organization_id', $orgId)->get()->keyBy('signal_key');
            $currentWeights = [];
            foreach ($signalKeys as $key) {
                $rule = $rules->get($key);
                $currentWeights[$key] = $rule ? (float) $rule->weight : 0;
            }

            // Normalize current weights
            $totalCurrent = array_sum($currentWeights);
            if ($totalCurrent > 0) {
                foreach ($currentWeights as $key => $w) {
                    $currentWeights[$key] = $w / $totalCurrent;
                }
            }

            // 4. Compute current scores and accuracy
            $currentScores = [];
            foreach ($applications as $i => $app) {
                $score = 0;
                foreach ($signalKeys as $key) {
                    $score += ($signalData[$key][$i] ?? 50) * ($currentWeights[$key] ?? 0);
                }
                $currentScores[] = $score;
            }
            $correlationBefore = $this->pearsonCorrelation($currentScores, $groundTruth);
            $maeBefore = $this->meanAbsoluteError($currentScores, $groundTruth);

            // 5. Per-signal correlation with ground truth
            $signalCorrelations = [];
            foreach ($signalKeys as $key) {
                $r = $this->pearsonCorrelation($signalData[$key], $groundTruth);
                $signalCorrelations[$key] = $r ?? 0;
            }

            // 6. Compute ideal weights from positive correlations
            $positiveCorrelations = array_map(fn($r) => max(0, $r), $signalCorrelations);
            $totalPositive = array_sum($positiveCorrelations);

            if ($totalPositive <= 0) {
                ScoringOptimizationRun::create([
                    'organization_id' => $orgId,
                    'version_before' => $currentVersion,
                    'applications_analyzed' => $applications->count(),
                    'correlation_before' => $correlationBefore,
                    'mae_before' => $maeBefore,
                    'status' => 'skipped',
                    'error_message' => 'No signals have positive correlation with feedback ratings.',
                ]);
                return;
            }

            $idealWeights = [];
            foreach ($signalKeys as $key) {
                $idealWeights[$key] = $positiveCorrelations[$key] / $totalPositive;
            }

            // 7. Adjust weights conservatively
            $newWeights = [];
            foreach ($signalKeys as $key) {
                $newWeights[$key] = $currentWeights[$key] + self::LEARNING_RATE * ($idealWeights[$key] - $currentWeights[$key]);
            }

            // Normalize new weights to sum to 1.0
            $totalNew = array_sum($newWeights);
            if ($totalNew > 0) {
                foreach ($newWeights as $key => $w) {
                    $newWeights[$key] = round($w / $totalNew, 4);
                }
            }

            // 8. Validate: recompute scores with new weights and check improvement
            $newScores = [];
            foreach ($applications as $i => $app) {
                $score = 0;
                foreach ($signalKeys as $key) {
                    $score += ($signalData[$key][$i] ?? 50) * $newWeights[$key];
                }
                $newScores[] = $score;
            }
            $correlationAfter = $this->pearsonCorrelation($newScores, $groundTruth);
            $maeAfter = $this->meanAbsoluteError($newScores, $groundTruth);

            // Only apply if MAE improved (or correlation improved and MAE didn't worsen significantly)
            if ($maeAfter > $maeBefore + 1) {
                ScoringOptimizationRun::create([
                    'organization_id' => $orgId,
                    'version_before' => $currentVersion,
                    'applications_analyzed' => $applications->count(),
                    'correlation_before' => $correlationBefore,
                    'correlation_after' => $correlationAfter,
                    'mae_before' => $maeBefore,
                    'mae_after' => $maeAfter,
                    'weight_deltas' => $this->computeDeltas($currentWeights, $newWeights),
                    'status' => 'skipped',
                    'error_message' => 'New weights did not improve MAE (before: ' . round($maeBefore, 2) . ', after: ' . round($maeAfter, 2) . ')',
                ]);
                return;
            }

            // 9. Apply new weights
            foreach ($newWeights as $key => $weight) {
                ScoringRule::where('organization_id', $orgId)
                    ->where('signal_key', $key)
                    ->update([
                        'weight' => $weight,
                        'is_active' => $weight > 0.001,
                    ]);
            }

            // Create version snapshot
            $nextVersion = $currentVersion + 1;
            ScoringRuleVersion::create([
                'organization_id' => $orgId,
                'version' => $nextVersion,
                'weights_snapshot' => $newWeights,
                'trigger' => 'auto_optimization',
                'metrics_at_snapshot' => [
                    'correlation' => $correlationAfter,
                    'mae' => $maeAfter,
                    'sample_size' => $applications->count(),
                    'signal_correlations' => $signalCorrelations,
                ],
                'notes' => sprintf(
                    'Auto-optimization: MAE %.1f → %.1f, Correlation %.3f → %.3f (%d samples)',
                    $maeBefore, $maeAfter, $correlationBefore ?? 0, $correlationAfter ?? 0, $applications->count()
                ),
            ]);

            ScoringOptimizationRun::create([
                'organization_id' => $orgId,
                'version_before' => $currentVersion,
                'version_after' => $nextVersion,
                'applications_analyzed' => $applications->count(),
                'correlation_before' => $correlationBefore,
                'correlation_after' => $correlationAfter,
                'mae_before' => $maeBefore,
                'mae_after' => $maeAfter,
                'weight_deltas' => $this->computeDeltas($currentWeights, $newWeights),
                'status' => 'completed',
            ]);

            Log::info("Scoring optimization completed for org {$orgId}: v{$currentVersion} → v{$nextVersion}");

        } catch (\Exception $e) {
            ScoringOptimizationRun::create([
                'organization_id' => $orgId,
                'version_before' => $currentVersion,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error("Scoring optimization failed for org {$orgId}: {$e->getMessage()}");
        }
    }

    private function pearsonCorrelation(array $x, array $y): ?float
    {
        $n = count($x);
        if ($n < 2) return null;

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }

        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));
        if ($denominator == 0) return null;

        return ($n * $sumXY - $sumX * $sumY) / $denominator;
    }

    private function meanAbsoluteError(array $predicted, array $actual): ?float
    {
        $n = count($predicted);
        if ($n === 0) return null;

        $sum = 0;
        for ($i = 0; $i < $n; $i++) {
            $sum += abs($predicted[$i] - $actual[$i]);
        }
        return $sum / $n;
    }

    private function computeDeltas(array $oldWeights, array $newWeights): array
    {
        $deltas = [];
        foreach ($newWeights as $key => $newW) {
            $oldW = $oldWeights[$key] ?? 0;
            $deltas[$key] = round($newW - $oldW, 4);
        }
        return $deltas;
    }
}
