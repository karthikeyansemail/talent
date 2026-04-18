<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeSignal;
use App\Models\EmployeeTask;
use App\Models\SignalSnapshot;
use App\Models\IntegrationConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Weekly demo data refresher for all 3 demo organizations.
 * Keeps demo environments looking fresh with current-week dates.
 *
 * Usage:
 *   php artisan demo:refresh              — refresh all 3 orgs
 *   php artisan demo:refresh --org=4      — refresh only Nalam Tech
 *   php artisan demo:refresh --weeks=2    — generate 2 weeks of data
 *   php artisan demo:refresh --with-slack — also post fresh Slack messages
 *   php artisan demo:refresh --with-teams — also post fresh Teams messages
 */
class RefreshDemoData extends Command
{
    protected $signature = 'demo:refresh
                            {--org= : Specific org ID (3,4,5). Default: all}
                            {--weeks=1 : Number of weeks to generate}
                            {--with-slack : Post fresh messages to Slack channels}
                            {--with-teams : Post fresh messages to Teams channels}';

    protected $description = 'Refresh demo data with current-week dates for Nalam Systems, Tech, and Labs';

    // ── Employee profiles per org ──────────────────────────────────
    // Each employee has a "personality" that determines their signal ranges
    // and task patterns. This creates a realistic, balanced team.

