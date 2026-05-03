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
 * Pulls per-agent activity from Freshdesk Support and writes weekly signals.
 *
 * Credentials in IntegrationConnection.credentials:
 *   - subdomain  e.g. nalamsupport (URL: nalamsupport.freshdesk.com)
 *   - api_key    Freshdesk API key (acts as password with 'X' as username)
 *
 * Setup: Freshdesk → Profile (top right) → API Key (copy)
 * Permissions needed: agent role with ticket and contact view access.
 */
class SyncFreshdeskSupportJob implements ShouldQueue
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

        $subdomain = $creds['subdomain'] ?? '';
        $apiKey    = $creds['api_key'] ?? '';

        if (!$subdomain || !$apiKey) {
            Log::warning('Freshdesk sync: missing credentials', ['connection_id' => $this->integrationConnection->id]);
            return;
        }

        $base = "https://{$subdomain}.freshdesk.com/api/v2";
        $http = Http::withBasicAuth($apiKey, 'X')->timeout(60);

        $employees = Employee::where('organization_id', $orgId)
            ->whereNotNull('email')
            ->get()
            ->keyBy(fn($e) => strtolower($e->email));

        $lastWeek  = now()->subWeek();
        $period    = $lastWeek->format('Y') . '-W' . str_pad($lastWeek->isoWeek(), 2, '0', STR_PAD_LEFT);
        $weekStart = (clone $lastWeek)->startOfWeek()->format('Y-m-d\TH:i:s\Z');
        $weekEnd   = (clone $lastWeek)->endOfWeek()->format('Y-m-d\TH:i:s\Z');

        try {
            // 1. Fetch agents
            $agents = $http->get("{$base}/agents", ['per_page' => 100])->json() ?: [];
            $agentEmail = [];
            foreach ($agents as $a) {
                $agentEmail[$a['id']] = strtolower($a['contact']['email'] ?? '');
            }

            // 2. Tickets resolved this week (status 4 = resolved, 5 = closed)
            // Freshdesk filter API: GET /tickets?updated_since=...
            // For per-agent resolved counts, we list tickets and group manually.
            $resolved = $http->get("{$base}/tickets", [
                'updated_since' => $weekStart,
                'per_page'      => 100,
                'order_by'      => 'updated_at',
                'order_type'    => 'desc',
            ])->json() ?: [];

            $stats = [];
            foreach ($resolved as $t) {
                $status     = $t['status'] ?? 0;
                $assigneeId = $t['responder_id'] ?? null;
                $email      = $agentEmail[$assigneeId] ?? '';
                if (!$email) continue;

                if (in_array($status, [4, 5])) { // resolved or closed
                    $stats[$email]['tickets_resolved_count'] = ($stats[$email]['tickets_resolved_count'] ?? 0) + 1;
                } elseif (in_array($status, [2, 3])) { // open or pending
                    $stats[$email]['tickets_open_count'] = ($stats[$email]['tickets_open_count'] ?? 0) + 1;
                }
            }

            // 3. CSAT (Customer Satisfaction Survey results)
            // Endpoint: /surveys/satisfaction_ratings — paginated
            $satResp = $http->get("{$base}/surveys/satisfaction_ratings", [
                'created_since' => $weekStart,
                'per_page'      => 100,
            ])->json() ?: [];

            $satByAgent = [];
            foreach ($satResp as $s) {
                $email = $agentEmail[$s['agent_id'] ?? null] ?? '';
                if (!$email) continue;
                // Freshdesk uses 1-7 scale; treat 4+ as "happy"
                $rating = $s['ratings']['default_question'] ?? 0;
                $satByAgent[$email]['total'] = ($satByAgent[$email]['total'] ?? 0) + 1;
                if ($rating >= 4) {
                    $satByAgent[$email]['happy'] = ($satByAgent[$email]['happy'] ?? 0) + 1;
                }
            }
            foreach ($satByAgent as $email => $s) {
                if ($s['total'] > 0) {
                    $stats[$email]['csat_score'] = round(($s['happy'] ?? 0) / $s['total'] * 100, 1);
                }
            }

            // Write to EmployeeSignal
            $synced = 0;
            foreach ($stats as $email => $metrics) {
                if (!isset($employees[$email])) continue;
                $emp = $employees[$email];
                foreach ($metrics as $key => $value) {
                    $unit = match($key) {
                        'csat_score', 'reopen_rate_pct' => 'percent',
                        'first_response_time_min', 'avg_resolution_time_min' => 'minutes',
                        default                          => 'count',
                    };
                    EmployeeSignal::updateOrCreate(
                        ['employee_id' => $emp->id, 'source_type' => 'freshdesk', 'metric_key' => $key, 'period' => $period],
                        ['organization_id' => $orgId, 'metric_value' => $value, 'metric_unit' => $unit]
                    );
                }
                $synced++;
            }

            $this->integrationConnection->update(['last_synced_at' => now()]);
            Log::info("Freshdesk sync complete: {$synced} agents", ['connection_id' => $this->integrationConnection->id]);
        } catch (\Throwable $e) {
            Log::error('Freshdesk sync error: ' . $e->getMessage(), ['connection_id' => $this->integrationConnection->id]);
            throw $e;
        }
    }
}
