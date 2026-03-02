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

            // Use the previous completed week so we always have full 5-day data.
            // On Monday the current week has 0 messages; last week is always complete.
            $lastWeek  = now()->subWeek();
            $period    = $lastWeek->format('Y') . '-W' . $lastWeek->format('W');
            $weekStart = (clone $lastWeek)->startOfWeek()->timestamp;
            $weekEnd   = (clone $lastWeek)->endOfWeek()->timestamp;

            // Fetch public channels
            $channelsResponse = $http->get('https://slack.com/api/conversations.list', [
                'types'            => 'public_channel',
                'exclude_archived' => true,
                'limit'            => 200,
            ]);

            $channels = $channelsResponse->json('channels') ?? [];

            // Simple keyword-based sentiment word lists
            $positiveWords = ['great', 'thanks', 'thank', 'awesome', 'good', 'nice', 'well done',
                'excited', 'happy', 'love', 'appreciate', 'excellent', 'perfect', 'congrats',
                'amazing', 'fantastic', 'brilliant', 'helpful', 'agree', 'yes', 'sure', 'absolutely'];
            $negativeWords = ['frustrated', 'stuck', 'issue', 'problem', 'error', 'broken', 'fail',
                'wrong', 'confused', 'unclear', 'blocked', 'delay', 'overloaded', 'stressed',
                'bad', 'difficult', 'impossible', 'terrible', 'hate', 'disappointed', 'no'];

            // Keywords that indicate proactive status sharing (employee updates team without being asked)
            $proactiveKeywords = ['update', 'done', 'completed', 'finished', 'shipped', 'deployed',
                'merged', 'submitted', 'pushed', 'released'];

            // Build slackId → email map for employees only (needed for mention crediting)
            $relevantSlackIds = [];
            foreach ($slackUsers as $email => $member) {
                if (isset($employees[$email])) {
                    $relevantSlackIds[$member['id']] = $email;
                }
            }

            // Aggregate per-user stats
            $userStats = []; // slackUserId => [...metrics]

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
                            'messages'           => 0,
                            'days'               => [],
                            'collaborators'      => [],
                            'after_hours'        => 0,
                            'total_length'       => 0,
                            'sentiment_pos'      => 0,
                            'sentiment_neg'      => 0,
                            'channels'           => [],
                            'initiated_messages' => 0,
                            'reply_messages'     => 0,
                            'inbound_mentions'   => 0,
                            'questions_asked'    => 0,
                            'proactive_status'   => 0,
                        ];
                    }

                    $userStats[$userId]['messages']++;
                    $userStats[$userId]['channels'][$channelId] = true;

                    $ts  = (int) ($msg['ts'] ?? 0);
                    $day = date('Y-m-d', $ts);
                    $userStats[$userId]['days'][$day] = true;

                    // Message length tracking
                    $text = $msg['text'] ?? '';
                    $userStats[$userId]['total_length'] += strlen($text);

                    // Keyword-based sentiment
                    $lower = strtolower($text);
                    foreach ($positiveWords as $w) {
                        if (str_contains($lower, $w)) {
                            $userStats[$userId]['sentiment_pos']++;
                        }
                    }
                    foreach ($negativeWords as $w) {
                        if (str_contains($lower, $w)) {
                            $userStats[$userId]['sentiment_neg']++;
                        }
                    }

                    // After-hours: before 9am or after 6pm (UTC)
                    $hour = (int) date('H', $ts);
                    if ($hour < 9 || $hour >= 18) {
                        $userStats[$userId]['after_hours']++;
                    }

                    // Thread initiation: message starts a thread (or is standalone) vs. a reply
                    $isThreadStarter = !isset($msg['thread_ts']) || $msg['thread_ts'] === $msg['ts'];
                    if ($isThreadStarter) {
                        $userStats[$userId]['initiated_messages']++;
                    } else {
                        $userStats[$userId]['reply_messages']++;
                    }

                    // Question-asking: contains '?' = actively seeking information/help
                    if (str_contains($text, '?')) {
                        $userStats[$userId]['questions_asked']++;
                    }

                    // Proactive status updates: employee shares progress without being asked
                    foreach ($proactiveKeywords as $kw) {
                        if (str_contains($lower, $kw)) {
                            $userStats[$userId]['proactive_status']++;
                            break;
                        }
                    }

                    // Inbound mention tracking: credit the mentioned user (not the sender)
                    // This captures how often others tag this person — high inbound + low initiation = chase pattern
                    preg_match_all('/<@([A-Z0-9]+)>/', $text, $mentionMatches);
                    foreach ($mentionMatches[1] as $mentionedSlackId) {
                        if ($mentionedSlackId !== $userId) {
                            if (!isset($userStats[$mentionedSlackId])) {
                                $userStats[$mentionedSlackId] = [
                                    'messages'           => 0,
                                    'days'               => [],
                                    'collaborators'      => [],
                                    'after_hours'        => 0,
                                    'total_length'       => 0,
                                    'sentiment_pos'      => 0,
                                    'sentiment_neg'      => 0,
                                    'channels'           => [],
                                    'initiated_messages' => 0,
                                    'reply_messages'     => 0,
                                    'inbound_mentions'   => 0,
                                    'questions_asked'    => 0,
                                    'proactive_status'   => 0,
                                ];
                            }
                            $userStats[$mentionedSlackId]['inbound_mentions']++;
                        }
                    }

                    // Count unique collaborators in same channel
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

                // Average message length (engagement depth signal)
                $avgMsgLength = $messageCount > 0
                    ? (int) round($stats['total_length'] / $messageCount)
                    : 0;

                // Sentiment score: net positive signals as % of all sentiment signals, scaled -100 to +100
                $sentimentTotal = $stats['sentiment_pos'] + $stats['sentiment_neg'];
                $sentimentScore = $sentimentTotal > 0
                    ? (int) round((($stats['sentiment_pos'] - $stats['sentiment_neg']) / $sentimentTotal) * 100)
                    : 0;

                // Channel diversity: distinct channels posted in
                $channelDiversity = count($stats['channels']);

                $metrics = [
                    ['metric_key' => 'messages_sent_count',           'metric_value' => $messageCount,              'metric_unit' => 'count'],
                    ['metric_key' => 'active_days_count',             'metric_value' => $activeDays,                'metric_unit' => 'days'],
                    ['metric_key' => 'unique_collaborators_count',    'metric_value' => $collaborators,             'metric_unit' => 'count'],
                    ['metric_key' => 'after_hours_message_pct',       'metric_value' => $afterHoursPct,             'metric_unit' => 'percent'],
                    ['metric_key' => 'avg_message_length',            'metric_value' => $avgMsgLength,              'metric_unit' => 'chars'],
                    ['metric_key' => 'message_sentiment_score',       'metric_value' => $sentimentScore,            'metric_unit' => 'score'],
                    ['metric_key' => 'channel_diversity_count',       'metric_value' => $channelDiversity,          'metric_unit' => 'count'],
                    ['metric_key' => 'initiated_conversations_count', 'metric_value' => $stats['initiated_messages'],'metric_unit' => 'count'],
                    ['metric_key' => 'inbound_mentions_count',        'metric_value' => $stats['inbound_mentions'],  'metric_unit' => 'count'],
                    ['metric_key' => 'questions_asked_count',         'metric_value' => $stats['questions_asked'],   'metric_unit' => 'count'],
                    ['metric_key' => 'proactive_status_count',        'metric_value' => $stats['proactive_status'],  'metric_unit' => 'count'],
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