    private array $profiles = [
        // ── Nalam Systems (org 3) — Jira + Slack ──────────────────
        3 => [
            'source_type' => 'jira',
            'comm_source' => 'slack',
            'employees' => [
                'Rahul Kumar' => [
                    'role' => 'lead', 'trend' => 'stable',
                    'signals' => ['msgs' => [48,55], 'active' => [5,5], 'collabs' => [14,18], 'after' => [7,11], 'calls' => [7,10], 'mtgs' => [12,16]],
                    'tasks' => ['done_rate' => [0.75,0.85], 'total' => [6,8], 'sp_range' => [2,8]],
                    'snapshot' => ['ci' => [83,88], 'rs' => [70,78], 'wp' => [48,58], 'csi' => [30,38], 'cd' => [82,90]],
                ],
                'David Kim' => [
                    'role' => 'developer', 'trend' => 'growing',
                    'signals' => ['msgs' => [35,45], 'active' => [5,5], 'collabs' => [10,14], 'after' => [5,9], 'calls' => [3,6], 'mtgs' => [8,12]],
                    'tasks' => ['done_rate' => [0.70,0.80], 'total' => [5,7], 'sp_range' => [2,5]],
                    'snapshot' => ['ci' => [78,85], 'rs' => [68,75], 'wp' => [40,52], 'csi' => [25,35], 'cd' => [70,80]],
                ],
                'Aman Verma' => [
                    'role' => 'developer', 'trend' => 'stable',
                    'signals' => ['msgs' => [30,40], 'active' => [5,5], 'collabs' => [9,13], 'after' => [3,7], 'calls' => [2,5], 'mtgs' => [7,10]],
                    'tasks' => ['done_rate' => [0.80,0.90], 'total' => [5,7], 'sp_range' => [2,8]],
                    'snapshot' => ['ci' => [85,92], 'rs' => [78,85], 'wp' => [38,48], 'csi' => [18,25], 'cd' => [58,68]],
                ],
                'Sara Lim' => [
                    'role' => 'designer', 'trend' => 'declining',
                    'signals' => ['msgs' => [20,30], 'active' => [3,4], 'collabs' => [5,8], 'after' => [1,3], 'calls' => [1,3], 'mtgs' => [4,7]],
                    'tasks' => ['done_rate' => [0.30,0.50], 'total' => [4,6], 'sp_range' => [1,5]],
                    'snapshot' => ['ci' => [52,62], 'rs' => [38,48], 'wp' => [25,35], 'csi' => [22,30], 'cd' => [40,50]],
                ],
                'Anita Patel' => [
                    'role' => 'hr', 'trend' => 'stable',
                    'signals' => ['msgs' => [25,35], 'active' => [5,5], 'collabs' => [12,16], 'after' => [4,8], 'calls' => [4,7], 'mtgs' => [10,14]],
                    'tasks' => ['done_rate' => [0.70,0.80], 'total' => [4,6], 'sp_range' => [1,3]],
                    'snapshot' => ['ci' => [76,82], 'rs' => [65,72], 'wp' => [45,55], 'csi' => [38,45], 'cd' => [85,92]],
                ],
            ],
        ],

        // ── Nalam Tech (org 4) — DevOps Boards + Teams ────────────
        4 => [
            'source_type' => 'devops_boards',
            'comm_source' => 'teams',
            'employees' => [
                'Kevin Lee' => [
                    'role' => 'lead', 'trend' => 'stable',
                    'signals' => ['msgs' => [45,55], 'active' => [5,5], 'collabs' => [14,17], 'after' => [6,10], 'calls' => [7,10], 'mtgs' => [11,15]],
                    'tasks' => ['done_rate' => [0.72,0.82], 'total' => [6,8], 'sp_range' => [1,8]],
                    'snapshot' => ['ci' => [84,88], 'rs' => [72,76], 'wp' => [48,55], 'csi' => [30,36], 'cd' => [83,88]],
                ],
                'Neha Sharma' => [
                    'role' => 'developer', 'trend' => 'growing',
                    'signals' => ['msgs' => [33,42], 'active' => [5,5], 'collabs' => [10,13], 'after' => [3,6], 'calls' => [3,5], 'mtgs' => [7,10]],
                    'tasks' => ['done_rate' => [0.82,0.92], 'total' => [5,7], 'sp_range' => [1,8]],
                    'snapshot' => ['ci' => [88,93], 'rs' => [80,85], 'wp' => [38,45], 'csi' => [18,23], 'cd' => [60,70]],
                ],
                'Omar Ali' => [
                    'role' => 'pm', 'trend' => 'stable',
                    'signals' => ['msgs' => [50,60], 'active' => [5,5], 'collabs' => [16,20], 'after' => [10,15], 'calls' => [5,8], 'mtgs' => [13,18]],
                    'tasks' => ['done_rate' => [0.65,0.78], 'total' => [5,7], 'sp_range' => [1,5]],
                    'snapshot' => ['ci' => [78,84], 'rs' => [66,72], 'wp' => [55,65], 'csi' => [40,48], 'cd' => [90,96]],
                ],
                'Maya Chen' => [
                    'role' => 'devops', 'trend' => 'overloaded',
                    'signals' => ['msgs' => [38,46], 'active' => [5,5], 'collabs' => [11,15], 'after' => [20,30], 'calls' => [4,6], 'mtgs' => [8,12]],
                    'tasks' => ['done_rate' => [0.30,0.45], 'total' => [6,9], 'sp_range' => [2,8]],
                    'snapshot' => ['ci' => [65,72], 'rs' => [33,40], 'wp' => [78,88], 'csi' => [60,70], 'cd' => [70,78]],
                ],
                'Nina Tan' => [
                    'role' => 'developer', 'trend' => 'declining',
                    'signals' => ['msgs' => [22,28], 'active' => [2,4], 'collabs' => [5,8], 'after' => [1,3], 'calls' => [1,3], 'mtgs' => [4,6]],
                    'tasks' => ['done_rate' => [0.15,0.30], 'total' => [5,7], 'sp_range' => [1,8]],
                    'snapshot' => ['ci' => [53,62], 'rs' => [38,45], 'wp' => [25,35], 'csi' => [22,28], 'cd' => [40,48]],
                ],
            ],
        ],

        // ── Nalam Labs (org 5) — Zoho Projects + Slack ────────────
        5 => [
            'source_type' => 'zoho_projects',
            'comm_source' => 'slack',
            'employees' => [
                'Ravi Das' => [
                    'role' => 'lead', 'trend' => 'stable',
                    'signals' => ['msgs' => [48,56], 'active' => [5,5], 'collabs' => [14,17], 'after' => [7,11], 'calls' => [7,10], 'mtgs' => [12,15]],
                    'tasks' => ['done_rate' => [0.72,0.82], 'total' => [6,8], 'sp_range' => [2,8]],
                    'snapshot' => ['ci' => [84,88], 'rs' => [72,76], 'wp' => [48,55], 'csi' => [30,36], 'cd' => [83,88]],
                ],
                'Sara Noor' => [
                    'role' => 'data_scientist', 'trend' => 'growing',
                    'signals' => ['msgs' => [35,43], 'active' => [5,5], 'collabs' => [11,14], 'after' => [4,6], 'calls' => [3,5], 'mtgs' => [8,10]],
                    'tasks' => ['done_rate' => [0.80,0.90], 'total' => [5,7], 'sp_range' => [2,8]],
                    'snapshot' => ['ci' => [89,93], 'rs' => [80,85], 'wp' => [38,44], 'csi' => [18,22], 'cd' => [63,70]],
                ],
                'Kai Chen' => [
                    'role' => 'developer', 'trend' => 'overloaded',
                    'signals' => ['msgs' => [38,46], 'active' => [5,5], 'collabs' => [12,15], 'after' => [18,24], 'calls' => [4,6], 'mtgs' => [8,11]],
                    'tasks' => ['done_rate' => [0.35,0.50], 'total' => [5,7], 'sp_range' => [2,8]],
                    'snapshot' => ['ci' => [66,72], 'rs' => [35,42], 'wp' => [76,84], 'csi' => [58,68], 'cd' => [72,78]],
                ],
                'Zara Khan' => [
                    'role' => 'designer', 'trend' => 'declining',
                    'signals' => ['msgs' => [22,28], 'active' => [3,4], 'collabs' => [5,8], 'after' => [1,3], 'calls' => [1,3], 'mtgs' => [4,6]],
                    'tasks' => ['done_rate' => [0.20,0.35], 'total' => [4,6], 'sp_range' => [1,5]],
                    'snapshot' => ['ci' => [54,62], 'rs' => [38,45], 'wp' => [26,34], 'csi' => [23,28], 'cd' => [42,50]],
                ],
                'Leo Kim' => [
                    'role' => 'developer', 'trend' => 'stable',
                    'signals' => ['msgs' => [40,48], 'active' => [5,5], 'collabs' => [13,15], 'after' => [5,9], 'calls' => [4,6], 'mtgs' => [10,12]],
                    'tasks' => ['done_rate' => [0.65,0.78], 'total' => [5,7], 'sp_range' => [2,5]],
                    'snapshot' => ['ci' => [80,85], 'rs' => [68,74], 'wp' => [50,58], 'csi' => [34,40], 'cd' => [80,85]],
                ],
            ],
        ],
    ];

