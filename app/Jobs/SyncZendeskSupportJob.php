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
 * Pulls per-agent activity from Zendesk Support and writes weekly signals.
 *
 * Maps to EmployeeSignal with metric_keys:
 *   tickets_resolved_count, tickets_assigned_count, tickets_open_count,
 *   first_response_time_min, avg_resolution_time_min, csat_score,
 *   reopen_rate_pct
 *
 * Credentials in IntegrationConnection.credentials:
 *   - subdomain      e.g. nalamsupport (so URL becomes nalamsupport.zendesk.com)
 *   - email          API user email
 *   - api_token      Zendesk API token
 *
 * Setup: Zendesk Admin → Apps & Integrations → APIs → Zendesk API
 *        → Settings → Token Access (create token)
 */
class SyncZendeskSupportJob implements ShouldQueue
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
        $email     = $creds['email'] ?? '';
        $token     = $creds['api_token'] ?? '';

        if (!$subdomain || !$email || !$token) {
            Log::warning('Zendesk sync: missing credentials', ['connection_id' => $this->integrationConnection->id]);
            return;
        }

        $base = "https://{$subdomain}.zendesk.com/api/v2";
        $http = Http::withBasicAuth($email . '/token', $token)->timeout(60);

        $employees = Employee::where('organization_id', $orgId)
            ->whereNotNull('email')
            ->get()
            ->keyBy(fn($e) => strtolower($e->email));

        // Use last completed week so we always get full data
        $lastWeek  = now()->subWeek();
        $period    = $lastWeek->format('Y') . '-W' . str_pad($lastWeek->isoWeek(), 2, '0', STR_PAD_LEFT);
        $weekStart = (clone $lastWeek)->startOfWeek()->toDateString();
        $weekEnd   = (clone $lastWeek)->endOfWeek()->toDateString();

        try {
            // 1. Fetch agents (Zendesk users with role: agent or admin)
            $usersResp = $http->get("{$base}/users.json", ['role[]' => ['agent', 'admin']])->json('users', []);
            $userIdToEmail = [];
            foreach ($usersResp as $u) {
                $userIdToEmail[$u['id']] = strtolower($u['email'] ?? '');
            }

            // 2. Tickets resolved this week, grouped by assignee
            // Zendesk search API: status:solved updated:[start TO end]
            $solvedQuery = "type:ticket status:solved updated>{$weekStart} updated<{$weekEnd}";
            $solved = $http->get("{$base}/search.json", ['query' => $solvedQuery, 'per_page' => 100])->json('results', []);

            // 3. Tickets currently open
            $openQuery = "type:ticket status<solved";
            $open = $http->get("{$base}/search.json", ['query' => $openQuery, 'per_page' => 100])->json('results', []);

            // 4. Satisfaction ratings this week (CSAT)
            $satResp = $http->get("{$base}/satisfaction_ratings.json", [
                'start_time' => strtotime($weekStart),
                'end_time'   => strtotime($weekEnd),
            ])->json('satisfaction_ratings', []);

            // Aggregate per assignee
            $stats = [];
            foreach ($solved as $t) {
                $email = $userIdToEmail[$t['assignee_id'] ?? null] ?? '';
                if (!$email) continue;
                $stats[$email]['tickets_resolved_count'] = ($stats[$email]['tickets_resolved_count'] ?? 0) + 1;
            }
            foreach ($open as $t) {
                $email = $userIdToEmail[$t['assignee_id'] ?? null] ?? '';
                if (!$email) continue;
                $stats[$email]['tickets_open_count'] = ($stats[$email]['tickets_open_count'] ?? 0) + 1;
            }

            // CSAT: avg score (Zendesk uses 'good'/'bad') → percent good
            $satByAgent = [];
            foreach ($satResp as $r) {
                $email = $userIdToEmail[$r['assignee_id'] ?? null] ?? '';
                if (!$email) continue;
                $satByAgent[$email]['total'] = ($satByAgent[$email]['total'] ?? 0) + 1;
                if (($r['score'] ?? '') === 'good') {
                    $satByAgent[$email]['good'] = ($satByAgent[$email]['good'] ?? 0) + 1;
                }
            }
            foreach ($satByAgent as $email => $s) {
                if ($s['total'] > 0) {
                    $stats[$email]['csat_score'] = round(($s['good'] ?? 0) / $s['total'] * 100, 1);
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
                        ['employee_id' => $emp->id, 'source_type' => 'zendesk', 'metric_key' => $key, 'period' => $period],
                        ['organization_id' => $orgId, 'metric_value' => $value, 'metric_unit' => $unit]
                    );
                }
                $synced++;
            }

            $this->integrationConnection->update(['last_synced_at' => now()]);
            Log::info("Zendesk sync complete: {$synced} agents", ['connection_id' => $this->integrationConnection->id]);
        } catch (\Throwable $e) {
            Log::error('Zendesk sync error: ' . $e->getMessage(), ['connection_id' => $this->integrationConnection->id]);
            throw $e;
        }
    }
}
