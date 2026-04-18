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
        private IntegrationConnection $integrationConnection
    ) {}

    public function handle(): void
    {
        $credentials  = $this->integrationConnection->credentials;
        $tenantId     = $credentials['tenant_id'] ?? '';
        $clientId     = $credentials['client_id'] ?? '';
        $clientSecret = $credentials['client_secret'] ?? '';
        $orgId        = $this->integrationConnection->organization_id;

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
                    'connection_id' => $this->integrationConnection->id,
                    'status' => $tokenResponse->status(),
                ]);
                return;
            }

            $accessToken = $tokenResponse->json('access_token');

            // Step 2: Get Teams activity report (past 7 days)
            // Graph Reports API returns CSV by default (even with Accept: application/json).
            // It often 302-redirects to a blob storage URL, so we must follow redirects.
            $reportResponse = Http::withToken($accessToken)
                ->withOptions(['allow_redirects' => true])
                ->get('https://graph.microsoft.com/v1.0/reports/getTeamsUserActivityUserDetail(period=\'D7\')');

            if (!$reportResponse->successful()) {
                Log::warning('Teams activity report fetch failed', [
                    'connection_id' => $this->integrationConnection->id,
                    'status' => $reportResponse->status(),
                ]);
                return;
            }

            $body = $reportResponse->body();

            // Try JSON first (some tenants/licenses return JSON)
            $jsonData = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($jsonData['value'])) {
                $rows = $this->parseJsonReport($jsonData['value']);
            } else {
                // Parse CSV (standard format from Graph Reports API)
                $rows = $this->parseCsvReport($body);
            }

            Log::info("Teams report fetched: " . count($rows) . " user rows", [
                'connection_id' => $this->integrationConnection->id,
            ]);

            $employees = Employee::where('organization_id', $orgId)
                ->whereNotNull('email')
                ->get()
                ->keyBy(fn($e) => strtolower($e->email));

            $period = now()->format('Y') . '-W' . now()->format('W');
            $synced = 0;

            foreach ($rows as $row) {
                $email = strtolower($row['email'] ?? '');
                if (!$email || !isset($employees[$email])) {
                    continue;
                }

                $employee = $employees[$email];

                $chatMessages    = (int) ($row['private_chat_messages'] ?? 0);
                $channelMessages = (int) ($row['team_chat_messages'] ?? 0);
                $totalMessages   = $chatMessages + $channelMessages;
                $calls           = (int) ($row['calls'] ?? 0);
                $meetings        = (int) ($row['meetings'] ?? 0);

                $metrics = [
                    ['metric_key' => 'messages_sent_count',         'metric_value' => $totalMessages,   'metric_unit' => 'count'],
                    ['metric_key' => 'calls_count',                 'metric_value' => $calls,           'metric_unit' => 'count'],
                    ['metric_key' => 'meetings_attended_count',     'metric_value' => $meetings,        'metric_unit' => 'count'],
                    ['metric_key' => 'channel_messages_count',      'metric_value' => $channelMessages, 'metric_unit' => 'count'],
                    ['metric_key' => 'private_chat_messages_count', 'metric_value' => $chatMessages,    'metric_unit' => 'count'],
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

            $this->integrationConnection->update(['last_synced_at' => now()]);

            Log::info("Teams metrics sync complete: {$synced} employees", [
                'connection_id' => $this->integrationConnection->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Teams metrics sync error: ' . $e->getMessage(), [
                'connection_id' => $this->integrationConnection->id,
            ]);
            throw $e;
        }
    }

    /**
     * Parse JSON format report (some tenants return this).
     */
    private function parseJsonReport(array $values): array
    {
        return array_map(fn($row) => [
            'email'                 => $row['userPrincipalName'] ?? '',
            'private_chat_messages' => $row['privateChatMessageCount'] ?? $row['chatMessageCount'] ?? 0,
            'team_chat_messages'    => $row['teamChatMessageCount'] ?? $row['channelMessageCount'] ?? 0,
            'calls'                 => $row['callCount'] ?? 0,
            'meetings'              => $row['meetingCount'] ?? 0,
        ], $values);
    }

    /**
     * Parse CSV format report (default from Graph Reports API).
     * CSV columns include: User Principal Name, Team Chat Message Count,
     * Private Chat Message Count, Call Count, Meeting Count, etc.
     */
    private function parseCsvReport(string $csv): array
    {
        // Remove BOM if present
        $csv = ltrim($csv, "\xEF\xBB\xBF");

        $lines = array_filter(explode("\n", $csv), fn($l) => trim($l) !== '');
        if (count($lines) < 2) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        // Normalize headers to lowercase for reliable matching
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);

        $rows = [];
        foreach ($lines as $line) {
            $values = str_getcsv($line);
            if (count($values) !== count($headers)) {
                continue;
            }
            $record = array_combine($headers, $values);

            $rows[] = [
                'email'                 => $record['user principal name'] ?? '',
                'private_chat_messages' => $record['private chat message count'] ?? 0,
                'team_chat_messages'    => $record['team chat message count'] ?? 0,
                'calls'                 => $record['call count'] ?? 0,
                'meetings'              => $record['meeting count'] ?? $record['meetings attended count'] ?? 0,
            ];
        }

        return $rows;
    }
}