    // ── Task title templates per role ──────────────────────────────
    private array $taskTemplates = [
        'lead' => [
            'Review and merge PRs from sprint backlog',
            'Architecture review for %s module',
            'Conduct code review for %s',
            'Sprint planning and backlog grooming',
            'Optimize %s performance bottleneck',
            'Design data model for %s feature',
            'Set up monitoring alerts for %s',
            'Mentor team on %s best practices',
            'Stakeholder demo preparation',
            'Incident response: investigate %s issue',
        ],
        'developer' => [
            'Implement %s API endpoint',
            'Fix %s validation bug',
            'Write unit tests for %s service',
            'Refactor %s module for readability',
            'Add error handling to %s',
            'Optimize database queries in %s',
            'Update %s dependencies to latest',
            'Build %s integration tests',
            'Fix failing CI tests in %s pipeline',
            'Implement retry logic for %s',
        ],
        'pm' => [
            'Update roadmap with Q%d priorities',
            'Sprint retrospective documentation',
            'Customer feedback analysis for %s',
            'Create user stories for %s feature',
            'Stakeholder presentation on %s',
            'Competitive analysis: %s market',
            'Write PRD for %s initiative',
            'Plan cross-team coordination for %s',
            'Update Jira/board with sprint progress',
            'Prioritize bug backlog for next sprint',
        ],
        'designer' => [
            'Design wireframes for %s screen',
            'Create high-fidelity mockup for %s',
            'Conduct user testing for %s flow',
            'Update design system components',
            'Accessibility audit for %s page',
            'Create icon set for %s feature',
            'Design onboarding flow improvements',
            'Review UX for %s interaction',
        ],
        'devops' => [
            'Configure autoscaling for %s service',
            'Update Terraform modules for %s',
            'Fix CI/CD pipeline for %s',
            'Set up monitoring dashboard for %s',
            'Rotate credentials for %s environment',
            'Optimize Docker image for %s',
            'Debug deployment failure in %s',
            'Set up staging environment for %s',
        ],
        'data_scientist' => [
            'Train %s classification model',
            'Evaluate model performance for %s',
            'Build feature engineering pipeline for %s',
            'Run A/B test for %s model version',
            'Optimize inference latency for %s',
            'Create data validation pipeline',
            'Update training data with %s samples',
            'Deploy model version %s to production',
        ],
        'hr' => [
            'Screen candidates for %s position',
            'Schedule interviews for %s role',
            'Update employee onboarding docs',
            'Process leave requests for sprint',
            'Coordinate team building activity',
            'Review performance feedback submissions',
        ],
    ];

