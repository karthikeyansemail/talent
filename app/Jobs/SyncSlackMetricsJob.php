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

class SyncSlackMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private IntegrationConnection $integrationConnection
    ) {}

    public function handle(): void
    {
        $credentials = $this->integrationConnection->credentials;
        $token       = $credentials['access_token'] ?? ($credentials['bot_token'] ?? '');
        $orgId       = $this->integrationConnection->organization_id;

        try {
            $http = Http::withToken($token);

            // Fetch workspace members
            $membersResponse = $http->get('https://slack.com/api/users.list', ['limit' => 500]);

            if (!$membersResponse->successful() || !($membersResponse->json('ok'))) {
                Log::warning('Slack users.list failed', [
                    'connection_id' => $this->integrationConnection->id,
                    'error' => $membersResponse->json('error'),
                ]);
                return;
            }

            $slackUsers = collect($membersResponse->json('members') ?? [])
                ->filter(fn($m) => !($m['is_bot'] ?? false) && !($m['deleted'] ?? false))
                ->keyBy(fn($m) => $m['profile']['email'] ?? '');

            $employees = Employee::where('organization_id', $orgId)
                ->whereNotNull('email')
                ->get()
                ->keyBy('email');

            // ISO week period: YYYY-WW
            $period    = now()->format('Y') . '-W' . now()->format('W');
            $weekStart = now()->startOfWeek()->timestamp;
            $weekEnd   = now()->endOfWeek()->timestamp;

            // Fetch public channels
            $channelsResponse = $http->get('https://slack.com/api/conversations.list', [
                'types'            => 'public_channel',
                'exclude_archived' => true,
                'limit'            => 200,
            ]);

            $channels = $channelsResponse->json('channels') ?? [];

            // Aggregate per-user stats
            $userStats = []; // slackUserId => [messages, days, collaborators, after_hours]

            foreach ($channels as $channel) {
                $channelId = $channel['id'];

                $historyResponse = $http->get('https://slack.com/api/conversations.history', [
                    'channel' => $channelId,
                    'oldest'  => $weekStart,
                    'latest'  => $weekEnd,
                    'limit'   => 1000,
                ]);

                if (!$historyResponse->successful() || !$historyResponse->json('ok')) {
                    continue;
                }

                $messages = $historyResponse->json('messages') ?? [];

                foreach ($messages as $msg) {
                    $userId = $msg['user'] ?? '';
                    if (!$userId) {
                        continue;
                    }

                    if (!isset($userStats[$userId])) {
                        $userStats[$userId] = [
                            'messages'     => 0,
                            'days'         => [],
                            'collaborators'=> [],
                            'after_hours'  => 0,
                        ];
                    }

                    $userStats[$userId]['messages']++;
                    $ts  = (int) ($msg['ts'] ?? 0);
                    $day = date('Y-m-d', $ts);
                    $userStats[$userId]['days'][$day] = true;

                    // After-hours: before 9am or after 6pm (UTC)
                    $hour = (int) date('H', $ts);
                    if ($hour < 9 || $hour >= 18) {
                        $userStats[$userId]['after_hours']++;
                    }

                    // Count unique collaborators in same channel+day
                    foreach ($messages as $other) {
                        $otherId = $other['user'] ?? '';
                        if ($otherId && $otherId !== $userId) {
                            $userStats[$userId]['collaborators'][$otherId] = true;
                        }
                    }
                }
            }

            $synced = 0;
            foreach ($userStats as $slackUserId => $stats) {
                // Find Slack user by id → get email → match employee
                $slackUser = collect($slackUsers)->first(fn($m) => ($m['id'] ?? '') === $slackUserId);
                $email     = $slackUser['profile']['email'] ?? '';
                if (!$email || !isset($employees[$email])) {
                    continue;
                }

                $employee     = $employees[$email];
                $messageCount = $stats['messages'];
                $activeDays   = count($stats['days']);
                $collaborators= count($stats['collaborators']);
                $afterHoursPct = $messageCount > 0
                    ? round(($stats['after_hours'] / $messageCount) * 100, 1)
                    : 0;

                $metrics = [
                    ['metric_key' => 'messages_sent_count',        'metric_value' => $messageCount,    'metric_unit' => 'count'],
                    ['metric_key' => 'active_days_count',          'metric_value' => $activeDays,      'metric_unit' => 'days'],
                    ['metric_key' => 'unique_collaborators_count', 'metric_value' => $collaborators,   'metric_unit' => 'count'],
                    ['metric_key' => 'after_hours_message_pct',    'metric_value' => $afterHoursPct,   'metric_unit' => 'percent'],
                ];

                foreach ($metrics as $metric) {
                    EmployeeSignal::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'source_type' => 'slack',
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

            Log::info("Slack metrics sync complete: {$synced} employees", [
                'connection_id' => $this->integrationConnection->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Slack metrics sync error: ' . $e->getMessage(), [
                'connection_id' => $this->integrationConnection->id,
            ]);
            throw $e;
        }
    }
}
