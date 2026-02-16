<?php

namespace App\Console\Commands;

use App\Jobs\OptimizeScoringRulesJob;
use App\Models\JobApplication;
use App\Models\Organization;
use Illuminate\Console\Command;

class OptimizeScoringRules extends Command
{
    protected $signature = 'scoring:optimize {--org= : Optimize for a specific organization ID only}';
    protected $description = 'Run scoring rule optimization for organizations with sufficient feedback data';

    public function handle(): int
    {
        $orgId = $this->option('org');

        if ($orgId) {
            $org = Organization::find($orgId);
            if (!$org) {
                $this->error("Organization {$orgId} not found.");
                return 1;
            }
            $this->optimizeOrg($org);
            return 0;
        }

        // Find all organizations with 20+ feedback-rated applications with ai_signals
        $orgs = Organization::all();
        $dispatched = 0;

        foreach ($orgs as $org) {
            $count = JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $org->id))
                ->whereNotNull('ai_signals')
                ->whereHas('feedback', fn($q) => $q->whereNotNull('rating'))
                ->count();

            if ($count >= 20) {
                $this->optimizeOrg($org);
                $dispatched++;
            } else {
                $this->line("  Skipping {$org->name}: {$count} samples (need 20)");
            }
        }

        $this->info("Dispatched optimization for {$dispatched} organization(s).");
        return 0;
    }

    private function optimizeOrg(Organization $org): void
    {
        $this->info("Dispatching optimization for: {$org->name} (ID: {$org->id})");
        OptimizeScoringRulesJob::dispatch($org->id);
    }
}
