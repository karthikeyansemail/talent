<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeSignal;
use App\Models\IntegrationConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SeedSlackDemoMessages extends Command
{
    protected $signature   = 'slack:seed-demo {--org=3 : Organization ID}';
    protected $description = 'Post realistic demo Slack messages and seed Work Pulse signals for Nalam Systems';

    // ── Realistic messages per channel ──────────────────────────────────────

    private array $channelMessages = [
        'general' => [
            ['from' => 'pm@nalamsystems.work',     'text' => "Good morning team! 👋 Reminder: all-hands sync is tomorrow at 10 AM IST. Please come prepared with your weekly highlights."],
            ['from' => 'hrm@nalamsystems.work',     'text' => "Insurance renewal forms are due by end of this month. Check your registered email for the form link."],
            ['from' => 'rahul.kumar@nalamsystems.work', 'text' => "Shipped the new authentication module to staging last night. Testing looks solid so far 🚀"],
            ['from' => 'sara.lim@nalamsystems.work', 'text' => "New brand guidelines are ready for review! I've updated the Figma workspace with the latest tokens and components."],
            ['from' => 'david.kim@nalamsystems.work', 'text' => "Hotfix deployed for the login page redirect issue. Should be stable now — let me know if you see anything weird."],
            ['from' => 'aman.verma@nalamsystems.work', 'text' => "Redis caching is live on prod 🎉 Cache hit rate already at 87%. Response times are looking significantly better."],
            ['from' => 'anita.patel@nalamsystems.work', 'text' => "Welcome kit sent to our new joiner Priya Sharma (Data Analyst) who starts next Monday! Please give her a warm welcome when she joins Slack."],
            ['from' => 'pm@nalamsystems.work',     'text' => "Q1 retrospective deck is shared in #engineering. Please add your inputs by Wednesday EOD."],
            ['from' => 'rahul.kumar@nalamsystems.work', 'text' => "Heads up: I'll be OOO Thursday afternoon. Aman is the escalation point for anything urgent."],
            ['from' => 'hrm@nalamsystems.work',     'text' => "Q1 performance review cycle kicks off Monday. Self-assessment forms sent to everyone — please complete by Friday 5 PM."],
            ['from' => 'david.kim@nalamsystems.work', 'text' => "Dashboard load time down from 3.2s → 0.8s after the chart optimisation PR. 📊"],
            ['from' => 'sara.lim@nalamsystems.work', 'text' => "Quick note: updating the design component library today. A few button styles will change slightly — frontend team please flag if anything looks off."],
            ['from' => 'aman.verma@nalamsystems.work', 'text' => "Automated DB backup verification is now running nightly. We'll get Slack alerts if anything fails."],
            ['from' => 'pm@nalamsystems.work',     'text' => "Great sprint everyone! Velocity is up 18% from last sprint. Keep it up 💪"],
        ],
        'engineering' => [
            ['from' => 'rahul.kumar@nalamsystems.work', 'text' => "*Standup* ✅ Yesterday: finished JWT refresh token logic + unit tests. Today: API integration test suite. Blockers: None."],
            ['from' => 'aman.verma@nalamsystems.work',  'text' => "*Standup* ✅ Yesterday: DB migration scripts for reporting module. Today: PR review + finishing query optimisation. Blockers: Need Rahul's review on #142."],
            ['from' => 'david.kim@nalamsystems.work',   'text' => "*Standup* ✅ Yesterday: responsive nav fix (#141), cross-browser testing. Today: integrating new charting library. Blockers: Waiting on design specs from Sara."],
            ['from' => 'aman.verma@nalamsystems.work',  'text' => "PR #142 is up for review — DB schema migrations for the new reporting module. Fairly straightforward, ~80 lines. @rahul.kumar if you get a chance today?"],
            ['from' => 'rahul.kumar@nalamsystems.work', 'text' => "On it @Aman, will review #142 after standup. Left a few minor comments on #139 too — nothing blocking."],
            ['from' => 'david.kim@nalamsystems.work',   'text' => "Can someone review #141? Responsive nav fix. Only 50 lines, mostly CSS. Tested on Chrome/Safari/Firefox."],
            ['from' => 'rahul.kumar@nalamsystems.work', 'text' => "Reviewed #141 ✅ Looks clean David. One suggestion on the breakpoint but it's minor. Approving."],
            ['from' => 'aman.verma@nalamsystems.work',  'text' => "Merged #142. Thanks for the quick review! Running migration on staging now."],
            ['from' => 'david.kim@nalamsystems.work',   'text' => "The Recharts integration is working well — dropped 40KB from bundle size compared to Chart.js. Win!"],
            ['from' => 'rahul.kumar@nalamsystems.work', 'text' => "Security audit findings landed in the backlog. We have 3 medium-severity items to address this sprint. I'll assign them out in the planning call."],
            ['from' => 'aman.verma@nalamsystems.work',  'text' => "Wrote the API rate-limiting middleware. Tests passing. Will raise a PR tomorrow morning after cleanup."],
            ['from' => 'david.kim@nalamsystems.work',   'text' => "FYI — I found a memory leak in the websocket handler. Opened issue #156. Looking at it now."],
            ['from' => 'rahul.kumar@nalamsystems.work', 'text' => "Good catch David. Let me know if you need a second pair of eyes on that."],
            ['from' => 'aman.verma@nalamsystems.work',  'text' => "Prod deploy window is Friday 7 PM IST. Please get your PRs merged by 5 PM latest."],
            ['from' => 'david.kim@nalamsystems.work',   'text' => "Memory leak fixed — was a missing cleanup in the `useEffect` teardown. PR #157 up."],
            ['from' => 'rahul.kumar@nalamsystems.work', 'text' => "Sprint planning artefacts are in Confluence. Tickets assigned. Ping me with questions."],
        ],
        'design' => [
            ['from' => 'sara.lim@nalamsystems.work',    'text' => "Sharing final wireframes for the new analytics dashboard. Feedback welcome by EOD Thursday — especially on the KPI card layout."],
            ['from' => 'pm@nalamsystems.work',          'text' => "Sara, the new onboarding flow is really clean and intuitive. Great work! The reduced step count will definitely help conversion."],
            ['from' => 'sara.lim@nalamsystems.work',    'text' => "Thank you! Will push the updated design tokens to the shared repo later today. @david.kim let me know if you need the Figma variables exported."],
            ['from' => 'david.kim@nalamsystems.work',   'text' => "Yes please! If you can export as CSS variables that would be perfect for the new theming system."],
            ['from' => 'sara.lim@nalamsystems.work',    'text' => "Done ✅ CSS variables + a JSON tokens file both in /design-tokens. Let me know if the naming convention works for you."],
            ['from' => 'sara.lim@nalamsystems.work',    'text' => "Updated the icon set — added 12 new icons for the resource allocation module. All SVGs optimised and in the Figma library."],
            ['from' => 'pm@nalamsystems.work',          'text' => "The mobile mock-ups are excellent Sara. Sharing with the client today. They're going to love the dark mode option."],
            ['from' => 'sara.lim@nalamsystems.work',    'text' => "Accessibility audit done on the new components. Updated contrast ratios on 4 elements — all WCAG AA compliant now. Updated specs in Figma."],
        ],
        'hr-updates' => [
            ['from' => 'anita.patel@nalamsystems.work', 'text' => "📋 *Performance Review Reminder* — Q1 self-assessments are due this Friday 5 PM. The form is in the HR portal. Reach out if you have any questions."],
            ['from' => 'hrm@nalamsystems.work',         'text' => "Insurance renewal: please submit your nomination forms by the 28th. Family coverage options are included this year. Details in the email sent yesterday."],
            ['from' => 'anita.patel@nalamsystems.work', 'text' => "🎉 Congratulations to *Rahul Kumar* on completing 2 years with Nalam Systems! Thank you for your contributions to the engineering team."],
            ['from' => 'hrm@nalamsystems.work',         'text' => "Reminder: the Learning & Development budget (₹15,000 per employee) resets at the end of Q1. Please submit reimbursement claims before March 31st."],
            ['from' => 'anita.patel@nalamsystems.work', 'text' => "Onboarding checklist for Priya Sharma sent to the team leads. Please ensure laptop setup and access provisioning are done by Friday."],
            ['from' => 'hrm@nalamsystems.work',         'text' => "Q1 bonus payouts will be processed this Friday. Net amounts will reflect in your accounts by Monday. Great work this quarter everyone! 🌟"],
            ['from' => 'anita.patel@nalamsystems.work', 'text' => "Pulse survey results are in — team engagement score is 82/100, up from 76 last quarter. Top themes: collaboration and project clarity. Full report shared with managers."],
            ['from' => 'hrm@nalamsystems.work',         'text' => "Work-from-home policy update: Fridays are now fully flexible remote for all teams. The updated policy doc is in the company handbook."],
        ],
    ];

    // ── Emoji avatars per employee ───────────────────────────────────────────

    private array $avatars = [
        'rahul.kumar@nalamsystems.work' => ':male-technologist:',
        'david.kim@nalamsystems.work'   => ':male-technologist:',
        'aman.verma@nalamsystems.work'  => ':male-technologist:',
        'sara.lim@nalamsystems.work'    => ':female-artist:',
        'anita.patel@nalamsystems.work' => ':female-office-worker:',
        'hrm@nalamsystems.work'         => ':female-office-worker:',
        'pm@nalamsystems.work'          => ':briefcase:',
        'gm@nalamsystems.work'          => ':necktie:',
    ];

    // ── Display names ────────────────────────────────────────────────────────

    private array $displayNames = [
        'rahul.kumar@nalamsystems.work' => 'Rahul Kumar',
        'david.kim@nalamsystems.work'   => 'David Kim',
        'aman.verma@nalamsystems.work'  => 'Aman Verma',
        'sara.lim@nalamsystems.work'    => 'Sara Lim',
        'anita.patel@nalamsystems.work' => 'Anita Patel',
        'hrm@nalamsystems.work'         => 'HR Manager',
        'pm@nalamsystems.work'          => 'Program Manager',
        'gm@nalamsystems.work'          => 'General Manager',
    ];

    public function handle(): void
    {
        $orgId = (int) $this->option('org');

        $connection = IntegrationConnection::where('organization_id', $orgId)
            ->where('type', 'slack')
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            $this->error("No active Slack integration found for org {$orgId}.");
            return;
        }

        $token = $connection->credentials['access_token'] ?? null;
        if (!$token) {
            $this->error('No access_token in Slack credentials. Reconnect Slack from Settings → Integrations.');
            return;
        }

        $this->info("Using Slack connection: {$connection->name}");

        // ── 1. Resolve channel IDs ───────────────────────────────────────────
        $this->info('Fetching channels...');
        $channelIds = $this->resolveChannelIds($token, array_keys($this->channelMessages));

        if (empty($channelIds)) {
            $this->error('No matching channels found. Please create these channels in Slack: ' .
                implode(', ', array_map(fn($c) => "#{$c}", array_keys($this->channelMessages))));
            return;
        }

        // ── 2. Post messages ─────────────────────────────────────────────────
        $posted = 0;
        foreach ($this->channelMessages as $channelName => $messages) {
            $channelId = $channelIds[$channelName] ?? null;
            if (!$channelId) {
                $this->warn("  Channel #{$channelName} not found — skipping.");
                continue;
            }

            // Bot must join the channel before posting
            Http::withToken($token)->post('https://slack.com/api/conversations.join', ['channel' => $channelId]);

            $this->info("  Posting to #{$channelName}...");
            foreach ($messages as $msg) {
                $result = Http::withToken($token)->post('https://slack.com/api/chat.postMessage', [
                    'channel'    => $channelId,
                    'text'       => $msg['text'],
                    'username'   => $this->displayNames[$msg['from']] ?? $msg['from'],
                    'icon_emoji' => $this->avatars[$msg['from']] ?? ':bust_in_silhouette:',
                ])->json();

                if ($result['ok'] ?? false) {
                    $posted++;
                } else {
                    $this->warn("    Failed to post message: " . ($result['error'] ?? 'unknown'));
                }

                usleep(300_000); // 300ms — stay well within Slack rate limits (1 msg/sec)
            }
        }

        $this->info("✓ Posted {$posted} messages to Slack.");

        // ── 3. Seed EmployeeSignal records for Work Pulse ────────────────────
        $this->info('Seeding Work Pulse signals...');
        $this->seedEmployeeSignals($orgId);

        $this->info('✓ Done! Run "Sync Work Data" on any employee profile to refresh Work Pulse.');
    }

    private function resolveChannelIds(string $token, array $channelNames): array
    {
        $response = Http::withToken($token)->get('https://slack.com/api/conversations.list', [
            'types'            => 'public_channel',
            'exclude_archived' => true,
            'limit'            => 200,
        ]);

        $channels = $response->json('channels') ?? [];
        $map = [];
        foreach ($channels as $ch) {
            $name = strtolower($ch['name'] ?? '');
            if (in_array($name, $channelNames)) {
                $map[$name] = $ch['id'];
            }
        }
        return $map;
    }

    private function seedEmployeeSignals(int $orgId): void
    {
        $employees = Employee::where('organization_id', $orgId)->get();

        // Seed current week + 3 prior weeks for a realistic history
        $weeksToSeed = [0, -1, -2, -3];

        // Per-employee signal profiles (realistic variation)
        $profiles = [
            'rahul.kumar@nalamsystems.work' => ['msgs' => [24, 18, 22, 20], 'days' => [5, 4, 5, 4], 'collabs' => [6, 5, 6, 5], 'ah' => [8,  6,  10, 7]],
            'david.kim@nalamsystems.work'   => ['msgs' => [18, 15, 17, 14], 'days' => [5, 4, 4, 5], 'collabs' => [5, 4, 5, 4], 'ah' => [5,  8,  6,  4]],
            'aman.verma@nalamsystems.work'  => ['msgs' => [20, 22, 19, 21], 'days' => [5, 5, 4, 5], 'collabs' => [5, 6, 5, 5], 'ah' => [12, 9,  14, 11]],
            'sara.lim@nalamsystems.work'    => ['msgs' => [14, 12, 15, 11], 'days' => [5, 4, 5, 4], 'collabs' => [4, 4, 5, 3], 'ah' => [3,  5,  4,  2]],
            'anita.patel@nalamsystems.work' => ['msgs' => [10,  9, 11,  8], 'days' => [5, 5, 5, 4], 'collabs' => [6, 5, 6, 5], 'ah' => [2,  3,  2,  1]],
            'hrm@nalamsystems.work'         => ['msgs' => [12, 10, 13,  9], 'days' => [5, 4, 5, 5], 'collabs' => [7, 6, 7, 6], 'ah' => [4,  3,  5,  2]],
            'pm@nalamsystems.work'          => ['msgs' => [22, 20, 25, 18], 'days' => [5, 5, 5, 5], 'collabs' => [7, 7, 7, 6], 'ah' => [15, 12, 18, 10]],
        ];

        $synced = 0;
        foreach ($employees as $employee) {
            $profile = $profiles[$employee->email] ?? null;
            if (!$profile) {
                continue;
            }

            foreach ($weeksToSeed as $i => $weekOffset) {
                $weekDate = now()->addWeeks($weekOffset);
                $period   = $weekDate->format('Y') . '-W' . $weekDate->format('W');

                $metrics = [
                    ['key' => 'messages_sent_count',        'value' => $profile['msgs'][$i],   'unit' => 'count'],
                    ['key' => 'active_days_count',          'value' => $profile['days'][$i],   'unit' => 'days'],
                    ['key' => 'unique_collaborators_count', 'value' => $profile['collabs'][$i], 'unit' => 'count'],
                    ['key' => 'after_hours_message_pct',    'value' => $profile['ah'][$i],     'unit' => 'percent'],
                ];

                foreach ($metrics as $metric) {
                    EmployeeSignal::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'source_type' => 'slack',
                            'metric_key'  => $metric['key'],
                            'period'      => $period,
                        ],
                        [
                            'organization_id' => $orgId,
                            'metric_value'    => $metric['value'],
                            'metric_unit'     => $metric['unit'],
                        ]
                    );
                }

                $synced++;
            }
        }

        $this->info("  ✓ Seeded {$synced} signal records (4 weeks × per employee).");
    }
}
