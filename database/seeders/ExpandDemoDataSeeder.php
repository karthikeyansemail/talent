<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Expands demo data for a realistic portal demo:
 * - Updates Nalam Jira tasks from "To Do" to realistic statuses
 * - Adds 3 months of sprint data for Nalam employees (9-16)
 * - Adds Slack signals for Acme employees 3-5 (who had none)
 * - Adds recent Slack signal weeks for all employees
 *
 * Safe to re-run — cleans up before inserting.
 * Run with: php artisan db:seed --class=ExpandDemoDataSeeder
 */
class ExpandDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Expanding demo data...');

        $this->updateNalamJiraTasks();
        $this->seedNalamSprintTasks();
        $this->seedAcmeSlackSignals();
        $this->seedNalamSlackExpansion();

        $this->command->info('Demo data expansion complete!');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 1. Fix existing Nalam Jira tasks — move from "To Do" to Done/In Progress
    // ═══════════════════════════════════════════════════════════════════════

    private function updateNalamJiraTasks(): void
    {
        $this->command->info('  Updating Nalam Jira task statuses...');

        // Update employee_jira_tasks — make most "Done" with resolution
        $taskUpdates = [
            // Rahul Kumar (emp 9)
            'SCRUM-10' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-15 14:30:00', 'story_points' => 5],
            'SCRUM-11' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-18 11:00:00', 'story_points' => 3],
            'SCRUM-39' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-01-22 16:45:00', 'story_points' => 3],

            // David Kim (emp 10)
            'SCRUM-12' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-10 10:00:00', 'story_points' => 5],
            'SCRUM-13' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-14 15:20:00', 'story_points' => 3],
            'SCRUM-14' => ['status' => 'In Progress', 'resolution' => null, 'resolved_at' => null, 'story_points' => 8],
            'SCRUM-40' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-22 09:30:00', 'story_points' => 5],

            // Aman Verma (emp 11)
            'SCRUM-15' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-08 17:00:00', 'story_points' => 8],
            'SCRUM-16' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-12 12:00:00', 'story_points' => 3],
            'SCRUM-17' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-20 14:00:00', 'story_points' => 5],
            'SCRUM-18' => ['status' => 'In Progress', 'resolution' => null, 'resolved_at' => null, 'story_points' => 5],

            // Sara Lim (emp 12)
            'SCRUM-19' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-05 11:30:00', 'story_points' => 5],
            'SCRUM-20' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-11 16:00:00', 'story_points' => 3],
            'SCRUM-21' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-19 10:15:00', 'story_points' => 8],

            // Anita Patel (emp 13)
            'SCRUM-22' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-07 09:00:00', 'story_points' => 3],
            'SCRUM-23' => ['status' => 'Done', 'resolution' => 'Done', 'resolved_at' => '2026-02-14 11:30:00', 'story_points' => 2],
            'SCRUM-24' => ['status' => 'In Progress', 'resolution' => null, 'resolved_at' => null, 'story_points' => 3],
        ];

        foreach ($taskUpdates as $key => $data) {
            DB::table('employee_jira_tasks')
                ->where('jira_task_key', $key)
                ->update($data);
        }

        // Also update the corresponding employee_tasks entries
        foreach ($taskUpdates as $key => $data) {
            DB::table('employee_tasks')
                ->where('external_id', $key)
                ->update([
                    'status'       => $data['status'],
                    'story_points' => $data['story_points'],
                    'completed_at' => $data['resolved_at'],
                    'metadata'     => json_encode(['resolution' => $data['resolution'], 'components' => []]),
                ]);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 2. Add realistic sprint tasks for Nalam employees across 3 months
    // ═══════════════════════════════════════════════════════════════════════

    private function seedNalamSprintTasks(): void
    {
        $this->command->info('  Seeding Nalam sprint task data...');
        $orgId = 3;

        // Clean up previously seeded expansion data
        DB::table('employee_tasks')
            ->where('organization_id', $orgId)
            ->where('external_id', 'LIKE', 'NALAM-%')
            ->delete();

        $rows = [];

        // ── Rahul Kumar (9) — Senior Full Stack Dev — strong performer ──
        $rows = array_merge($rows, $this->makePeriodTasks(9, $orgId, '2025-12', [
            ['Build tenant isolation middleware',                 'Story', 'High',   8,  'Done',        10],
            ['Fix SQL injection in search endpoint',             'Bug',   'High',   3,  'Done',         2],
            ['Implement organization onboarding wizard',         'Story', 'High',  13,  'Done',        14],
            ['Add database connection pooling',                  'Task',  'Medium', 5,  'Done',         6],
            ['Fix timezone handling in scheduled jobs',          'Bug',   'Medium', 3,  'Done',         4],
            ['Write migration scripts for v2 schema',            'Task',  'Medium', 5,  'Done',         5],
            ['Review David\'s React component PRs',              'Task',  'Low',    2,  'Done',         1],
            ['Set up CI/CD pipeline for staging',                'Task',  'Medium', 3,  'In Progress', null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(9, $orgId, '2026-01', [
            ['Implement real-time notifications via WebSocket',  'Story', 'High',   8,  'Done',         9],
            ['Fix memory leak in queue worker',                  'Bug',   'High',   5,  'Done',         3],
            ['Add Redis caching for dashboard queries',          'Story', 'Medium', 5,  'Done',         5],
            ['Build employee bulk import API',                   'Story', 'High',   8,  'Done',        11],
            ['Fix race condition in concurrent session handling','Bug',   'High',   5,  'Done',         4],
            ['Optimize N+1 query in employee listing',           'Bug',   'Medium', 3,  'Done',         2],
            ['Add API rate limiting with throttle middleware',   'Task',  'Medium', 3,  'Done',         3],
            ['Document API endpoints in OpenAPI 3.0',            'Task',  'Low',    3,  'In Progress', null],
            ['Research GraphQL vs REST for v3 API',              'Task',  'Low',    2,  'To Do',       null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(9, $orgId, '2026-02', [
            ['Build multi-tenant SSO integration',               'Story', 'High',  13,  'Done',        12],
            ['Fix OAuth callback URL mismatch',                  'Bug',   'High',   2,  'Done',         1],
            ['Implement password policy enforcement',            'Story', 'Medium', 5,  'Done',         5],
            ['Add audit logging for admin actions',              'Story', 'Medium', 5,  'Done',         6],
            ['Fix file upload size limit error',                 'Bug',   'Medium', 2,  'Done',         2],
            ['Optimize slow dashboard API response',             'Task',  'High',   5,  'Done',         4],
            ['Set up automated DB backups',                      'Task',  'Medium', 3,  'Done',         3],
            ['Build health check endpoint for monitoring',       'Task',  'Medium', 3,  'In Progress', null],
            ['Implement role-based menu visibility',             'Story', 'Medium', 5,  'In Progress', null],
        ]));

        // ── David Kim (10) — Frontend Developer — improving over time ──
        $rows = array_merge($rows, $this->makePeriodTasks(10, $orgId, '2025-12', [
            ['Build responsive dashboard layout',                'Story', 'High',   8,  'Done',        12],
            ['Fix CSS grid overflow on mobile',                  'Bug',   'Medium', 2,  'Done',         3],
            ['Implement dark mode toggle',                       'Story', 'Medium', 5,  'Done',         7],
            ['Create reusable data table component',             'Task',  'Medium', 5,  'Done',         8],
            ['Fix form validation UX inconsistencies',           'Bug',   'Medium', 3,  'Done',         4],
            ['Add loading skeleton animations',                  'Task',  'Low',    2,  'Done',         3],
            ['Integrate Chart.js for analytics widgets',         'Task',  'Medium', 3,  'In Progress', null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(10, $orgId, '2026-01', [
            ['Build drag-and-drop kanban board',                 'Story', 'High',  13,  'Done',        14],
            ['Fix React state management in forms',              'Bug',   'Medium', 3,  'Done',         3],
            ['Implement infinite scroll for lead lists',         'Story', 'Medium', 5,  'Done',         5],
            ['Add keyboard shortcuts for navigation',            'Task',  'Low',    3,  'Done',         4],
            ['Fix timezone display in date pickers',             'Bug',   'Medium', 2,  'Done',         2],
            ['Create employee profile card component',           'Task',  'Medium', 3,  'Done',         3],
            ['Build notification center UI',                     'Story', 'Medium', 5,  'In Progress', null],
            ['Add accessibility improvements (WCAG 2.1)',        'Task',  'Medium', 5,  'To Do',       null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(10, $orgId, '2026-02', [
            ['Implement real-time chat UI with WebSocket',       'Story', 'High',  13,  'Done',        11],
            ['Fix button alignment across all forms',            'Bug',   'Low',    2,  'Done',         1],
            ['Build interview scheduling calendar view',         'Story', 'High',   8,  'Done',         9],
            ['Add file upload drag-and-drop zone',              'Task',  'Medium', 3,  'Done',         4],
            ['Fix memory leak in React useEffect cleanup',       'Bug',   'High',   3,  'Done',         2],
            ['Create responsive sidebar navigation',             'Task',  'Medium', 5,  'Done',         5],
            ['Implement toast notification system',              'Task',  'Medium', 3,  'Done',         3],
            ['Build project timeline Gantt component',           'Story', 'Medium', 8,  'In Progress', null],
        ]));

        // ── Aman Verma (11) — Backend Developer — steady performer ──
        $rows = array_merge($rows, $this->makePeriodTasks(11, $orgId, '2025-12', [
            ['Build email notification service',                 'Story', 'High',   8,  'Done',         9],
            ['Fix database deadlock in order processing',        'Bug',   'High',   5,  'Done',         5],
            ['Implement Stripe webhook handler',                 'Story', 'High',   8,  'Done',        10],
            ['Add request logging middleware',                   'Task',  'Medium', 3,  'Done',         3],
            ['Fix password reset token expiry logic',            'Bug',   'Medium', 2,  'Done',         2],
            ['Write unit tests for billing module',              'Task',  'Medium', 5,  'In Progress', null],
            ['Add CSV export for customer data',                 'Task',  'Low',    3,  'To Do',       null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(11, $orgId, '2026-01', [
            ['Build Razorpay payment integration',               'Story', 'High',   8,  'Done',        10],
            ['Fix invoice PDF generation encoding',              'Bug',   'Medium', 3,  'Done',         3],
            ['Implement subscription lifecycle management',      'Story', 'High',  13,  'Done',        14],
            ['Add automated email reminders for renewals',       'Task',  'Medium', 5,  'Done',         5],
            ['Fix currency conversion rounding errors',          'Bug',   'Medium', 2,  'Done',         2],
            ['Build usage metrics collection pipeline',          'Task',  'Medium', 5,  'Done',         6],
            ['Add webhook signature verification',               'Task',  'Medium', 3,  'In Progress', null],
            ['Implement coupon/discount code system',            'Story', 'Low',    5,  'To Do',       null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(11, $orgId, '2026-02', [
            ['Build multi-currency billing support',             'Story', 'High',  13,  'Done',        12],
            ['Fix Stripe checkout session redirect loop',        'Bug',   'High',   3,  'Done',         2],
            ['Implement refund processing workflow',             'Story', 'Medium', 8,  'Done',         8],
            ['Add billing history PDF download',                 'Task',  'Medium', 3,  'Done',         3],
            ['Fix tax calculation for Indian customers',         'Bug',   'Medium', 3,  'Done',         3],
            ['Build payment retry logic for failed charges',     'Task',  'Medium', 5,  'Done',         5],
            ['Implement proration for plan upgrades',            'Story', 'Medium', 8,  'In Progress', null],
            ['Add Slack notifications for payment events',       'Task',  'Low',    3,  'In Progress', null],
        ]));

        // ── Sara Lim (12) — UI/UX Designer — creative work patterns ──
        $rows = array_merge($rows, $this->makePeriodTasks(12, $orgId, '2025-12', [
            ['Design hiring pipeline dashboard mockups',         'Story', 'High',   8,  'Done',        10],
            ['Fix icon inconsistencies across navigation',       'Bug',   'Low',    2,  'Done',         2],
            ['Create component library in Figma',                'Task',  'High',   8,  'Done',        12],
            ['Design employee profile page layout',              'Task',  'Medium', 5,  'Done',         5],
            ['Update brand color palette for dark mode',         'Task',  'Medium', 3,  'Done',         4],
            ['Design onboarding flow wireframes',                'Story', 'Medium', 5,  'In Progress', null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(12, $orgId, '2026-01', [
            ['Design AI analysis results visualization',         'Story', 'High',  13,  'Done',        14],
            ['Fix responsive layout issues in candidate view',   'Bug',   'Medium', 3,  'Done',         3],
            ['Create interview scheduling calendar design',      'Story', 'High',   8,  'Done',         9],
            ['Design notification center and toast styles',      'Task',  'Medium', 3,  'Done',         3],
            ['Build interactive prototype for demo',             'Task',  'High',   5,  'Done',         6],
            ['Design settings pages for integrations',           'Task',  'Medium', 3,  'Done',         4],
            ['Update typography scale for readability',           'Task',  'Low',    2,  'Done',         2],
            ['Design kanban board drag-drop interactions',       'Story', 'Medium', 5,  'In Progress', null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(12, $orgId, '2026-02', [
            ['Design Work Pulse signal intelligence dashboard',  'Story', 'High',  13,  'Done',        11],
            ['Fix color contrast issues for accessibility',      'Bug',   'Medium', 3,  'Done',         2],
            ['Design resource allocation match cards',           'Story', 'High',   8,  'Done',         8],
            ['Create illustration set for empty states',         'Task',  'Medium', 5,  'Done',         5],
            ['Design project timeline Gantt chart UI',           'Story', 'Medium', 8,  'Done',         9],
            ['Update loading animations for consistency',        'Task',  'Low',    2,  'Done',         2],
            ['Design billing and subscription management UI',   'Task',  'Medium', 5,  'In Progress', null],
            ['Create style guide documentation',                 'Task',  'Low',    3,  'To Do',       null],
        ]));

        // ── Anita Patel (13) — HR Specialist — lighter workload, process tasks ──
        $rows = array_merge($rows, $this->makePeriodTasks(13, $orgId, '2025-12', [
            ['Set up employee onboarding checklist template',    'Task',  'High',   5,  'Done',         5],
            ['Fix duplicate candidate detection logic',          'Bug',   'Medium', 3,  'Done',         4],
            ['Create hiring pipeline status workflow docs',      'Task',  'Medium', 3,  'Done',         3],
            ['Add bulk candidate import from CSV',               'Story', 'Medium', 5,  'Done',         6],
            ['Write interview scoring rubric templates',         'Task',  'Low',    2,  'Done',         3],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(13, $orgId, '2026-01', [
            ['Build candidate feedback collection form',         'Story', 'High',   5,  'Done',         6],
            ['Fix offer letter PDF template formatting',         'Bug',   'Medium', 2,  'Done',         2],
            ['Create employee skills assessment framework',      'Task',  'High',   5,  'Done',         5],
            ['Set up automated interview reminders',             'Task',  'Medium', 3,  'Done',         3],
            ['Add department-wise headcount tracking',           'Task',  'Medium', 3,  'Done',         4],
            ['Design new hire orientation schedule',             'Task',  'Low',    2,  'In Progress', null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(13, $orgId, '2026-02', [
            ['Build employee exit process workflow',             'Story', 'Medium', 5,  'Done',         5],
            ['Fix candidate status transition validation',       'Bug',   'Medium', 2,  'Done',         2],
            ['Create 360-degree review template',                'Task',  'Medium', 3,  'Done',         4],
            ['Add probation period tracking dashboard',          'Story', 'Medium', 5,  'Done',         6],
            ['Write HR policy documentation in-app',             'Task',  'Low',    3,  'Done',         4],
            ['Set up automated birthday/anniversary reminders',  'Task',  'Low',    2,  'In Progress', null],
        ]));

        // ── Program Manager (16) — planning and coordination ──
        $rows = array_merge($rows, $this->makePeriodTasks(16, $orgId, '2025-12', [
            ['Define Q1 2026 product roadmap priorities',         'Story', 'High',   5,  'Done',         5],
            ['Create sprint velocity tracking dashboard',        'Task',  'High',   5,  'Done',         6],
            ['Write acceptance criteria for SSO feature',        'Task',  'Medium', 3,  'Done',         3],
            ['Coordinate cross-team dependency mapping',         'Task',  'High',   5,  'Done',         4],
            ['Fix JIRA board filter configuration',              'Bug',   'Low',    1,  'Done',         1],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(16, $orgId, '2026-01', [
            ['Plan billing module development sprint',           'Story', 'High',   5,  'Done',         3],
            ['Define deployment pipeline requirements',          'Task',  'High',   5,  'Done',         4],
            ['Review and approve design system proposals',       'Task',  'Medium', 3,  'Done',         2],
            ['Create risk assessment for self-hosted offering',  'Task',  'High',   5,  'Done',         5],
            ['Write competitive analysis document',              'Task',  'Medium', 3,  'Done',         4],
            ['Plan user acceptance testing schedule',             'Task',  'Medium', 3,  'In Progress', null],
        ]));

        $rows = array_merge($rows, $this->makePeriodTasks(16, $orgId, '2026-02', [
            ['Coordinate v1.0.0 launch preparation',             'Story', 'High',   8,  'Done',         8],
            ['Define customer success metrics and KPIs',         'Task',  'High',   5,  'Done',         5],
            ['Plan demo environment setup for sales team',       'Task',  'Medium', 3,  'Done',         3],
            ['Write release notes for v1.0.0',                   'Task',  'Medium', 3,  'Done',         3],
            ['Fix project timeline calculations',                'Bug',   'Medium', 2,  'Done',         2],
            ['Create support escalation process document',       'Task',  'Medium', 3,  'In Progress', null],
            ['Plan Q2 2026 feature prioritization',              'Task',  'Medium', 5,  'To Do',       null],
        ]));

        // Bulk insert
        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('employee_tasks')->insert($chunk);
        }

        $this->command->info('    Inserted ' . count($rows) . ' sprint tasks for Nalam employees.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 3. Add Slack signals for Acme employees 3-5 (Carol, Dan, Eva)
    // ═══════════════════════════════════════════════════════════════════════

    private function seedAcmeSlackSignals(): void
    {
        $this->command->info('  Seeding Acme Slack signals for employees 3-5...');
        $orgId = 1;

        // Clean previous expansion data
        DB::table('employee_signals')
            ->whereIn('employee_id', [3, 4, 5])
            ->where('source_type', 'slack')
            ->delete();

        // Also add more weeks for employees 1-2
        DB::table('employee_signals')
            ->whereIn('employee_id', [1, 2])
            ->where('source_type', 'slack')
            ->whereIn('period', ['2026-W06', '2026-W07', '2026-W08', '2026-W09', '2026-W10'])
            ->delete();

        $rows = [];

        $signalData = [
            // Carol Davis (3) — Data Scientist — moderate communication, analytical
            3 => [
                '2026-W04' => ['messages_sent_count' => [42, 'count'], 'active_days_count' => [4, 'days'], 'unique_collaborators_count' => [7, 'count'], 'after_hours_message_pct' => [5.2, 'percent'], 'calls_count' => [2, 'count'], 'meetings_attended_count' => [4, 'count']],
                '2026-W05' => ['messages_sent_count' => [48, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [8, 'count'], 'after_hours_message_pct' => [3.8, 'percent'], 'calls_count' => [2, 'count'], 'meetings_attended_count' => [5, 'count']],
                '2026-W06' => ['messages_sent_count' => [51, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [9, 'count'], 'after_hours_message_pct' => [4.1, 'percent'], 'calls_count' => [3, 'count'], 'meetings_attended_count' => [4, 'count']],
                '2026-W07' => ['messages_sent_count' => [38, 'count'], 'active_days_count' => [4, 'days'], 'unique_collaborators_count' => [6, 'count'], 'after_hours_message_pct' => [7.6, 'percent'], 'calls_count' => [1, 'count'], 'meetings_attended_count' => [3, 'count']],
                '2026-W08' => ['messages_sent_count' => [55, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [10, 'count'], 'after_hours_message_pct' => [3.4, 'percent'], 'calls_count' => [3, 'count'], 'meetings_attended_count' => [5, 'count']],
                '2026-W09' => ['messages_sent_count' => [46, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [8, 'count'], 'after_hours_message_pct' => [4.8, 'percent'], 'calls_count' => [2, 'count'], 'meetings_attended_count' => [4, 'count']],
            ],
            // Dan Wilson (4) — Frontend Dev — social, collaborative
            4 => [
                '2026-W04' => ['messages_sent_count' => [73, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [14, 'count'], 'after_hours_message_pct' => [11.2, 'percent'], 'calls_count' => [4, 'count'], 'meetings_attended_count' => [6, 'count']],
                '2026-W05' => ['messages_sent_count' => [81, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [13, 'count'], 'after_hours_message_pct' => [9.8, 'percent'], 'calls_count' => [5, 'count'], 'meetings_attended_count' => [7, 'count']],
                '2026-W06' => ['messages_sent_count' => [68, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [11, 'count'], 'after_hours_message_pct' => [12.5, 'percent'], 'calls_count' => [3, 'count'], 'meetings_attended_count' => [5, 'count']],
                '2026-W07' => ['messages_sent_count' => [76, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [15, 'count'], 'after_hours_message_pct' => [8.9, 'percent'], 'calls_count' => [4, 'count'], 'meetings_attended_count' => [6, 'count']],
                '2026-W08' => ['messages_sent_count' => [84, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [16, 'count'], 'after_hours_message_pct' => [10.3, 'percent'], 'calls_count' => [5, 'count'], 'meetings_attended_count' => [8, 'count']],
                '2026-W09' => ['messages_sent_count' => [79, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [14, 'count'], 'after_hours_message_pct' => [7.5, 'percent'], 'calls_count' => [4, 'count'], 'meetings_attended_count' => [6, 'count']],
            ],
            // Eva Brown (5) — UX Designer — focused, fewer messages but high quality
            5 => [
                '2026-W04' => ['messages_sent_count' => [35, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [9, 'count'], 'after_hours_message_pct' => [2.1, 'percent'], 'calls_count' => [2, 'count'], 'meetings_attended_count' => [5, 'count']],
                '2026-W05' => ['messages_sent_count' => [41, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [10, 'count'], 'after_hours_message_pct' => [3.0, 'percent'], 'calls_count' => [3, 'count'], 'meetings_attended_count' => [4, 'count']],
                '2026-W06' => ['messages_sent_count' => [38, 'count'], 'active_days_count' => [4, 'days'], 'unique_collaborators_count' => [8, 'count'], 'after_hours_message_pct' => [1.8, 'percent'], 'calls_count' => [2, 'count'], 'meetings_attended_count' => [4, 'count']],
                '2026-W07' => ['messages_sent_count' => [44, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [11, 'count'], 'after_hours_message_pct' => [2.5, 'percent'], 'calls_count' => [3, 'count'], 'meetings_attended_count' => [5, 'count']],
                '2026-W08' => ['messages_sent_count' => [37, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [9, 'count'], 'after_hours_message_pct' => [1.5, 'percent'], 'calls_count' => [2, 'count'], 'meetings_attended_count' => [4, 'count']],
                '2026-W09' => ['messages_sent_count' => [42, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [10, 'count'], 'after_hours_message_pct' => [2.8, 'percent'], 'calls_count' => [3, 'count'], 'meetings_attended_count' => [5, 'count']],
            ],
            // Extend Alice (1) and Bob (2) with more weeks
            1 => [
                '2026-W06' => ['messages_sent_count' => [91, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [14, 'count'], 'after_hours_message_pct' => [7.1, 'percent'], 'calls_count' => [3, 'count'], 'meetings_attended_count' => [6, 'count']],
                '2026-W07' => ['messages_sent_count' => [85, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [13, 'count'], 'after_hours_message_pct' => [5.8, 'percent'], 'calls_count' => [4, 'count'], 'meetings_attended_count' => [7, 'count']],
                '2026-W08' => ['messages_sent_count' => [102, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [16, 'count'], 'after_hours_message_pct' => [8.2, 'percent'], 'calls_count' => [5, 'count'], 'meetings_attended_count' => [8, 'count']],
                '2026-W09' => ['messages_sent_count' => [94, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [15, 'count'], 'after_hours_message_pct' => [6.4, 'percent'], 'calls_count' => [4, 'count'], 'meetings_attended_count' => [7, 'count']],
                '2026-W10' => ['messages_sent_count' => [88, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [14, 'count'], 'after_hours_message_pct' => [5.9, 'percent'], 'calls_count' => [3, 'count'], 'meetings_attended_count' => [6, 'count']],
            ],
            2 => [
                '2026-W06' => ['messages_sent_count' => [55, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [17, 'count'], 'after_hours_message_pct' => [13.1, 'percent'], 'calls_count' => [7, 'count'], 'meetings_attended_count' => [13, 'count']],
                '2026-W07' => ['messages_sent_count' => [61, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [19, 'count'], 'after_hours_message_pct' => [10.5, 'percent'], 'calls_count' => [8, 'count'], 'meetings_attended_count' => [14, 'count']],
                '2026-W08' => ['messages_sent_count' => [53, 'count'], 'active_days_count' => [4, 'days'], 'unique_collaborators_count' => [15, 'count'], 'after_hours_message_pct' => [15.2, 'percent'], 'calls_count' => [6, 'count'], 'meetings_attended_count' => [11, 'count']],
                '2026-W09' => ['messages_sent_count' => [64, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [18, 'count'], 'after_hours_message_pct' => [12.0, 'percent'], 'calls_count' => [9, 'count'], 'meetings_attended_count' => [15, 'count']],
                '2026-W10' => ['messages_sent_count' => [59, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [16, 'count'], 'after_hours_message_pct' => [11.3, 'percent'], 'calls_count' => [7, 'count'], 'meetings_attended_count' => [12, 'count']],
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

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('employee_signals')->insert($chunk);
        }

        $this->command->info('    Inserted ' . count($rows) . ' Slack signal records for Acme employees.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 4. Expand Nalam Slack signals with more recent weeks
    // ═══════════════════════════════════════════════════════════════════════

    private function seedNalamSlackExpansion(): void
    {
        $this->command->info('  Expanding Nalam Slack signal data...');
        $orgId = 3;

        // Add W10 for Nalam employees who only had up to W09
        DB::table('employee_signals')
            ->where('organization_id', $orgId)
            ->where('source_type', 'slack')
            ->where('period', '2026-W10')
            ->delete();

        $rows = [];

        $nalamSignals = [
            // Rahul Kumar (9) — very active communicator
            9  => ['2026-W10' => ['messages_sent_count' => [95, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [14, 'count'], 'after_hours_message_pct' => [12.1, 'percent'], 'calls_count' => [5, 'count'], 'meetings_attended_count' => [8, 'count']]],
            // David Kim (10) — moderate
            10 => ['2026-W10' => ['messages_sent_count' => [62, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [10, 'count'], 'after_hours_message_pct' => [6.5, 'percent'], 'calls_count' => [3, 'count'], 'meetings_attended_count' => [5, 'count']]],
            // Aman Verma (11) — focused, less social
            11 => ['2026-W10' => ['messages_sent_count' => [48, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [8, 'count'], 'after_hours_message_pct' => [3.2, 'percent'], 'calls_count' => [2, 'count'], 'meetings_attended_count' => [4, 'count']]],
            // Sara Lim (12) — creative, collaborative
            12 => ['2026-W10' => ['messages_sent_count' => [58, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [11, 'count'], 'after_hours_message_pct' => [4.8, 'percent'], 'calls_count' => [4, 'count'], 'meetings_attended_count' => [6, 'count']]],
            // Anita Patel (13) — process-oriented
            13 => ['2026-W10' => ['messages_sent_count' => [34, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [9, 'count'], 'after_hours_message_pct' => [1.5, 'percent'], 'calls_count' => [2, 'count'], 'meetings_attended_count' => [4, 'count']]],
            // HR Manager (15)
            15 => ['2026-W10' => ['messages_sent_count' => [28, 'count'], 'active_days_count' => [4, 'days'], 'unique_collaborators_count' => [7, 'count'], 'after_hours_message_pct' => [2.0, 'percent'], 'calls_count' => [2, 'count'], 'meetings_attended_count' => [3, 'count']]],
            // Program Manager (16)
            16 => ['2026-W10' => ['messages_sent_count' => [71, 'count'], 'active_days_count' => [5, 'days'], 'unique_collaborators_count' => [16, 'count'], 'after_hours_message_pct' => [9.5, 'percent'], 'calls_count' => [6, 'count'], 'meetings_attended_count' => [10, 'count']]],
        ];

        foreach ($nalamSignals as $empId => $periods) {
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

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('employee_signals')->insert($chunk);
        }

        $this->command->info('    Inserted ' . count($rows) . ' Nalam Slack signal records.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function makePeriodTasks(int $empId, int $orgId, string $period, array $tasks): array
    {
        [$year, $month] = explode('-', $period);
        $rows = [];
        $day = 1;

        foreach ($tasks as $i => $t) {
            [$title, $type, $priority, $sp, $status, $cycleDays] = $t;

            $createdAt = Carbon::create((int)$year, (int)$month, min($day + $i * 2, 28));
            $completedAt = ($status === 'Done' && $cycleDays !== null)
                ? $createdAt->copy()->addDays((int)$cycleDays)
                : null;

            $rows[] = [
                'employee_id'      => $empId,
                'organization_id'  => $orgId,
                'connection_id'    => null,
                'source_type'      => 'jira',
                'external_id'      => "NALAM-{$empId}-{$period}-{$i}",
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
}
