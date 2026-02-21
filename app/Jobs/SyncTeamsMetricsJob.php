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

class SyncTeamsMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private IntegrationConnection $connection
    ) {}

    public function handle(): void
    {
        $credentials  = $this->connection->credentials;
        $tenantId     = $credentials['tenant_id'] ?? '';
        $clientId     = $credentials['client_id'] ?? '';
        $clientSecret = $credentials['client_secret'] ?? '';
        $orgId        = $this->connection->organization_id;

        try {
            // Step 1: Get application access token via client_credentials
            $tokenResponse = Http::asForm()->post(
                "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
                [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecret,
                    'scope'         => 'https://graph.microsoft.com/.default',
                ]
            );

            if (!$tokenResponse->successful()) {
                Log::warning('Teams token fetch failed', [
                    'connection_id' => $this->connection->id,
                    'status' => $tokenResponse->status(),
                ]);
                return;
            }

            $accessToken = $tokenResponse->json('access_token');

            $http = Http::withToken($accessToken)
                ->baseUrl('https://graph.microsoft.com/v1.0');

            // Step 2: Get Teams activity report (past 7 days)
            // Microsoft Graph Reports API: getTeamsUserActivityUserDetail
            $reportResponse = $http->get('/reports/getTeamsUserActivityUserDetail(period=\'D7\')', [
                '$format' => 'application/json',
            ]);

            if (!$reportResponse->successful()) {
                Log::warning('Teams activity report fetch failed', [
                    'connection_id' => $this->connection->id,
                    'status' => $reportResponse->status(),
                ]);
                return;
            }

            $employees = Employee::where('organization_id', $orgId)
                ->whereNotNull('email')
                ->get()
                ->keyBy('email');

            $period  = now()->format('Y') . '-W' . now()->format('W');
            $rows    = $reportResponse->json('value') ?? [];
            $synced  = 0;

            foreach ($rows as $row) {
                $email = $row['userPrincipalName'] ?? '';
                if (!$email || !isset($employees[$email])) {
                    continue;
                }

                $employee = $employees[$email];

                // Microsoft Graph report fields
                $chatMessages   = (int) ($row['chatMessageCount'] ?? 0);
                $channelMessages= (int) ($row['channelMessageCount'] ?? 0);
                $totalMessages  = $chatMessages + $channelMessages;
                $activeDays     = (int) ($row['activeDays'] ?? 0);
                $calls          = (int) ($row['callCount'] ?? 0);
                $meetings       = (int) ($row['meetingCount'] ?? 0);

                $metrics = [
                    ['metric_key' => 'messages_sent_count',        'metric_value' => $totalMessages, 'metric_unit' => 'count'],
                    ['metric_key' => 'active_days_count',          'metric_value' => $activeDays,    'metric_unit' => 'days'],
                    ['metric_key' => 'calls_count',                'metric_value' => $calls,         'metric_unit' => 'count'],
                    ['metric_key' => 'meetings_attended_count',    'metric_value' => $meetings,      'metric_unit' => 'count'],
                    ['metric_key' => 'channel_messages_count',     'metric_value' => $channelMessages, 'metric_unit' => 'count'],
                    ['metric_key' => 'private_chat_messages_count','metric_value' => $chatMessages,  'metric_unit' => 'count'],
                ];

                foreach ($metrics as $metric) {
                    EmployeeSignal::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'source_type' => 'teams',
                            'metric_key'  => $metric['metric_key'],
                            'period'      => $period,
                        ],
                        [
                            'organization_id' => $orgId,
                            'metric_value'    => $metric['metric_value'],
                            'metric_unit'     => $metric['metric_unit'],
                        ]
                    );
                }

                $synced++;
            }

            $this->connection->update(['last_synced_at' => now()]);

            Log::info("Teams metrics sync complete: {$synced} employees", [
                'connection_id' => $this->connection->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Teams metrics sync error: ' . $e->getMessage(), [
                'connection_id' => $this->connection->id,
            ]);
            throw $e;
        }
    }
}