    private array $featureWords = [
        'authentication', 'dashboard', 'notifications', 'search', 'reporting',
        'analytics', 'billing', 'user management', 'settings', 'API gateway',
        'file upload', 'data export', 'caching', 'logging', 'permissions',
        'webhooks', 'scheduler', 'migration', 'deployment', 'monitoring',
    ];

    public function handle(): void
    {
        $targetOrg = $this->option('org') ? (int) $this->option('org') : null;
        $numWeeks  = (int) $this->option('weeks');

        $orgs = $targetOrg ? [$targetOrg] : [3, 4, 5];

        foreach ($orgs as $orgId) {
            if (!isset($this->profiles[$orgId])) {
                $this->warn("Unknown org ID: {$orgId}");
                continue;
            }

            $orgConfig = $this->profiles[$orgId];
            $orgName   = match($orgId) { 3 => 'Nalam Systems', 4 => 'Nalam Tech', 5 => 'Nalam Labs' };

            $this->info("═══ Refreshing {$orgName} (org {$orgId}) ═══");

            $employees = Employee::where('organization_id', $orgId)
                ->whereIn(\DB::raw("CONCAT(first_name, ' ', last_name)"), array_keys($orgConfig['employees']))
                ->get()
                ->keyBy(fn($e) => $e->first_name . ' ' . $e->last_name);

            for ($w = $numWeeks - 1; $w >= 0; $w--) {
                $weekDate = now()->subWeeks($w);
                $period   = $weekDate->format('Y') . '-W' . str_pad($weekDate->isoWeek(), 2, '0', STR_PAD_LEFT);
                $monthKey = $weekDate->format('Y-m');

                $this->line("  Week: {$period}");

                foreach ($orgConfig['employees'] as $name => $profile) {
                    $emp = $employees[$name] ?? null;
                    if (!$emp) continue;

                    // 1. Generate signals
                    $this->generateSignals($emp, $orgId, $orgConfig['comm_source'], $period, $profile);

                    // 2. Generate tasks (only for current week to avoid duplicates)
                    if ($w === 0) {
                        $this->generateTasks($emp, $orgId, $orgConfig['source_type'], $monthKey, $profile);
                    }

                    // 3. Generate snapshot (only for latest 2 weeks)
                    if ($w <= 1) {
                        $this->generateSnapshot($emp, $orgId, $period, $profile, $name);
                    }
                }
            }

            // Post fresh messages if flags are set
            if ($this->option('with-slack') && $orgConfig['comm_source'] === 'slack') {
                $this->postSlackMessages($orgId);
            }
            if ($this->option('with-teams') && $orgConfig['comm_source'] === 'teams') {
                $this->postTeamsMessages($orgId);
            }

            $this->info("  Done!\n");
        }

        $this->info('Demo data refresh complete. Run Work Pulse analysis to update AI insights.');
    }

    private function generateSignals(Employee $emp, int $orgId, string $source, string $period, array $profile): void
    {
        $s = $profile['signals'];

        $metrics = [
            ['messages_sent_count',         rand($s['msgs'][0], $s['msgs'][1]),         'count'],
            ['active_days_count',           rand($s['active'][0], $s['active'][1]),      'days'],
            ['unique_collaborators_count',  rand($s['collabs'][0], $s['collabs'][1]),    'count'],
            ['after_hours_message_pct',     round(rand($s['after'][0]*10, $s['after'][1]*10) / 10, 1), 'percent'],
            ['calls_count',                 rand($s['calls'][0], $s['calls'][1]),        'count'],
            ['meetings_attended_count',     rand($s['mtgs'][0], $s['mtgs'][1]),          'count'],
            ['channel_messages_count',      rand((int)($s['msgs'][0]*0.4), (int)($s['msgs'][1]*0.45)), 'count'],
            ['private_chat_messages_count', rand((int)($s['msgs'][0]*0.55), (int)($s['msgs'][1]*0.6)), 'count'],
        ];

        foreach ($metrics as [$key, $val, $unit]) {
            EmployeeSignal::updateOrCreate(
                ['employee_id' => $emp->id, 'source_type' => $source, 'metric_key' => $key, 'period' => $period],
                ['organization_id' => $orgId, 'metric_value' => $val, 'metric_unit' => $unit]
            );
        }
    }

