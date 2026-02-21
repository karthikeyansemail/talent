<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\JiraConnection;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

/**
 * Creates a Jira project, board, sprint, and realistic tasks in the real
 * Atlassian Cloud instance, then triggers a sync back to the application.
 *
 * Run: php artisan db:seed --class=NalamJiraTasksSeeder
 */
class NalamJiraTasksSeeder extends Seeder
{
    private string $baseUrl  = 'https://nalamsystems.atlassian.net';
    private string $email    = 'rahul.kumar@nalamsystems.work';
    private string $token    = 'ATATT3xFfGF0Mj_lsdKT3sBuqeVXelwrhqxyTmBOJ-RagtDO1ePH7BiOM_jSPNhjVqH8aZ05O-fYojbF8lA8Otr3QZlD5L5v_XY5kizx5N8_inky6_ITiS8jBbcvQrgbrjx1iTYTcZVUunr-3zEoCSl4oPF_8BwTEirT1GWPbvPFwZ4GgCAxUMU=ACD85B75';

    private function jira(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withBasicAuth($this->email, $this->token)
            ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
            ->timeout(60);
    }

    public function run(): void
    {
        // ── Pre-resolved values (from initial discovery run) ──────────
        // accountIds resolved on first successful run — skip repeated API lookups
        $accountIds = [
            'rahul.kumar@nalamsystems.work' => '712020:1065a21e-8b36-4e71-919e-b71190a8fabc',
            'david.kim@nalamsystems.work'   => '712020:8c4981a0-dc1d-45f5-8c4b-47522cfa1029',
            'aman.verma@nalamsystems.work'  => '712020:3ab90dc2-0e81-44cd-8a43-cb0307684edf',
            'sara.lim@nalamsystems.work'    => '712020:21558d72-b8a3-410d-a0c4-03fb0268f619',
            'anita.patel@nalamsystems.work' => '712020:dbc1b73b-fca8-450b-8b32-b357efc82d18',
        ];

        $projectKey = 'SCRUM'; // Nalam Systems Team project
        $this->command->info("Using pre-resolved accountIds and project: {$projectKey}");

        // ── 4. Create issues (sprint tasks) ──────────────────────────
        $tasks = $this->buildSprintTasks($projectKey, $accountIds);
        $createdCount = 0;

        foreach ($tasks as $i => $task) {
            $summary = $task['fields']['summary'];
            $attempts = 0;
            $maxAttempts = 3;
            $created = false;

            while ($attempts < $maxAttempts && !$created) {
                $attempts++;
                try {
                    $res = $this->jira()->timeout(60)->post("{$this->baseUrl}/rest/api/3/issue", $task);
                    if ($res->successful()) {
                        $key = $res->json('key');
                        $this->command->line("  [{$i}] Created: {$key} — {$summary}");
                        $createdCount++;
                        $created = true;
                    } else {
                        $this->command->warn("  [{$i}] Failed: {$summary} — " . substr($res->body(), 0, 150));
                        break; // Don't retry API errors, only timeouts
                    }
                } catch (\Exception $e) {
                    $this->command->warn("  [{$i}] Attempt {$attempts}/{$maxAttempts} timeout: {$summary}");
                    if ($attempts < $maxAttempts) {
                        sleep(3); // Wait 3s before retry
                    }
                }
            }
            // Delay to avoid rate limiting
            usleep(800000); // 800ms
        }

        $this->command->info("Created {$createdCount} Jira issues in project {$projectKey}");
        $this->command->info('');
        $this->command->info('Next: Log into the app as admin@nalamsystems.work, go to Settings → Integrations,');
        $this->command->info('then sync employees to pull these tasks into the application.');
    }

