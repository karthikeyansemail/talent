<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds realistic multi-period task, signal, and sprint sheet data for
 * employees 1–5 (org 1) so the Signal Intelligence tab is meaningful.
 *
 * Safe to re-run — uses insertOrIgnore / updateOrInsert.
 * Run with: php artisan db:seed --class=SignalIntelligenceDemoSeeder
 */
class SignalIntelligenceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $orgId = 1;

        // ── Employee profiles ──────────────────────────────────────────────
        // 1 Alice Wang     — Senior Engineer  — high throughput, fast, improving
        // 2 Bob Martinez   — Tech Lead        — mixed: planning + bugs, sprint manager
        // 3 Carol Davis    — Data Scientist   — medium throughput, stable
        // 4 Dan Wilson     — Frontend Dev     — lower Dec → improving Jan/Feb
        // 5 Eva Brown      — UX Designer      — fewer tasks, steady

        $this->seedTasks($orgId, $now);
        $this->seedSignals($orgId, $now);
        $this->seedSprintSheets($orgId, $now);
    }

    // ── TASKS ─────────────────────────────────────────────────────────────

    private function seedTasks(int $orgId, Carbon $now): void
    {
        $rows = [];

        // Alice (1): strong performer — 72%→75%→82% completion, fast cycle time
        $rows = array_merge($rows, $this->makePeriodTasks(1, $orgId, '2025-12', [
            ['Fix authentication token expiry bug',             'Bug',   'High',   3,  'Done',        12],
            ['Implement OAuth2 refresh flow',                   'Story', 'High',   8,  'Done',         9],
            ['Refactor session middleware',                     'Task',  'Medium', 5,  'Done',         7],
            ['Write unit tests for auth module',               'Task',  'Medium', 3,  'Done',         5],
            ['Resolve memory leak in background worker',       'Bug',   'High',   5,  'Done',        11],
            ['Upgrade PHP runtime to 8.2',                     'Task',  'Medium', 2,  'Done',         6],
            ['Profile slow API endpoints',                     'Task',  'Medium', 3,  'In Progress', null],
            ['Document authentication architecture',           'Task',  'Low',    2,  'To Do',       null],
            ['Investigate Redis connection timeouts',          'Bug',   'Medium', 2,  'To Do',       null],  // aging
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(1, $orgId, '2026-01', [
            ['Add rate limiting to public API',                'Story', 'High',   5,  'Done',         8],
            ['Fix CORS headers for mobile clients',            'Bug',   'High',   2,  'Done',         3],
            ['Implement API key rotation mechanism',           'Story', 'High',   8,  'Done',        10],
            ['Migrate legacy endpoints to v2',                 'Story', 'Medium', 13, 'Done',        14],
            ['Write integration tests for API gateway',        'Task',  'Medium', 5,  'Done',         7],
            ['Refactor error response formatting',             'Task',  'Low',    3,  'Done',         4],
            ['Improve request validation layer',               'Task',  'Medium', 3,  'In Progress', null],
            ['Add OpenAPI spec generation',                    'Task',  'Low',    2,  'To Do',       null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(1, $orgId, '2026-02', [
            ['Implement webhook delivery system',              'Story', 'High',   13, 'Done',         9],
            ['Fix payload size validation bug',               'Bug',   'High',   2,  'Done',         2],
            ['Add idempotency keys to payment endpoints',     'Story', 'High',   8,  'Done',         7],
            ['Refactor queue retry logic',                     'Task',  'Medium', 5,  'Done',         5],
            ['Write end-to-end tests for webhook flow',       'Task',  'Medium', 5,  'Done',         6],
            ['Add circuit breaker to external API calls',     'Story', 'Medium', 8,  'In Progress', null],
            ['Update API documentation',                      'Task',  'Low',    2,  'To Do',       null],
        ]));

        // Bob (2): Tech Lead — planning tasks + bugs, some spillover, sprint manager
        $rows = array_merge($rows, $this->makePeriodTasks(2, $orgId, '2025-12', [
            ['Define Sprint 12 scope and acceptance criteria', 'Story', 'High',   5,  'Done',         3],
            ['Review and merge 5 pending PRs',                'Task',  'High',   3,  'Done',         2],
            ['Unblock Dan on CSS grid layout issue',          'Task',  'High',   2,  'Done',         1],
            ['Fix critical null pointer exception in prod',   'Bug',   'High',   3,  'Done',         4],
            ['Sprint 12 retrospective action items',          'Task',  'Medium', 2,  'Done',         5],
            ['Resolve test environment database drift',       'Bug',   'Medium', 3,  'Done',         6],
            ['Onboard new contractor to codebase',            'Task',  'Medium', 5,  'In Progress', null],
            ['Set up monitoring dashboards',                  'Task',  'Low',    3,  'To Do',       null],  // aging
            ['Document deployment runbook',                   'Task',  'Low',    2,  'To Do',       null],  // aging
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(2, $orgId, '2026-01', [
            ['Define Sprint 13 and Sprint 14 scope',          'Story', 'High',   5,  'Done',         2],
            ['Fix intermittent test flakiness in CI',         'Bug',   'High',   3,  'Done',         5],
            ['Unblock Carol on data pipeline dependency',     'Task',  'High',   2,  'Done',         1],
            ['Review team capacity for Q1',                   'Task',  'Medium', 3,  'Done',         3],
            ['Resolve production memory spike',               'Bug',   'High',   5,  'Done',         6],
            ['Conduct 1:1 performance check-ins',             'Task',  'Medium', 2,  'Done',         4],
            ['Review architecture for new reporting module',  'Task',  'Medium', 5,  'In Progress', null],
            ['Update team OKR tracking doc',                  'Task',  'Low',    2,  'To Do',       null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(2, $orgId, '2026-02', [
            ['Define Sprint 15 scope and acceptance criteria', 'Story', 'High',   5,  'Done',         2],
            ['Fix authentication regression from last deploy', 'Bug',   'High',   3,  'Done',         3],
            ['Unblock Eva on design handoff blockers',        'Task',  'High',   2,  'Done',         1],
            ['Review and approve 4 architecture proposals',   'Task',  'High',   5,  'Done',         4],
            ['Coordinate cross-team dependency for Q1 launch','Task',  'Medium', 3,  'Done',         5],
            ['Sprint 15 mid-sprint velocity check',           'Task',  'Medium', 2,  'In Progress', null],
            ['Prepare appraisal documentation for team',      'Task',  'Low',    3,  'To Do',       null],
        ]));

        // Carol (3): Data Scientist — medium throughput, stable
        $rows = array_merge($rows, $this->makePeriodTasks(3, $orgId, '2025-12', [
            ['Build customer churn prediction model',         'Story', 'High',   13, 'Done',        15],
            ['Data pipeline for feature engineering',         'Story', 'High',   8,  'Done',        12],
            ['Fix data quality issue in events table',        'Bug',   'Medium', 3,  'Done',         5],
            ['Write model evaluation report',                 'Task',  'Medium', 5,  'Done',         8],
            ['Experiment: LSTM vs XGBoost comparison',        'Task',  'Medium', 8,  'Done',        10],
            ['Clean up ETL job error handling',               'Task',  'Low',    3,  'In Progress', null],
            ['Update data dictionary',                        'Task',  'Low',    2,  'To Do',       null],  // aging
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(3, $orgId, '2026-01', [
            ['Deploy churn model to production',              'Story', 'High',   8,  'Done',        10],
            ['Build real-time scoring API wrapper',           'Story', 'High',   8,  'Done',        12],
            ['Fix feature drift in prod model',               'Bug',   'High',   5,  'Done',         7],
            ['A/B test framework for model variants',        'Story', 'Medium', 13, 'Done',        16],
            ['Monitor model performance alerts',              'Task',  'Medium', 3,  'Done',         4],
            ['Prepare data science sprint review',            'Task',  'Medium', 2,  'Done',         3],
            ['Investigate anomaly in session duration data',  'Task',  'Low',    3,  'In Progress', null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(3, $orgId, '2026-02', [
            ['Next-best-action recommendation model',         'Story', 'High',   13, 'Done',        11],
            ['Fix incorrect null handling in feature store',  'Bug',   'High',   3,  'Done',         4],
            ['Retrain churn model with Q4 data',             'Task',  'Medium', 5,  'Done',         6],
            ['Document model serving architecture',           'Task',  'Medium', 3,  'Done',         5],
            ['Explore LLM embeddings for text features',     'Story', 'Medium', 8,  'In Progress', null],
            ['Update model monitoring dashboard',             'Task',  'Low',    2,  'To Do',       null],
        ]));

        // Dan (4): Frontend Dev — lower Dec → improving trend Jan/Feb
        $rows = array_merge($rows, $this->makePeriodTasks(4, $orgId, '2025-12', [
            ['Fix layout broken on Safari mobile',            'Bug',   'High',   3,  'Done',        10],
            ['Implement responsive nav component',            'Story', 'Medium', 5,  'Done',        12],
            ['Refactor date picker component',                'Task',  'Medium', 3,  'Done',         8],
            ['Fix z-index stacking issue in modals',          'Bug',   'Low',    2,  'Done',         4],
            ['Add keyboard accessibility to dropdown',        'Task',  'Medium', 3,  'In Progress', null],
            ['Write Storybook stories for form components',  'Task',  'Low',    2,  'In Progress', null],
            ['Update icon library to new brand set',          'Task',  'Low',    2,  'To Do',       null],  // aging
            ['Fix Safari-specific animation glitch',          'Bug',   'Medium', 2,  'To Do',       null],  // aging
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(4, $orgId, '2026-01', [
            ['Build reusable data table component',           'Story', 'High',   8,  'Done',         9],
            ['Fix input validation error display on mobile',  'Bug',   'High',   2,  'Done',         3],
            ['Implement dark mode toggle',                    'Story', 'Medium', 5,  'Done',         7],
            ['Fix chart rendering on retina displays',        'Bug',   'Medium', 3,  'Done',         5],
            ['Add skeleton loading states',                   'Task',  'Medium', 3,  'Done',         6],
            ['Migrate CSS to design token system',            'Task',  'Medium', 5,  'In Progress', null],
            ['Write unit tests for table component',          'Task',  'Low',    3,  'To Do',       null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(4, $orgId, '2026-02', [
            ['Rebuild dashboard widget grid',                 'Story', 'High',   8,  'Done',         8],
            ['Fix tooltip overflow in small viewports',       'Bug',   'High',   2,  'Done',         2],
            ['Implement real-time notification panel',        'Story', 'High',   8,  'Done',         9],
            ['Improve first contentful paint score',         'Task',  'Medium', 5,  'Done',         7],
            ['Fix drag-and-drop ordering bug',               'Bug',   'Medium', 3,  'Done',         4],
            ['Add animation to page transitions',             'Task',  'Low',    3,  'In Progress', null],
            ['Update component documentation',                'Task',  'Low',    2,  'To Do',       null],
        ]));

        // Eva (5): UX Designer — fewer tasks, design-focused, steady
        $rows = array_merge($rows, $this->makePeriodTasks(5, $orgId, '2025-12', [
            ['Create onboarding flow wireframes',             'Story', 'High',   5,  'Done',        10],
            ['User research: checkout abandonment study',     'Story', 'High',   8,  'Done',        14],
            ['Fix inconsistent button styling in designs',    'Bug',   'Medium', 2,  'Done',         3],
            ['Design system: update typography tokens',       'Task',  'Medium', 3,  'Done',         5],
            ['Prototype mobile nav redesign',                 'Story', 'Medium', 5,  'In Progress', null],
            ['Update Figma component library',                'Task',  'Low',    2,  'To Do',       null],  // aging
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(5, $orgId, '2026-01', [
            ['Design new reporting dashboard layout',         'Story', 'High',   8,  'Done',        11],
            ['Accessibility audit: WCAG 2.1 AA review',      'Task',  'High',   5,  'Done',         8],
            ['Fix contrast ratio issues in dark mode',        'Bug',   'High',   2,  'Done',         3],
            ['Create design handoff guide for developers',    'Task',  'Medium', 3,  'Done',         5],
            ['Prototype AI feature disclosure patterns',      'Story', 'Medium', 5,  'Done',         9],
            ['Conduct usability testing sessions',            'Task',  'Medium', 3,  'In Progress', null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(5, $orgId, '2026-02', [
            ['Redesign employee profile page',                'Story', 'High',   8,  'Done',         8],
            ['Fix icon misalignment in export dialog',       'Bug',   'Medium', 1,  'Done',         2],
            ['Design signal intelligence visualisations',    'Story', 'High',   8,  'Done',        10],
            ['Create micro-interaction specs for modals',    'Task',  'Medium', 3,  'In Progress', null],
            ['Update brand guidelines document',              'Task',  'Low',    2,  'To Do',       null],
        ]));

        DB::table('employee_tasks')->insertOrIgnore($rows);
    }

    /**
     * Build task rows for a given employee and period (YYYY-MM).
     * Each task: [title, type, priority, story_points, status, cycle_days]
     * cycle_days = null means no completed_at (not Done or still open).
     */
    private function makePeriodTasks(
        int $empId,
        int $orgId,
        string $period,   // 'YYYY-MM'
        array $tasks
    ): array {
        [$year, $month] = explode('-', $period);
        $rows = [];
        $day = 1;

        foreach ($tasks as $i => $t) {
            [$title, $type, $priority, $sp, $status, $cycleDays] = $t;

            $createdAt = Carbon::create((int)$year, (int)$month, min($day + $i, 28));
            $completedAt = ($status === 'Done' && $cycleDays !== null)
                ? $createdAt->copy()->addDays((int)$cycleDays)
                : null;

            $rows[] = [
                'employee_id'      => $empId,
                'organization_id'  => $orgId,
                'connection_id'    => null,
                'source_type'      => 'jira',
                'external_id'      => "DEMO-{$empId}-{$period}-{$i}",
                'title'            => $title,
                'description'      => null,
                'task_type'        => $type,
                'status'           => $status,
                'priority'         => $priority,
                'story_points'     => $sp,
                'assignee_email'   => null,
                'labels'           => json_encode([]),
                'completed_at'     => $completedAt?->toDateTimeString(),
                'source_created_at'=> $createdAt->toDateTimeString(),
                'metadata'         => json_encode(['resolution' => $status === 'Done' ? 'Done' : null]),
                'created_at'       => now()->toDateTimeString(),
                'updated_at'       => now()->toDateTimeString(),
            ];
        }

        return $rows;
    }

    // ── COMMUNICATION SIGNALS ──────────────────────────────────────────────

    private function seedSignals(int $orgId, Carbon $now): void
    {
        $rows = [];

        // Alice (1) and Bob (2) — Slack signals for 2 weekly periods
        $signalData = [
            1 => [ // Alice Wang
                '2026-W04' => [
                    'messages_sent_count'        => [84,   'count'],
                    'active_days_count'           => [5,    'days'],
                    'unique_collaborators_count'  => [12,   'count'],
                    'after_hours_message_pct'     => [8.5,  'percent'],
                    'calls_count'                 => [3,    'count'],
                    'meetings_attended_count'     => [6,    'count'],
                ],
                '2026-W05' => [
                    'messages_sent_count'        => [97,   'count'],
                    'active_days_count'           => [5,    'days'],
                    'unique_collaborators_count'  => [15,   'count'],
                    'after_hours_message_pct'     => [6.2,  'percent'],
                    'calls_count'                 => [4,    'count'],
                    'meetings_attended_count'     => [7,    'count'],
                ],
            ],
            2 => [ // Bob Martinez
                '2026-W04' => [
                    'messages_sent_count'        => [62,   'count'],
                    'active_days_count'           => [5,    'days'],
                    'unique_collaborators_count'  => [18,   'count'],
                    'after_hours_message_pct'     => [14.3, 'percent'],
                    'calls_count'                 => [8,    'count'],
                    'meetings_attended_count'     => [14,   'count'],
                ],
                '2026-W05' => [
                    'messages_sent_count'        => [58,   'count'],
                    'active_days_count'           => [4,    'days'],
                    'unique_collaborators_count'  => [16,   'count'],
                    'after_hours_message_pct'     => [11.8, 'percent'],
                    'calls_count'                 => [7,    'count'],
                    'meetings_attended_count'     => [12,   'count'],
                ],
            ],
        ];

        foreach ($signalData as $empId => $periods) {
            foreach ($periods as $period => $metrics) {
                foreach ($metrics as $key => [$value, $unit]) {
                    $rows[] = [
                        'employee_id'     => $empId,
                        'organization_id' => $orgId,
                        'source_type'     => 'slack',
                        'metric_key'      => $key,
                        'metric_value'    => $value,
                        'metric_unit'     => $unit,
                        'period'          => $period,
                        'metadata'        => json_encode([]),
                        'created_at'      => now()->toDateTimeString(),
                        'updated_at'      => now()->toDateTimeString(),
                    ];
                }
            }
        }

        // Delete existing demo signals for these employees to avoid duplicates
        DB::table('employee_signals')
            ->whereIn('employee_id', [1, 2])
            ->where('source_type', 'slack')
            ->whereIn('period', ['2026-W04', '2026-W05'])
            ->delete();

        if (!empty($rows)) {
            DB::table('employee_signals')->insert($rows);
        }
    }

    // ── SPRINT SHEETS (Bob = Tech Lead / Sprint Manager) ──────────────────

    private function seedSprintSheets(int $orgId, Carbon $now): void
    {
        $sprints = [
            [
                'sprint_name'      => 'Sprint 13',
                'start_date'       => '2026-01-06',
                'end_date'         => '2026-01-17',
                'planned_points'   => 42,
                'completed_points' => 36,
                'tasks_planned'    => 12,
                'tasks_completed'  => 10,
            ],
            [
                'sprint_name'      => 'Sprint 14',
                'start_date'       => '2026-01-20',
                'end_date'         => '2026-01-31',
                'planned_points'   => 45,
                'completed_points' => 43,
                'tasks_planned'    => 13,
                'tasks_completed'  => 12,
            ],
            [
                'sprint_name'      => 'Sprint 15',
                'start_date'       => '2026-02-03',
                'end_date'         => '2026-02-14',
                'planned_points'   => 48,
                'completed_points' => 44,
                'tasks_planned'    => 14,
                'tasks_completed'  => 13,
            ],
        ];

        // Remove existing demo sprint sheets for Bob
        DB::table('sprint_sheets')
            ->where('employee_id', 2)
            ->where('organization_id', $orgId)
            ->delete();

        foreach ($sprints as $sprint) {
            DB::table('sprint_sheets')->insert([
                'organization_id'  => $orgId,
                'employee_id'      => 2,  // Bob Martinez
                'sprint_name'      => $sprint['sprint_name'],
                'start_date'       => $sprint['start_date'],
                'end_date'         => $sprint['end_date'],
                'planned_points'   => $sprint['planned_points'],
                'completed_points' => $sprint['completed_points'],
                'tasks_planned'    => $sprint['tasks_planned'],
                'tasks_completed'  => $sprint['tasks_completed'],
                'metadata'         => json_encode([]),
                'created_at'       => now()->toDateTimeString(),
                'updated_at'       => now()->toDateTimeString(),
            ]);
        }
    }
}