    private function generateTasks(Employee $emp, int $orgId, string $sourceType, string $monthKey, array $profile): void
    {
        $tp       = $profile['tasks'];
        $total    = rand($tp['total'][0], $tp['total'][1]);
        $doneRate = rand((int)($tp['done_rate'][0]*100), (int)($tp['done_rate'][1]*100)) / 100;
        $doneCount= (int) round($total * $doneRate);
        $role     = $profile['role'];
        $templates= $this->taskTemplates[$role] ?? $this->taskTemplates['developer'];

        // Delete old demo-generated tasks for this employee+month to avoid duplicates
        EmployeeTask::where('employee_id', $emp->id)
            ->where('organization_id', $orgId)
            ->where('external_id', 'like', 'demo_' . $emp->id . '_' . $monthKey . '_%')
            ->delete();

        $priorities = ['High', 'Medium', 'Low'];
        $types      = ['Task', 'Issue', 'Bug'];

        for ($i = 0; $i < $total; $i++) {
            $template = $templates[array_rand($templates)];
            $feature  = $this->featureWords[array_rand($this->featureWords)];
            $quarter  = (int) ceil(now()->month / 3);

            $title = str_contains($template, '%s')
                ? sprintf($template, $feature)
                : (str_contains($template, '%d') ? sprintf($template, $quarter) : $template);

            $isDone     = $i < $doneCount;
            $status     = $isDone ? 'Done' : ($i < $doneCount + 2 ? 'Doing' : 'To Do');
            $createdAt  = Carbon::parse($monthKey . '-01')->addDays(rand(0, 25));
            $completedAt= $isDone ? $createdAt->copy()->addDays(rand(1, 7)) : null;
            $sp         = rand($tp['sp_range'][0], $tp['sp_range'][1]);

            EmployeeTask::create([
                'employee_id'       => $emp->id,
                'organization_id'   => $orgId,
                'source_type'       => $sourceType,
                'external_id'       => 'demo_' . $emp->id . '_' . $monthKey . '_' . $i,
                'title'             => $title,
                'task_type'         => $types[array_rand($types)],
                'status'            => $status,
                'priority'          => $priorities[array_rand($priorities)],
                'story_points'      => $sp,
                'source_created_at' => $createdAt,
                'completed_at'      => $completedAt,
            ]);
        }
    }