    /**
     * Build realistic sprint tasks for a 3-sprint simulation.
     * Covers all three projects: Core API, Customer Dashboard, Data Pipeline.
     */
    private function buildSprintTasks(string $projectKey, array $accountIds): array
    {
        $rahul = $accountIds['rahul.kumar@nalamsystems.work'];
        $david = $accountIds['david.kim@nalamsystems.work'];
        $aman  = $accountIds['aman.verma@nalamsystems.work'];
        $sara  = $accountIds['sara.lim@nalamsystems.work'];
        $anita = $accountIds['anita.patel@nalamsystems.work'];

        $task = fn(string $summary, string $type, string $priority, ?string $assignee, int $points, string $status, array $labels = []) => [
            'fields' => [
                'project'   => ['key' => $projectKey],
                'summary'   => $summary,
                'issuetype' => ['name' => $type],
                'priority'  => ['name' => $priority],
                'assignee'  => $assignee ? ['accountId' => $assignee] : null,
                'labels'    => $labels,
                'description' => [
                    'type'    => 'doc',
                    'version' => 1,
                    'content' => [[
                        'type'    => 'paragraph',
                        'content' => [['type' => 'text', 'text' => "Sprint task: {$summary}"]],
                    ]],
                ],
            ],
        ];

        return [
            // ── Rahul Kumar (Senior Full Stack / Laravel) ─────────────
            $task('Design multi-tenant database schema for organization isolation', 'Story', 'High', $rahul, 8, 'Done', ['backend', 'database', 'architecture']),
            $task('Implement JWT authentication with refresh token rotation', 'Story', 'High', $rahul, 5, 'Done', ['backend', 'auth', 'security']),
            $task('Build RESTful CRUD endpoints for employee management', 'Task', 'Medium', $rahul, 5, 'Done', ['backend', 'api', 'laravel']),
            $task('Add role-based access control middleware (RBAC)', 'Story', 'High', $rahul, 3, 'Done', ['backend', 'security', 'laravel']),
            $task('Set up Redis caching layer for API responses', 'Task', 'Medium', $rahul, 3, 'In Progress', ['backend', 'performance', 'redis']),
            $task('Integrate queue workers for async AI processing jobs', 'Story', 'Medium', $rahul, 5, 'In Progress', ['backend', 'queues', 'laravel']),
            $task('Fix N+1 query issue in employee listing endpoint', 'Bug', 'High', $rahul, 2, 'Done', ['backend', 'performance', 'bug']),
            $task('Write API documentation using OpenAPI 3.0', 'Task', 'Low', $rahul, 3, 'To Do', ['documentation', 'api']),

            // ── David Kim (Frontend / React) ──────────────────────────
            $task('Build responsive dashboard layout with sidebar navigation', 'Story', 'High', $david, 5, 'Done', ['frontend', 'react', 'ui']),
            $task('Implement real-time data table with sorting and pagination', 'Story', 'High', $david, 8, 'Done', ['frontend', 'react', 'typescript']),
            $task('Create reusable form components with validation hooks', 'Task', 'Medium', $david, 5, 'Done', ['frontend', 'react', 'components']),
            $task('Add GraphQL client integration with Apollo', 'Story', 'Medium', $david, 5, 'In Progress', ['frontend', 'graphql', 'apollo']),
            $task('Implement dark mode with CSS custom properties', 'Task', 'Low', $david, 3, 'In Progress', ['frontend', 'css', 'ui']),
            $task('Write unit tests for dashboard components with Jest', 'Task', 'Medium', $david, 3, 'To Do', ['frontend', 'testing', 'jest']),
            $task('Fix layout overflow bug on mobile viewport', 'Bug', 'Medium', $david, 1, 'Done', ['frontend', 'bug', 'responsive']),
            $task('Integrate Storybook for component documentation', 'Task', 'Low', $david, 2, 'To Do', ['frontend', 'storybook', 'documentation']),

            // ── Aman Verma (Backend / Python / DevOps) ────────────────
            $task('Design FastAPI microservice for ML inference', 'Story', 'High', $aman, 8, 'Done', ['backend', 'python', 'fastapi', 'ml']),
            $task('Set up Celery distributed task queue with Redis broker', 'Task', 'High', $aman, 5, 'Done', ['backend', 'celery', 'redis', 'python']),
            $task('Write data ingestion pipeline for Jira webhook events', 'Story', 'Medium', $aman, 5, 'Done', ['backend', 'python', 'pipeline', 'jira']),
            $task('Containerise FastAPI service with Docker and docker-compose', 'Task', 'Medium', $aman, 3, 'Done', ['devops', 'docker', 'python']),
            $task('Deploy Kubernetes manifests for production workloads', 'Story', 'High', $aman, 8, 'In Progress', ['devops', 'kubernetes', 'infrastructure']),
            $task('Implement CI/CD pipeline with GitHub Actions', 'Task', 'Medium', $aman, 5, 'In Progress', ['devops', 'cicd', 'github-actions']),
            $task('Add health check and readiness probes to all services', 'Task', 'Low', $aman, 2, 'To Do', ['devops', 'kubernetes', 'monitoring']),
            $task('Debug memory leak in Celery worker after 24h uptime', 'Bug', 'High', $aman, 3, 'In Progress', ['backend', 'python', 'bug', 'performance']),

            // ── Sara Lim (UI/UX Designer) ─────────────────────────────
            $task('Create design system component library in Figma', 'Story', 'High', $sara, 8, 'Done', ['design', 'figma', 'design-system']),
            $task('Design onboarding flow wireframes for new users', 'Story', 'Medium', $sara, 5, 'Done', ['design', 'ux', 'wireframes']),
            $task('Conduct usability testing sessions with 5 stakeholders', 'Task', 'High', $sara, 3, 'Done', ['design', 'research', 'usability']),
            $task('Design mobile-responsive layouts for core screens', 'Story', 'Medium', $sara, 5, 'In Progress', ['design', 'figma', 'mobile', 'responsive']),
            $task('Create accessibility audit report and remediation plan', 'Task', 'Medium', $sara, 3, 'In Progress', ['design', 'accessibility', 'a11y']),
            $task('Redesign employee profile page with skills visualisation', 'Story', 'Low', $sara, 5, 'To Do', ['design', 'figma', 'ux']),
            $task('Update icon set and typography tokens in design system', 'Task', 'Low', $sara, 2, 'To Do', ['design', 'design-system', 'ui']),

            // ── Anita Patel (HR Specialist) ───────────────────────────
            $task('Create employee onboarding checklist and documentation', 'Task', 'High', $anita, 3, 'Done', ['hr', 'onboarding', 'documentation']),
            $task('Set up HRIS integration for employee data sync', 'Story', 'High', $anita, 5, 'Done', ['hr', 'hris', 'integration']),
            $task('Define role competency frameworks for engineering levels', 'Story', 'Medium', $anita, 5, 'In Progress', ['hr', 'performance', 'competency']),
            $task('Conduct Q1 performance review calibration sessions', 'Task', 'Medium', $anita, 3, 'In Progress', ['hr', 'performance', 'review']),
            $task('Draft remote work policy update for 2026', 'Task', 'Low', $anita, 2, 'To Do', ['hr', 'policy', 'documentation']),
            $task('Organise technical hiring panel training for 6 engineers', 'Task', 'Medium', $anita, 2, 'To Do', ['hr', 'hiring', 'training']),
        ];
    }
}
