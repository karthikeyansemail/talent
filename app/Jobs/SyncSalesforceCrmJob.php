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
 * Pulls per-rep activity from Salesforce and writes weekly signals.
 *
 * Maps to EmployeeSignal with metric_keys:
 *   deals_closed_count, pipeline_value_usd, calls_made_count,
 *   emails_sent_count, meetings_held_count, lead_response_time_hrs,
 *   active_days_count
 *
 * Credentials in IntegrationConnection.credentials:
 *   - instance_url    e.g. https://nalamsales.my.salesforce.com
 *   - access_token    OAuth bearer token
 *   - refresh_token   for token refresh
 *   - client_id, client_secret
 *
 * Setup: Salesforce -> Setup -> Apps -> App Manager -> New Connected App
 *        with OAuth scopes: api, refresh_token, offline_access
 */
class SyncSalesforceCrmJob implements ShouldQueue
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

        $instanceUrl = rtrim($creds['instance_url'] ?? '', '/');
        $token       = $creds['access_token'] ?? '';
        if (!$instanceUrl || !$token) {
            Log::warning('Salesforce CRM sync: missing credentials', ['connection_id' => $this->integrationConnection->id]);
            return;
        }

        $employees = Employee::where('organization_id', $orgId)
            ->whereNotNull('email')
            ->get()
            ->keyBy(fn($e) => strtolower($e->email));

        // Use last completed week so we always get full data
        $lastWeek  = now()->subWeek();
        $period    = $lastWeek->format('Y') . '-W' . str_pad($lastWeek->isoWeek(), 2, '0', STR_PAD_LEFT);
        $weekStart = (clone $lastWeek)->startOfWeek()->toDateString();
        $weekEnd   = (clone $lastWeek)->endOfWeek()->toDateString();

        $http = Http::withToken($token)->timeout(60);

        try {
            // 1. Closed deals this week — Opportunity grouped by Owner
            $deals = $http->get("{$instanceUrl}/services/data/v59.0/query", [
                'q' => "SELECT Owner.Email, COUNT(Id) cnt, SUM(Amount) total " .
                       "FROM Opportunity WHERE IsClosed=true AND IsWon=true " .
                       "AND CloseDate >= {$weekStart} AND CloseDate <= {$weekEnd} " .
                       "GROUP BY Owner.Email",
            ])->json('records', []);

            // 2. Pipeline (open deals) per rep
            $pipeline = $http->get("{$instanceUrl}/services/data/v59.0/query", [
                'q' => "SELECT Owner.Email, SUM(Amount) total FROM Opportunity " .
                       "WHERE IsClosed=false GROUP BY Owner.Email",
            ])->json('records', []);

            // 3. Activities (calls, emails, meetings) — Task object
            $activities = $http->get("{$instanceUrl}/services/data/v59.0/query", [
                'q' => "SELECT Owner.Email, TaskSubtype, COUNT(Id) cnt FROM Task " .
                       "WHERE CreatedDate >= {$weekStart}T00:00:00Z AND CreatedDate <= {$weekEnd}T23:59:59Z " .
                       "GROUP BY Owner.Email, TaskSubtype",
            ])->json('records', []);

            // Aggregate per email
            $stats = [];
            foreach ($deals as $row) {
                $email = strtolower($row['Email'] ?? '');
                if (!$email) continue;
                $stats[$email]['deals_closed_count'] = (int) ($row['cnt'] ?? 0);
                $stats[$email]['deals_won_value']    = (float) ($row['total'] ?? 0);
            }
            foreach ($pipeline as $row) {
                $email = strtolower($row['Email'] ?? '');
                if (!$email) continue;
                $stats[$email]['pipeline_value_usd'] = (float) ($row['total'] ?? 0);
            }
            foreach ($activities as $row) {
                $email = strtolower($row['Email'] ?? '');
                if (!$email) continue;
                $sub   = strtolower($row['TaskSubtype'] ?? 'task');
                $cnt   = (int) ($row['cnt'] ?? 0);
                $key   = match($sub) {
                    'call'    => 'calls_made_count',
                    'email'   => 'emails_sent_count',
                    'meeting' => 'meetings_held_count',
                    default   => null,
                };
                if ($key) {
                    $stats[$email][$key] = ($stats[$email][$key] ?? 0) + $cnt;
                }
            }

            // Write to EmployeeSignal
            $synced = 0;
            foreach ($stats as $email => $metrics) {
                if (!isset($employees[$email])) continue;
                $emp = $employees[$email];
                foreach ($metrics as $key => $value) {
                    $unit = match($key) {
                        'pipeline_value_usd', 'deals_won_value' => 'usd',
                        default                                 => 'count',
                    };
                    EmployeeSignal::updateOrCreate(
                        ['employee_id' => $emp->id, 'source_type' => 'salesforce', 'metric_key' => $key, 'period' => $period],
                        ['organization_id' => $orgId, 'metric_value' => $value, 'metric_unit' => $unit]
                    );
                }
                $synced++;
            }

            $this->integrationConnection->update(['last_synced_at' => now()]);
            Log::info("Salesforce CRM sync complete: {$synced} reps", ['connection_id' => $this->integrationConnection->id]);
        } catch (\Throwable $e) {
            Log::error('Salesforce CRM sync error: ' . $e->getMessage(), ['connection_id' => $this->integrationConnection->id]);
            throw $e;
        }
    }
}
