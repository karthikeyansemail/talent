<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name', 'slug', 'domain', 'logo_path', 'settings', 'llm_config', 'is_active',
        'is_premium', 'premium_expires_at', 'premium_features',
        'subscription_plan', 'subscription_expires_at', 'support_expires_at',
        'stripe_customer_id', 'stripe_subscription_id', 'razorpay_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'llm_config' => 'array',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
            'premium_expires_at' => 'datetime',
            'premium_features' => 'array',
            'subscription_expires_at' => 'datetime',
            'support_expires_at' => 'datetime',
        ];
    }

    /**
     * Plan limits and feature access.
     * free             → HR/ATS only, max 3 jobs, max 50 candidates, no AI, no resource allocation
     * cloud_enterprise → all features, unlimited
     * self_hosted      → all features, unlimited (on-premise license)
     */
    public function canUse(string $feature): bool
    {
        $plan = $this->subscription_plan ?? 'free';

        // Cloud enterprise + self-hosted get everything
        if (in_array($plan, ['cloud_enterprise', 'self_hosted'])) {
            // Check expiry for cloud_enterprise
            if ($plan === 'cloud_enterprise' && $this->subscription_expires_at) {
                return $this->subscription_expires_at->isFuture();
            }
            return true;
        }

        // Free plan restrictions
        return match ($feature) {
            'jobs'                 => true,  // can post jobs (count enforced separately)
            'candidates'           => true,  // can add candidates (count enforced separately)
            'ai_analysis'          => false,
            'resource_allocation'  => false,
            'work_pulse'           => false,
            'signal_intelligence'  => false,
            'bulk_upload'          => false,
            'scoring_rules'        => false,
            'integrations'         => false,
            default                => false,
        };
    }

    public function jobLimit(): ?int
    {
        return match ($this->subscription_plan ?? 'free') {
            'free'             => 3,
            'cloud_enterprise' => null,
            'self_hosted'      => null,
            default            => 3,
        };
    }

    public function candidateLimit(): ?int
    {
        return match ($this->subscription_plan ?? 'free') {
            'free'             => 50,
            'cloud_enterprise' => null,
            'self_hosted'      => null,
            default            => 50,
        };
    }

    public function planLabel(): string
    {
        return match ($this->subscription_plan ?? 'free') {
            'free'             => 'Free',
            'cloud_enterprise' => 'Cloud Enterprise',
            'self_hosted'      => 'Self-Hosted Enterprise',
            default            => 'Free',
        };
    }

    public function users() { return $this->hasMany(User::class); }
    public function departments() { return $this->hasMany(Department::class); }
    public function jobPostings() { return $this->hasMany(JobPosting::class); }
    public function candidates() { return $this->hasMany(Candidate::class); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function projects() { return $this->hasMany(Project::class); }
    public function jiraConnections() { return $this->hasMany(JiraConnection::class); }
    public function zohoProjectsConnections() { return $this->hasMany(ZohoProjectsConnection::class); }
    public function zohoPeopleConnections() { return $this->hasMany(ZohoPeopleConnection::class); }
    public function scoringRules() { return $this->hasMany(ScoringRule::class); }
    public function scoringRuleVersions() { return $this->hasMany(ScoringRuleVersion::class); }
}
