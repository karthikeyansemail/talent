<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\OptimizeScoringRulesJob;
use App\Models\ScoringOptimizationRun;
use App\Models\ScoringRule;
use App\Models\ScoringRuleVersion;
use App\Models\InterviewFeedback;
use App\Models\JobApplication;
use App\Services\ScoringEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScoringRulesController extends Controller
{
    public function index()
    {
        $org = Auth::user()->currentOrganization();
        $engine = new ScoringEngine();

        // Lazy-seed defaults if no rules exist
        $rules = ScoringRule::where('organization_id', $org->id)->get();
        if ($rules->isEmpty()) {
            $engine->seedDefaultRules($org->id);
            $rules = ScoringRule::where('organization_id', $org->id)->get();
        }

        $coreRules = $rules->where('category', 'core')->sortByDesc('weight');
        $authenticityRules = $rules->where('category', 'authenticity')->sortByDesc('weight');

        // Version history
        $versions = ScoringRuleVersion::where('organization_id', $org->id)
            ->with('user')
            ->orderByDesc('version')
            ->limit(20)
            ->get();

        // Optimization run history
        $optimizationRuns = ScoringOptimizationRun::where('organization_id', $org->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Compute prediction accuracy metrics
        $accuracy = $this->computeAccuracyMetrics($org->id);

        $currentVersion = $versions->first()?->version ?? 1;

        return view('settings.scoring-rules', compact(
            'coreRules', 'authenticityRules', 'versions',
            'optimizationRuns', 'accuracy', 'currentVersion'
        ));
    }

    public function update(Request $request)
    {
        $org = Auth::user()->currentOrganization();

        $validated = $request->validate([
            'weights' => 'required|array',
            'weights.*' => 'required|numeric|min:0|max:1',
        ]);

        // Normalize weights to sum to 1.0
        $totalWeight = array_sum($validated['weights']);
        $normalizedWeights = [];
        foreach ($validated['weights'] as $key => $weight) {
            $normalizedWeights[$key] = $totalWeight > 0
                ? round($weight / $totalWeight, 4)
                : 0;
        }

        // Update each rule
        foreach ($normalizedWeights as $signalKey => $weight) {
            ScoringRule::where('organization_id', $org->id)
                ->where('signal_key', $signalKey)
                ->update(['weight' => $weight]);
        }

        // Create version snapshot
        $nextVersion = (ScoringRuleVersion::where('organization_id', $org->id)->max('version') ?? 0) + 1;
        ScoringRuleVersion::create([
            'organization_id' => $org->id,
            'version' => $nextVersion,
            'weights_snapshot' => $normalizedWeights,
            'trigger' => 'manual',
            'triggered_by' => Auth::id(),
            'notes' => 'Manual weight adjustment',
        ]);

        return back()->with('success', 'Scoring weights updated and saved as version ' . $nextVersion . '.');
    }

    public function toggleSignal(ScoringRule $rule)
    {
        $org = Auth::user()->currentOrganization();
        if ($rule->organization_id !== $org->id) {
            abort(403);
        }

        $rule->update(['is_active' => !$rule->is_active]);

        return back()->with('success', $rule->signal_label . ' ' . ($rule->is_active ? 'enabled' : 'disabled') . '.');
    }

    public function triggerOptimization()
    {
        $org = Auth::user()->currentOrganization();

        // Check minimum sample size
        $sampleCount = JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $org->id))
            ->whereNotNull('ai_signals')
            ->whereHas('feedback', fn($q) => $q->whereNotNull('rating'))
            ->count();

        if ($sampleCount < 20) {
            return back()->with('error', "Need at least 20 applications with AI signals and interview ratings to optimize (currently {$sampleCount}).");
        }

        OptimizeScoringRulesJob::dispatch($org->id);
        return back()->with('success', 'Optimization job queued. Results will appear shortly.');
    }

    public function rollback(ScoringRuleVersion $version)
    {
        $org = Auth::user()->currentOrganization();
        if ($version->organization_id !== $org->id) {
            abort(403);
        }

        $snapshot = $version->weights_snapshot;
        if (!$snapshot || !is_array($snapshot)) {
            return back()->with('error', 'Invalid version snapshot.');
        }

        // Restore weights from snapshot
        foreach ($snapshot as $signalKey => $weight) {
            ScoringRule::where('organization_id', $org->id)
                ->where('signal_key', $signalKey)
                ->update(['weight' => $weight, 'is_active' => $weight > 0]);
        }

        // Create new version recording the rollback
        $nextVersion = (ScoringRuleVersion::where('organization_id', $org->id)->max('version') ?? 0) + 1;
        ScoringRuleVersion::create([
            'organization_id' => $org->id,
            'version' => $nextVersion,
            'weights_snapshot' => $snapshot,
            'trigger' => 'manual',
            'triggered_by' => Auth::id(),
            'notes' => 'Rolled back to version ' . $version->version,
        ]);

        return back()->with('success', 'Weights rolled back to version ' . $version->version . ' (saved as version ' . $nextVersion . ').');
    }

    private function computeAccuracyMetrics(int $orgId): array
    {
        // Get applications with both signals and feedback ratings
        $applications = JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $orgId))
            ->whereNotNull('ai_signals')
            ->whereNotNull('ai_score')
            ->with(['feedback' => fn($q) => $q->whereNotNull('rating')])
            ->get()
            ->filter(fn($app) => $app->feedback->isNotEmpty());

        $sampleSize = $applications->count();

        if ($sampleSize < 5) {
            return [
                'sample_size' => $sampleSize,
                'correlation' => null,
                'mae' => null,
                'enough_data' => false,
            ];
        }

        $scores = [];
        $ratings = [];
        foreach ($applications as $app) {
            $scores[] = (float) $app->ai_score;
            // Normalize average rating to 0-100 scale
            $avgRating = $app->feedback->avg('rating');
            $ratings[] = ($avgRating - 1) / 4 * 100;
        }

        $correlation = $this->pearsonCorrelation($scores, $ratings);
        $mae = $this->meanAbsoluteError($scores, $ratings);

        return [
            'sample_size' => $sampleSize,
            'correlation' => $correlation !== null ? round($correlation, 4) : null,
            'mae' => $mae !== null ? round($mae, 2) : null,
            'enough_data' => $sampleSize >= 20,
        ];
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
}
