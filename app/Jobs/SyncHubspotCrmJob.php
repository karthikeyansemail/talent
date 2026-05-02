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
 * Pulls per-rep activity from HubSpot.
 *
 * Credentials in IntegrationConnection.credentials:
 *   - access_token  (Private App access token — simplest auth in HubSpot)
 *
 * Setup: HubSpot -> Settings -> Integrations -> Private Apps -> Create
 *        Scopes: crm.objects.deals.read, crm.objects.contacts.read,
 *                crm.objects.owners.read, sales-email-read,
 *                tickets, e-commerce
 */
class SyncHubspotCrmJob implements ShouldQueue
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
        $token = $this->integrationConnection->credentials['access_token'] ?? '';
        if (!$token) {
            Log::warning('HubSpot CRM sync: missing access token');
            return;
        }

        $employees = Employee::where('organization_id', $orgId)
            ->whereNotNull('email')
            ->get()
            ->keyBy(fn($e) => strtolower($e->email));

        $lastWeek    = now()->subWeek();
        $period      = $lastWeek->format('Y') . '-W' . str_pad($lastWeek->isoWeek(), 2, '0', STR_PAD_LEFT);
        $weekStartMs = (clone $lastWeek)->startOfWeek()->timestamp * 1000;
        $weekEndMs   = (clone $lastWeek)->endOfWeek()->timestamp * 1000;

        $http = Http::withToken($token)->timeout(60);

        try {
            // Fetch owners (HubSpot users) — get emails to map to employees
            $ownersResp = $http->get('https://api.hubapi.com/crm/v3/owners', ['limit' => 500])->json('results', []);
            $ownerEmail = [];
            foreach ($ownersResp as $o) {
                $ownerEmail[$o['id']] = strtolower($o['email'] ?? '');
            }

            // Closed-won deals in window
            $deals = $http->post('https://api.hubapi.com/crm/v3/objects/deals/search', [
                'filterGroups' => [
                    ['filters' => [
                        ['propertyName' => 'dealstage', 'operator' => 'EQ', 'value' => 'closedwon'],
                        ['propertyName' => 'closedate', 'operator' => 'BETWEEN', 'value' => $weekStartMs, 'highValue' => $weekEndMs],
                    ]],
                ],
                'properties' => ['amount', 'dealstage', 'closedate', 'hubspot_owner_id'],
                'limit'      => 100,
            ])->json('results', []);

            // Open pipeline
            $openDeals = $http->post('https://api.hubapi.com/crm/v3/objects/deals/search', [
                'filterGroups' => [
                    ['filters' => [
                        ['propertyName' => 'dealstage', 'operator' => 'NEQ', 'value' => 'closedwon'],
                        ['propertyName' => 'dealstage', 'operator' => 'NEQ', 'value' => 'closedlost'],
                    ]],
                ],
                'properties' => ['amount', 'hubspot_owner_id'],
                'limit'      => 200,
            ])->json('results', []);

            // Engagements (calls + emails + meetings) in window
            $calls = $http->post('https://api.hubapi.com/crm/v3/objects/calls/search', [
                'filterGroups' => [['filters' => [['propertyName' => 'hs_createdate', 'operator' => 'BETWEEN', 'value' => $weekStartMs, 'highValue' => $weekEndMs]]]],
                'properties'   => ['hubspot_owner_id'],
                'limit'        => 200,
            ])->json('results', []);

            $emails = $http->post('https://api.hubapi.com/crm/v3/objects/emails/search', [
                'filterGroups' => [['filters' => [['propertyName' => 'hs_createdate', 'operator' => 'BETWEEN', 'value' => $weekStartMs, 'highValue' => $weekEndMs]]]],
                'properties'   => ['hubspot_owner_id'],
                'limit'        => 200,
            ])->json('results', []);

            $meetings = $http->post('https://api.hubapi.com/crm/v3/objects/meetings/search', [
                'filterGroups' => [['filters' => [['propertyName' => 'hs_createdate', 'operator' => 'BETWEEN', 'value' => $weekStartMs, 'highValue' => $weekEndMs]]]],
                'properties'   => ['hubspot_owner_id'],
                'limit'        => 200,
            ])->json('results', []);

            // Aggregate per owner email
            $stats = [];
            $bumpFn = function (&$stats, $items, $key, $valueField = null) use ($ownerEmail) {
                foreach ($items as $item) {
                    $ownerId = $item['properties']['hubspot_owner_id'] ?? null;
                    $email   = $ownerEmail[$ownerId] ?? '';
                    if (!$email) continue;
                    if ($valueField) {
                        $stats[$email][$key] = ($stats[$email][$key] ?? 0) + (float) ($item['properties'][$valueField] ?? 0);
                    } else {
                        $stats[$email][$key] = ($stats[$email][$key] ?? 0) + 1;
                    }
                }
            };

            $bumpFn($stats, $deals, 'deals_closed_count');
            $bumpFn($stats, $deals, 'deals_won_value', 'amount');
            $bumpFn($stats, $openDeals, 'pipeline_value_usd', 'amount');
            $bumpFn($stats, $calls, 'calls_made_count');
            $bumpFn($stats, $emails, 'emails_sent_count');
            $bumpFn($stats, $meetings, 'meetings_held_count');

            $synced = 0;
            foreach ($stats as $email => $metrics) {
                if (!isset($employees[$email])) continue;
                $emp = $employees[$email];
                foreach ($metrics as $key => $value) {
                    $unit = in_array($key, ['pipeline_value_usd', 'deals_won_value']) ? 'usd' : 'count';
                    EmployeeSignal::updateOrCreate(
                        ['employee_id' => $emp->id, 'source_type' => 'hubspot', 'metric_key' => $key, 'period' => $period],
                        ['organization_id' => $orgId, 'metric_value' => $value, 'metric_unit' => $unit]
                    );
                }
                $synced++;
            }

            $this->integrationConnection->update(['last_synced_at' => now()]);
            Log::info("HubSpot CRM sync complete: {$synced} reps");
        } catch (\Throwable $e) {
            Log::error('HubSpot CRM sync error: ' . $e->getMessage());
            throw $e;
        }
    }
}
