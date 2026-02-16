<?php

namespace App\Services;

use App\Models\ScoringRule;
use App\Models\ScoringRuleVersion;

class ScoringEngine
{
    /**
     * Default signal weights — replicate the original hardcoded scoring.
     * New quality signals start at 0.00 so existing behavior is unchanged.
     */
    public const DEFAULT_RULES = [
        // Core signals (original weights)
        'skill_match_score'  => ['label' => 'Skill Match',          'weight' => 0.3500, 'category' => 'core',        'description' => 'How well candidate skills match job requirements'],
        'experience_score'   => ['label' => 'Experience',           'weight' => 0.2500, 'category' => 'core',        'description' => 'Relevance and depth of professional experience'],
        'relevance_score'    => ['label' => 'Relevance',            'weight' => 0.2500, 'category' => 'core',        'description' => 'Overall background alignment with job description'],
        'authenticity_score' => ['label' => 'Authenticity',         'weight' => 0.1500, 'category' => 'core',        'description' => 'Consistency, specificity, and credibility of claims'],
        // Quality signals (start inactive — opt-in via admin UI or optimization)
        'keyword_density'      => ['label' => 'Keyword Density',      'weight' => 0.0000, 'category' => 'authenticity', 'description' => 'Natural language vs keyword stuffing (100=natural)'],
        'generic_language'     => ['label' => 'Generic Language',     'weight' => 0.0000, 'category' => 'authenticity', 'description' => 'Specific details vs buzzwords (100=specific)'],
        'verifiable_evidence'  => ['label' => 'Verifiable Evidence',  'weight' => 0.0000, 'category' => 'authenticity', 'description' => 'Named employers, dates, metrics, verifiable projects'],
        'career_progression'   => ['label' => 'Career Progression',   'weight' => 0.0000, 'category' => 'authenticity', 'description' => 'Logical career trajectory with increasing responsibility'],
        'quantified_claims'    => ['label' => 'Quantified Claims',    'weight' => 0.0000, 'category' => 'authenticity', 'description' => 'Measurable achievements (percentages, dollar amounts, team sizes)'],
    ];

    /**
     * Compute a weighted score from raw signals using organization-specific rules.
     *
     * @param  array  $signals   Associative array of signal_key => value (0-100)
     * @param  int    $orgId     Organization ID
     * @return array  ['score' => float, 'version' => int, 'weights_used' => array]
     */
    public function computeScore(array $signals, int $orgId): array
    {
        $rules = ScoringRule::where('organization_id', $orgId)
            ->where('is_active', true)
            ->get();

        // If no rules exist yet, seed defaults
        if ($rules->isEmpty()) {
            $this->seedDefaultRules($orgId);
            $rules = ScoringRule::where('organization_id', $orgId)
                ->where('is_active', true)
                ->get();
        }

        $totalWeight = $rules->sum('weight');
        $weightedSum = 0;
        $weightsUsed = [];

        foreach ($rules as $rule) {
            $value = $signals[$rule->signal_key] ?? 50; // default to 50 if missing
            $normalizedWeight = $totalWeight > 0 ? $rule->weight / $totalWeight : 0;
            $weightedSum += $value * $normalizedWeight;
            $weightsUsed[$rule->signal_key] = round($normalizedWeight, 4);
        }

        // Get current version number
        $version = ScoringRuleVersion::where('organization_id', $orgId)
            ->max('version') ?? 1;

        return [
            'score' => round($weightedSum, 2),
            'version' => $version,
            'weights_used' => $weightsUsed,
        ];
    }

    /**
     * Compute score from legacy ai_analysis data (old format without separate signals).
     * Maps the 4 original scores into signals; new signals default to 50.
     */
    public function computeScoreFromLegacy(array $aiAnalysis, int $orgId): array
    {
        $signals = [
            'skill_match_score'  => $aiAnalysis['skill_match_score'] ?? 50,
            'experience_score'   => $aiAnalysis['experience_score'] ?? 50,
            'relevance_score'    => $aiAnalysis['relevance_score'] ?? 50,
            'authenticity_score' => $aiAnalysis['authenticity_score'] ?? 50,
            // New signals not available in old format — use neutral defaults
            'keyword_density'      => 50,
            'generic_language'     => 50,
            'verifiable_evidence'  => 50,
            'career_progression'   => 50,
            'quantified_claims'    => 50,
        ];

        return $this->computeScore($signals, $orgId);
    }

    /**
     * Seed default scoring rules for a new organization.
     */
    public function seedDefaultRules(int $orgId): void
    {
        foreach (self::DEFAULT_RULES as $key => $config) {
            ScoringRule::firstOrCreate(
                ['organization_id' => $orgId, 'signal_key' => $key],
                [
                    'signal_label' => $config['label'],
                    'weight' => $config['weight'],
                    'is_active' => $config['weight'] > 0,
                    'category' => $config['category'],
                    'description' => $config['description'],
                ]
            );
        }

        // Create initial version snapshot
        $weights = ScoringRule::where('organization_id', $orgId)
            ->pluck('weight', 'signal_key')
            ->toArray();

        ScoringRuleVersion::create([
            'organization_id' => $orgId,
            'version' => 1,
            'weights_snapshot' => $weights,
            'trigger' => 'manual',
            'notes' => 'Default rules initialized',
        ]);
    }

    /**
     * Extract the 9 signal keys recognized by the scoring engine.
     */
    public static function signalKeys(): array
    {
        return array_keys(self::DEFAULT_RULES);
    }
}
