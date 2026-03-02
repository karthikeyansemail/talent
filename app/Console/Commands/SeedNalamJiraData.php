<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeTask;
use App\Models\JiraConnection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SeedNalamJiraData extends Command
{
    protected $signature   = 'nalam:seed-jira {--dry-run : Show what would be created without calling Jira API}';
    protected $description = 'Seed realistic Jira tasks for Nalam Systems employees so Signal Intelligence tab shows meaningful data';

    // Org + employees (IDs from the DB)
    private const ORG_ID     = 3;
    private const EMPLOYEES  = [
        9  => ['name' => 'Rahul Kumar',  'email' => 'rahul.kumar@nalamsystems.work',  'role' => 'Senior Full Stack Developer'],
        10 => ['name' => 'David Kim',    'email' => 'david.kim@nalamsystems.work',    'role' => 'Frontend Developer'],
        11 => ['name' => 'Aman Verma',   'email' => 'aman.verma@nalamsystems.work',   'role' => 'Backend Developer'],
        12 => ['name' => 'Sara Lim',     'email' => 'sara.lim@nalamsystems.work',     'role' => 'UI/UX Designer'],
        16 => ['name' => 'Program Manager', 'email' => 'pm@nalamsystems.work',        'role' => 'Program Manager'],
    ];

    // Tasks to create in Jira per employee
    // 'status_transition' => target Jira status name (we'll resolve to ID at runtime)
    private function taskBlueprints(): array
    {
        return [
            // ── Rahul Kumar (Senior Full Stack Dev) ─────────────────────────────
            9 => [
                ['summary' => 'Implement JWT authentication with refresh token rotation',     'type' => 'Story', 'priority' => 'High',   'sp' => 8,  'done' => true,  'days_ago_created' => 52, 'cycle_days' => 4],
                ['summary' => 'Design multi-tenant database schema for organization isolation','type' => 'Story', 'priority' => 'High',   'sp' => 13, 'done' => true,  'days_ago_created' => 48, 'cycle_days' => 6],
                ['summary' => 'Build RESTful CRUD endpoints for employee management',         'type' => 'Task',  'priority' => 'Medium', 'sp' => 5,  'done' => true,  'days_ago_created' => 44, 'cycle_days' => 3],
                ['summary' => 'Add role-based access control middleware (RBAC)',              'type' => 'Story', 'priority' => 'High',   'sp' => 8,  'done' => true,  'days_ago_created' => 40, 'cycle_days' => 5],
                ['summary' => 'Fix N+1 query issue in employee listing endpoint',            'type' => 'Bug',   'priority' => 'High',   'sp' => 3,  'done' => true,  'days_ago_created' => 38, 'cycle_days' => 2],
                ['summary' => 'Set up Redis caching layer for API responses',                'type' => 'Task',  'priority' => 'Medium', 'sp' => 5,  'done' => true,  'days_ago_created' => 32, 'cycle_days' => 4],
                ['summary' => 'Integrate queue workers for async AI processing jobs',        'type' => 'Story', 'priority' => 'Medium', 'sp' => 5,  'done' => true,  'days_ago_created' => 26, 'cycle_days' => 5],
                ['summary' => 'Write API documentation using OpenAPI 3.0',                  'type' => 'Task',  'priority' => 'Low',    'sp' => 3,  'done' => true,  'days_ago_created' => 20, 'cycle_days' => 3],
                ['summary' => 'Race condition bug in concurrent session handling',            'type' => 'Bug',   'priority' => 'High',   'sp' => 5,  'done' => true,  'days_ago_created' => 16, 'cycle_days' => 3],
                ['summary' => 'Add database connection pooling for high-traffic scenarios',  'type' => 'Task',  'priority' => 'Medium', 'sp' => 3,  'done' => false, 'days_ago_created' => 10, 'cycle_days' => null],
                ['summary' => 'Implement rate limiting on public API endpoints',             'type' => 'Story', 'priority' => 'High',   'sp' => 5,  'done' => false, 'days_ago_created' => 6,  'cycle_days' => null],
                ['summary' => 'Memory leak in background job processor',                     'type' => 'Bug',   'priority' => 'High',   'sp' => null, 'done' => false, 'days_ago_created' => 3, 'cycle_days' => null],
            ],

            // ── David Kim (Frontend Developer) ───────────────────────────────────
            10 => [
                ['summary' => 'Build responsive dashboard layout with sidebar navigation',   'type' => 'Story', 'priority' => 'High',   'sp' => 8,  'done' => true,  'days_ago_created' => 55, 'cycle_days' => 7],
                ['summary' => 'Implement real-time data table with sorting and pagination',  'type' => 'Story', 'priority' => 'High',   'sp' => 8,  'done' => true,  'days_ago_created' => 48, 'cycle_days' => 5],
                ['summary' => 'Create reusable form components with validation hooks',       'type' => 'Task',  'priority' => 'Medium', 'sp' => 5,  'done' => true,  'days_ago_created' => 42, 'cycle_days' => 4],
                ['summary' => 'Fix layout overflow bug on mobile viewport',                 'type' => 'Bug',   'priority' => 'High',   'sp' => 2,  'done' => true,  'days_ago_created' => 38, 'cycle_days' => 2],
                ['summary' => 'Implement dark mode with CSS custom properties',             'type' => 'Task',  'priority' => 'Low',    'sp' => 3,  'done' => true,  'days_ago_created' => 30, 'cycle_days' => 3],
                ['summary' => 'Write unit tests for dashboard components with Jest',        'type' => 'Task',  'priority' => 'Medium', 'sp' => 3,  'done' => true,  'days_ago_created' => 24, 'cycle_days' => 4],
                ['summary' => 'Dropdown menu does not close on outside click in Safari',    'type' => 'Bug',   'priority' => 'Medium', 'sp' => 2,  'done' => true,  'days_ago_created' => 18, 'cycle_days' => 2],
                ['summary' => 'Integrate Storybook for component documentation',            'type' => 'Task',  'priority' => 'Low',    'sp' => 3,  'done' => false, 'days_ago_created' => 12, 'cycle_days' => null],
                ['summary' => 'Add GraphQL client integration with Apollo',                 'type' => 'Story', 'priority' => 'Medium', 'sp' => 5,  'done' => false, 'days_ago_created' => 8,  'cycle_days' => null],
                ['summary' => 'Tooltip z-index conflict on modal overlay pages',            'type' => 'Bug',   'priority' => 'Low',    'sp' => null, 'done' => false, 'days_ago_created' => 4, 'cycle_days' => null],
            ],

            // ── Aman Verma (Backend Developer) ───────────────────────────────────
            11 => [
                ['summary' => 'Design FastAPI microservice for ML inference',               'type' => 'Story', 'priority' => 'High',   'sp' => 8,  'done' => true,  'days_ago_created' => 58, 'cycle_days' => 6],
                ['summary' => 'Set up Celery distributed task queue with Redis broker',     'type' => 'Task',  'priority' => 'High',   'sp' => 5,  'done' => true,  'days_ago_created' => 50, 'cycle_days' => 4],
                ['summary' => 'Write data ingestion pipeline for Jira webhook events',      'type' => 'Story', 'priority' => 'Medium', 'sp' => 5,  'done' => true,  'days_ago_created' => 44, 'cycle_days' => 5],
                ['summary' => 'Containerise FastAPI service with Docker and docker-compose','type' => 'Task',  'priority' => 'Medium', 'sp' => 3,  'done' => true,  'days_ago_created' => 38, 'cycle_days' => 3],
                ['summary' => 'Implement CI/CD pipeline with GitHub Actions',               'type' => 'Story', 'priority' => 'High',   'sp' => 8,  'done' => true,  'days_ago_created' => 30, 'cycle_days' => 7],
                ['summary' => 'Deploy Kubernetes manifests for production workloads',       'type' => 'Task',  'priority' => 'High',   'sp' => 5,  'done' => true,  'days_ago_created' => 22, 'cycle_days' => 5],
                ['summary' => 'Add health check and readiness probes to all services',      'type' => 'Task',  'priority' => 'Medium', 'sp' => 3,  'done' => true,  'days_ago_created' => 16, 'cycle_days' => 3],
                ['summary' => 'Debug memory leak in Celery worker after 24h uptime',       'type' => 'Bug',   'priority' => 'High',   'sp' => 5,  'done' => false, 'days_ago_created' => 40, 'cycle_days' => null],  // aging
                ['summary' => 'Implement distributed tracing with OpenTelemetry',           'type' => 'Story', 'priority' => 'Medium', 'sp' => 5,  'done' => false, 'days_ago_created' => 8,  'cycle_days' => null],
                ['summary' => 'Improve error handling for downstream API timeouts',         'type' => 'Task',  'priority' => 'Medium', 'sp' => 3,  'done' => false, 'days_ago_created' => 5,  'cycle_days' => null],
            ],

            // ── Sara Lim (UI/UX Designer) ────────────────────────────────────────
            12 => [
                ['summary' => 'Design mobile-first onboarding flow with 5-step wizard',    'type' => 'Story', 'priority' => 'High',   'sp' => 8,  'done' => true,  'days_ago_created' => 55, 'cycle_days' => 8],
                ['summary' => 'Create design system with reusable component library',       'type' => 'Story', 'priority' => 'High',   'sp' => 13, 'done' => true,  'days_ago_created' => 45, 'cycle_days' => 9],
                ['summary' => 'User research interviews for navigation redesign',           'type' => 'Task',  'priority' => 'Medium', 'sp' => 5,  'done' => true,  'days_ago_created' => 38, 'cycle_days' => 5],
                ['summary' => 'Accessibility audit: WCAG 2.1 AA compliance',               'type' => 'Task',  'priority' => 'High',   'sp' => 5,  'done' => true,  'days_ago_created' => 30, 'cycle_days' => 4],
                ['summary' => 'Icon inconsistency bug: logout icon shows wrong state',     'type' => 'Bug',   'priority' => 'Low',    'sp' => 1,  'done' => true,  'days_ago_created' => 25, 'cycle_days' => 2],
                ['summary' => 'Prototype high-fidelity employee dashboard in Figma',        'type' => 'Story', 'priority' => 'Medium', 'sp' => 5,  'done' => false, 'days_ago_created' => 12, 'cycle_days' => null],
                ['summary' => 'Design notification centre UX patterns',                    'type' => 'Task',  'priority' => 'Medium', 'sp' => 3,  'done' => false, 'days_ago_created' => 7,  'cycle_days' => null],
            ],

            // ── Program Manager ──────────────────────────────────────────────────
            16 => [
                ['summary' => 'Define Sprint 14 scope and acceptance criteria',             'type' => 'Story', 'priority' => 'High',   'sp' => 5,  'done' => true,  'days_ago_created' => 40, 'cycle_days' => 3],
                ['summary' => 'Unblock Aman on data pipeline Kafka dependency',             'type' => 'Task',  'priority' => 'High',   'sp' => 2,  'done' => true,  'days_ago_created' => 36, 'cycle_days' => 1],
                ['summary' => 'Sprint retrospective action items — sprint 13',              'type' => 'Task',  'priority' => 'Medium', 'sp' => 2,  'done' => true,  'days_ago_created' => 32, 'cycle_days' => 2],
                ['summary' => 'Coordinate cross-team dependency on auth service release',   'type' => 'Task',  'priority' => 'High',   'sp' => 3,  'done' => true,  'days_ago_created' => 28, 'cycle_days' => 3],
                ['summary' => 'Review and approve API contract changes before release',     'type' => 'Task',  'priority' => 'High',   'sp' => 2,  'done' => true,  'days_ago_created' => 22, 'cycle_days' => 2],
                ['summary' => 'Sprint 15 planning and capacity analysis',                   'type' => 'Story', 'priority' => 'High',   'sp' => 3,  'done' => true,  'days_ago_created' => 18, 'cycle_days' => 2],
                ['summary' => 'Risk register update for Q1 delivery milestone',            'type' => 'Task',  'priority' => 'Medium', 'sp' => 2,  'done' => true,  'days_ago_created' => 14, 'cycle_days' => 2],
                ['summary' => 'Prepare stakeholder demo for board review',                 'type' => 'Task',  'priority' => 'High',   'sp' => 3,  'done' => false, 'days_ago_created' => 6,  'cycle_days' => null],
                ['summary' => 'Sprint 16 planning session',                                'type' => 'Story', 'priority' => 'High',   'sp' => 3,  'done' => false, 'days_ago_created' => 2,  'cycle_days' => null],
            ],
        ];
    }

    public function handle(): int
    {
        $this->info('Nalam Systems — Jira task seeder');
        $this->line('');

        $conn = JiraConnection::where('organization_id', self::ORG_ID)
            ->where('is_active', true)
            ->first();

        if (!$conn) {
            $this->error('No active Jira connection found for Nalam Systems (org_id=' . self::ORG_ID . ')');
            return Command::FAILURE;
        }

        $this->info("Jira: {$conn->jira_base_url}");
        $this->info("User: {$conn->jira_email}");
        $this->line('');

        // Resolve Jira project key
        $projectKey = $this->resolveProjectKey($conn);
        if (!$projectKey) {
            return Command::FAILURE;
        }
        $this->info("Project key: {$projectKey}");
        $this->line('');

        // Resolve status IDs (Done, In Progress, To Do) for this project
        $statusMap = $this->resolveStatusMap($conn, $projectKey);
        $this->info('Status map: ' . implode(', ', array_map(
            fn($k, $v) => "{$k}={$v}",
            array_keys($statusMap),
            array_values($statusMap)
        )));
        $this->line('');

        $dryRun   = $this->option('dry-run');
        $created  = 0;
        $skipped  = 0;
        $dbPatch  = 0;
        $blueprints = $this->taskBlueprints();

        foreach (self::EMPLOYEES as $empId => $emp) {
            if (!isset($blueprints[$empId])) {
                continue;
            }

            $this->line("<comment>▶ {$emp['name']} ({$emp['role']}) — employee #{$empId}</comment>");

            foreach ($blueprints[$empId] as $task) {
                $summary = $task['summary'];

                // Check if task already exists in our DB (by title + employee)
                $existing = EmployeeTask::where('employee_id', $empId)
                    ->where('title', $summary)
                    ->first();

                if ($existing) {
                    $this->line("  <comment>↻ Already exists (external_id: {$existing->external_id}) — patching DB metadata</comment>");

                    if (!$dryRun) {
                        $createdAt = now()->subDays($task['days_ago_created'])->startOfDay();
                        $completedAt = null;
                        if ($task['done'] && $task['cycle_days']) {
                            $completedAt = $createdAt->copy()->addDays($task['cycle_days']);
                        }

                        $existing->update([
                            'status'            => $task['done'] ? 'Done' : ($task['cycle_days'] === null && $task['days_ago_created'] < 14 ? 'In Progress' : 'To Do'),
                            'story_points'      => $task['sp'],
                            'task_type'         => $task['type'],
                            'priority'          => $task['priority'],
                            'source_created_at' => $createdAt,
                            'completed_at'      => $completedAt,
                        ]);
                        $dbPatch++;
                    }

                    $skipped++;
                    continue;
                }

                // Build Jira issue payload
                $payload = $this->buildIssuePayload($summary, $task, $emp, $projectKey);

                if ($dryRun) {
                    $this->line("  <info>Would create: [{$task['type']}] {$summary}</info>");
                    $created++;
                    continue;
                }

                // Create in Jira
                $response = Http::withBasicAuth($conn->jira_email, $conn->jira_api_token)
                    ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                    ->timeout(30)
                    ->post($conn->jira_base_url . '/rest/api/3/issue', $payload);

                if (!$response->successful()) {
                    $this->warn("  ✗ Failed to create: {$summary}");
                    $this->warn("    " . $response->status() . ': ' . substr($response->body(), 0, 200));
                    continue;
                }

                $issueKey = $response->json('key');
                $issueId  = $response->json('id');
                $this->line("  <info>✓ Created {$issueKey}: {$summary}</info>");

                // Transition to Done if needed
                if ($task['done'] && !empty($statusMap['Done'])) {
                    $this->transitionIssue($conn, $issueKey, $statusMap['Done']);
                }

                // Upsert into employee_tasks with historical dates
                $createdAt   = now()->subDays($task['days_ago_created'])->startOfDay();
                $completedAt = null;
                if ($task['done'] && $task['cycle_days']) {
                    $completedAt = $createdAt->copy()->addDays($task['cycle_days']);
                }

                EmployeeTask::updateOrCreate(
                    ['employee_id' => $empId, 'source_type' => 'jira', 'external_id' => $issueKey],
                    [
                        'organization_id'   => self::ORG_ID,
                        'title'             => $summary,
                        'task_type'         => $task['type'],
                        'status'            => $task['done'] ? 'Done' : 'To Do',
                        'priority'          => $task['priority'],
                        'story_points'      => $task['sp'],
                        'assignee_email'    => $emp['email'],
                        'source_created_at' => $createdAt,
                        'completed_at'      => $completedAt,
                        'metadata'          => ['jira_id' => $issueId, 'seeded' => true],
                    ]
                );

                $created++;
            }
        }

        $this->line('');
        $this->info("Done! Created: {$created} | Skipped (already existed): {$skipped} | DB patches: {$dbPatch}");
        $this->line('');
        $this->comment('Now run: php artisan employees:sync-jira-all  (or click Sync Jira Tasks per employee)');

        return Command::SUCCESS;
    }

    private function resolveProjectKey(JiraConnection $conn): ?string
    {
        $response = Http::withBasicAuth($conn->jira_email, $conn->jira_api_token)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(15)
            ->get($conn->jira_base_url . '/rest/api/3/project/search', ['maxResults' => 50]);

        if (!$response->successful()) {
            $this->error('Failed to fetch projects: ' . $response->status() . ' ' . substr($response->body(), 0, 200));
            return null;
        }

        $projects = $response->json('values', []);
        if (empty($projects)) {
            $this->error('No projects found in this Jira instance.');
            return null;
        }

        // Prefer SCRUM project (existing tasks use SCRUM-XX keys)
        foreach ($projects as $p) {
            if ($p['key'] === 'SCRUM') {
                return 'SCRUM';
            }
        }

        // Return first project
        $first = $projects[0];
        $this->warn("SCRUM project not found, using: {$first['name']} ({$first['key']})");
        return $first['key'];
    }

    private function resolveStatusMap(JiraConnection $conn, string $projectKey): array
    {
        $response = Http::withBasicAuth($conn->jira_email, $conn->jira_api_token)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(15)
            ->get($conn->jira_base_url . '/rest/api/3/project/' . $projectKey . '/statuses');

        $map = [];
        if (!$response->successful()) {
            return $map;
        }

        // Each entry = issue type with its statuses
        foreach ($response->json() as $issueType) {
            foreach ($issueType['statuses'] ?? [] as $status) {
                $name = $status['name'];
                $id   = $status['id'];
                if (!isset($map[$name])) {
                    $map[$name] = $id;
                }
            }
        }

        return $map;
    }

    private function transitionIssue(JiraConnection $conn, string $issueKey, string $transitionId): void
    {
        // First get available transitions for this issue
        $transResp = Http::withBasicAuth($conn->jira_email, $conn->jira_api_token)
            ->withHeaders(['Accept' => 'application/json'])
            ->timeout(15)
            ->get($conn->jira_base_url . '/rest/api/3/issue/' . $issueKey . '/transitions');

        if (!$transResp->successful()) {
            return;
        }

        // Find the "Done" transition
        $doneTransition = null;
        foreach ($transResp->json('transitions', []) as $t) {
            if (strtolower($t['to']['name'] ?? '') === 'done') {
                $doneTransition = $t['id'];
                break;
            }
        }

        if (!$doneTransition) {
            return;
        }

        Http::withBasicAuth($conn->jira_email, $conn->jira_api_token)
            ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
            ->timeout(15)
            ->post($conn->jira_base_url . '/rest/api/3/issue/' . $issueKey . '/transitions', [
                'transition' => ['id' => $doneTransition],
            ]);
    }

    private function buildIssuePayload(string $summary, array $task, array $emp, string $projectKey): array
    {
        // Map task types to what the SCRUM project supports (Bug→Task with label)
        $jiraTypeName = match ($task['type']) {
            'Bug'   => 'Task',
            'Story' => 'Story',
            default => 'Task',
        };

        $labels = ($task['type'] === 'Bug') ? ['Bug'] : [];

        $payload = [
            'fields' => [
                'project'   => ['key' => $projectKey],
                'summary'   => $summary,
                'issuetype' => ['name' => $jiraTypeName],
                'priority'  => ['name' => $task['priority']],
                'labels'    => $labels,
                'description' => [
                    'type'    => 'doc',
                    'version' => 1,
                    'content' => [[
                        'type'    => 'paragraph',
                        'content' => [['type' => 'text', 'text' => $summary]],
                    ]],
                ],
            ],
        ];

        // Assignee (skip if pm@ — that user may not exist in Jira)
        if (!str_contains($emp['email'], 'pm@')) {
            $payload['fields']['assignee'] = ['emailAddress' => $emp['email']];
        }

        // Story points: customfield_10016 only (not 'story_points' which is invalid)
        if ($task['sp'] !== null) {
            $payload['fields']['customfield_10016'] = (float) $task['sp'];
        }

        return $payload;
    }
}
