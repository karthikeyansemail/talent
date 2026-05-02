<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\EmployeeSignal;
use App\Models\IntegrationConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pulls per-rep activity from Zoho CRM.
 *
 * Credentials in IntegrationConnection.credentials:
 *   - access_token   (refresh handled separately like Zoho Projects)
 *   - api_domain     e.g. https://www.zohoapis.in or .com
 *
 * Setup: api-console.zoho.[in|com] -> Self Client / Server-based App
 *        Scopes: ZohoCRM.modules.deals.READ, ZohoCRM.modules.activities.READ,
 *                ZohoCRM.users.READ
 */
class SyncZohoCrmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(
        private IntegrationConnection $integrationConnection
    ) {}

    public function handle(): void
    {
        $orgId = $this->integrationConnection->organization_id;
        $creds = $this->integrationConnection->credentials;
        $token = $creds['access_token'] ?? '';
        $base  = rtrim($creds['api_domain'] ?? 'https://www.zohoapis.in', '/');

        if (!$token) {
            Log::warning('Zoho CRM sync: missing access token');
            return;
        }

        $employees = Employee::where('organization_id', $orgId)
            ->whereNotNull('email')
            ->get()
            ->keyBy(fn($e) => strtolower($e->email));

        $lastWeek  = now()->subWeek();
        $period    = $lastWeek->format('Y') . '-W' . str_pad($lastWeek->isoWeek(), 2, '0', STR_PAD_LEFT);
        $weekStart = (clone $lastWeek)->startOfWeek()->format('Y-m-d\TH:i:s');
        $weekEnd   = (clone $lastWeek)->endOfWeek()->format('Y-m-d\TH:i:s');

        $http = Http::withHeaders(['Authorization' => 'Zoho-oauthtoken ' . $token])->timeout(60);

        try {
            // Closed-Won deals — Zoho Deals module with criteria
            $criteriaWon = "(Stage:equals:Closed Won) and (Closing_Date:between:{$weekStart},{$weekEnd})";
            $deals = $http->get("{$base}/crm/v6/Deals/search", ['criteria' => $criteriaWon, 'per_page' => 200])->json('data', []);

            // Open pipeline — all deals not closed
            $openCriteria = "(Stage:not_equal:Closed Won) and (Stage:not_equal:Closed Lost)";
            $openDeals = $http->get("{$base}/crm/v6/Deals/search", ['criteria' => $openCriteria, 'per_page' => 200])->json('data', []);

            // Activities — Calls + Tasks + Events (Meetings)
            $callsCriteria = "(Created_Time:between:{$weekStart},{$weekEnd})";
            $calls    = $http->get("{$base}/crm/v6/Calls/search",  ['criteria' => $callsCriteria, 'per_page' => 200])->json('data', []);
            $tasks    = $http->get("{$base}/crm/v6/Tasks/search",  ['criteria' => $callsCriteria, 'per_page' => 200])->json('data', []);
            $events   = $http->get("{$base}/crm/v6/Events/search", ['criteria' => $callsCriteria, 'per_page' => 200])->json('data', []);

            $stats = [];
            $bump = function (&$stats, $items, $key, $valueField = null) {
                foreach ($items as $item) {
                    $owner = $item['Owner']['email'] ?? '';
                    if (!$owner) continue;
                    $owner = strtolower($owner);
                    if ($valueField) {
                        $stats[$owner][$key] = ($stats[$owner][$key] ?? 0) + (float) ($item[$valueField] ?? 0);
                    } else {
                        $stats[$owner][$key] = ($stats[$owner][$key] ?? 0) + 1;
                    }
                }
            };

            $bump($stats, $deals, 'deals_closed_count');
            $bump($stats, $deals, 'deals_won_value', 'Amount');
            $bump($stats, $openDeals, 'pipeline_value_usd', 'Amount');
            $bump($stats, $calls, 'calls_made_count');
            $bump($stats, $tasks, 'tasks_completed_count');
            $bump($stats, $events, 'meetings_held_count');

            $synced = 0;
            foreach ($stats as $email => $metrics) {
                if (!isset($employees[$email])) continue;
                $emp = $employees[$email];
                foreach ($metrics as $key => $value) {
                    $unit = in_array($key, ['pipeline_value_usd', 'deals_won_value']) ? 'usd' : 'count';
                    EmployeeSignal::updateOrCreate(
                        ['employee_id' => $emp->id, 'source_type' => 'zoho_crm', 'metric_key' => $key, 'period' => $period],
                        ['organization_id' => $orgId, 'metric_value' => $value, 'metric_unit' => $unit]
                    );
                }
                $synced++;
            }

            $this->integrationConnection->update(['last_synced_at' => now()]);
            Log::info("Zoho CRM sync complete: {$synced} reps");
        } catch (\Throwable $e) {
            Log::error('Zoho CRM sync error: ' . $e->getMessage());
            throw $e;
        }
    }
}