    private function generateSnapshot(Employee $emp, int $orgId, string $period, array $profile, string $name): void
    {
        $s = $profile['snapshot'];
        $trend = $profile['trend'];

        $ci  = rand($s['ci'][0], $s['ci'][1]);
        $rs  = rand($s['rs'][0], $s['rs'][1]);
        $wp  = rand($s['wp'][0], $s['wp'][1]);
        $csi = rand($s['csi'][0], $s['csi'][1]);
        $cd  = rand($s['cd'][0], $s['cd'][1]);

        $summaryTemplates = [
            'stable'     => [
                "{$name} maintains consistent performance with a consistency index of {$ci}. Workload is balanced and collaboration remains strong. No concerning signals this week.",
                "Stable week for {$name}. Task completion on track, meetings and collaboration within normal range. Work-life balance healthy.",
                "{$name} continues steady delivery. Consistency index at {$ci}, collaboration density at {$cd}. Solid contributor this sprint.",
            ],
            'growing'    => [
                "{$name} shows improving engagement trends. Consistency index up to {$ci}. Collaboration broadening with {$cd} density. Positive growth trajectory.",
                "Strong week for {$name}. Completion rates improving, active 5 days. Low context switching at {$csi} suggests deep focused work.",
                "{$name} continues upward trend. Most consistent performer with index {$ci}. Low after-hours work indicates healthy sustainable pace.",
            ],
            'declining'  => [
                "{$name} shows declining engagement signals. Active days reduced, collaboration density at {$cd} — team-low. Recommend manager check-in to understand blockers.",
                "Engagement continues below baseline for {$name}. Messages and meetings both down. Low workload pressure ({$wp}) suggests capacity exists but motivation may be the concern.",
                "{$name}: consistency index at {$ci}, below team average. Collaboration narrowing. Consider pairing with a team member to boost engagement.",
            ],
            'overloaded' => [
                "{$name} shows signs of overload. After-hours work elevated, workload pressure at {$wp}. Multiple items in-progress simultaneously creates high context switching ({$csi}). Consider reducing parallel work.",
                "Workload pressure remains high for {$name} at {$wp}. Recovery signal low at {$rs}. Recommend splitting large tasks into smaller deliverables to improve completion velocity.",
                "{$name}: high context switching index ({$csi}) with elevated after-hours. Workload rebalancing recommended. Strong technical contributor but at risk of burnout.",
            ],
        ];

        $templates = $summaryTemplates[$trend] ?? $summaryTemplates['stable'];
        $summary   = $templates[array_rand($templates)];

        SignalSnapshot::updateOrCreate(
            ['employee_id' => $emp->id, 'period' => $period],
            [
                'organization_id'         => $orgId,
                'consistency_index'       => $ci,
                'recovery_signal'         => $rs,
                'workload_pressure'       => $wp,
                'context_switching_index' => $csi,
                'collaboration_density'   => $cd,
                'ai_summary'              => $summary,
                'raw_signals'             => [],
                'ai_analysis'             => [],
            ]
        );
    }

    private function postSlackMessages(int $orgId): void
    {
        $conn = IntegrationConnection::where('organization_id', $orgId)
            ->where('type', 'slack')->first();
        if (!$conn) { $this->warn('  No Slack connection'); return; }

        $token = $conn->credentials['bot_token'] ?? $conn->credentials['access_token'] ?? '';
        if (!$token) { $this->warn('  No Slack token'); return; }

        $this->line('  Posting fresh Slack messages...');

        // Get channels
        $ch = curl_init('https://slack.com/api/conversations.list?types=public_channel&limit=100');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $channels = [];
        foreach ($resp['channels'] ?? [] as $c) {
            if ($c['name'] !== 'general' && $c['name'] !== 'random') {
                $channels[$c['name']] = $c['id'];
            }
        }

        // Pick a random channel and post a few timely messages
        $dayOfWeek = now()->dayOfWeekIso; // 1=Mon, 5=Fri
        $week      = now()->isoWeek();

        $freshMessages = [
            "Sprint {$week} standup: all blockers cleared. On track for sprint goals.",
            "Pushed a fix for the edge case we discussed yesterday. PR is up for review.",
            "Quick update: the performance optimization reduced response time by 35%.",
            "Deployed to staging. Please test the new flow when you get a chance.",
            "Good progress this week. Let's sync tomorrow on the integration timeline.",
            "FYI: updated the documentation for the new API endpoints.",
            "The automated tests are all passing now. Ready for final review.",
            "Just completed the security audit. No critical findings.",
            "Merged the feature branch. Release notes updated in the wiki.",
            "Heads up: dependency updates are ready. No breaking changes.",
        ];

        shuffle($freshMessages);
        $toPost = array_slice($freshMessages, 0, min(4, count($channels) * 2));

        $channelList = array_values($channels);
        $posted = 0;

        foreach ($toPost as $msg) {
            $chanId = $channelList[$posted % count($channelList)] ?? $channelList[0];

            // Join + post
            $ch = curl_init('https://slack.com/api/conversations.join');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}", "Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['channel' => $chanId]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch); curl_close($ch);

            $ch = curl_init('https://slack.com/api/chat.postMessage');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}", "Content-Type: application/json"]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['channel' => $chanId, 'text' => $msg]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $resp = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if ($resp['ok'] ?? false) $posted++;
            usleep(500000);
        }

        $this->line("    Posted {$posted} fresh messages");
    }

    private function postTeamsMessages(int $orgId): void
    {
        // Teams requires user tokens (ROPC) to post as users — skipping for now
        // The signal data refresh is sufficient for demo purposes
        $this->line('  Teams message posting skipped (use --with-slack for Slack orgs)');
    }
}
