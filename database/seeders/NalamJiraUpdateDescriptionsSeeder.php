<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

/**
 * Updates SCRUM-5 through SCRUM-41 with realistic descriptions and acceptance criteria.
 *
 * Run: php artisan db:seed --class=NalamJiraUpdateDescriptionsSeeder
 */
class NalamJiraUpdateDescriptionsSeeder extends Seeder
{
    private string $baseUrl = 'https://nalamsystems.atlassian.net';
    private string $email   = 'rahul.kumar@nalamsystems.work';
    private string $token   = '';

    private function jira(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withBasicAuth($this->email, $this->token)
            ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
            ->timeout(60);
    }

    // ── ADF helpers ──────────────────────────────────────────────────────────

    private function doc(array ...$content): array
    {
        return ['type' => 'doc', 'version' => 1, 'content' => $content];
    }

    private function h3(string $text): array
    {
        return ['type' => 'heading', 'attrs' => ['level' => 3],
                'content' => [['type' => 'text', 'text' => $text]]];
    }

    private function p(string $text): array
    {
        return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
    }

    private function bullets(string ...$items): array
    {
        $listItems = array_map(fn($t) => [
            'type'    => 'listItem',
            'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $t]]]],
        ], $items);
        return ['type' => 'bulletList', 'content' => $listItems];
    }

    // ── Main ─────────────────────────────────────────────────────────────────

    public function run(): void
    {
        $updates = $this->buildDescriptions();
        $ok = 0;
        $fail = 0;

        foreach ($updates as $key => $description) {
            try {
                $res = $this->jira()->put("{$this->baseUrl}/rest/api/3/issue/{$key}", [
                    'fields' => ['description' => $description],
                ]);

                if ($res->successful() || $res->status() === 204) {
                    $this->command->line("  Updated: {$key}");
                    $ok++;
                } else {
                    $this->command->warn("  Failed {$key}: " . substr($res->body(), 0, 120));
                    $fail++;
                }
            } catch (\Exception $e) {
                $this->command->warn("  Timeout on {$key}, retrying...");
                sleep(3);
                try {
                    $res = $this->jira()->put("{$this->baseUrl}/rest/api/3/issue/{$key}", [
                        'fields' => ['description' => $description],
                    ]);
                    if ($res->successful() || $res->status() === 204) {
                        $this->command->line("  Updated (retry): {$key}");
                        $ok++;
                    } else {
                        $fail++;
                    }
                } catch (\Exception $e2) {
                    $this->command->warn("  Skipped {$key}: {$e2->getMessage()}");
                    $fail++;
                }
            }
            usleep(700000); // 700ms between requests
        }

        $this->command->info("Done — {$ok} updated, {$fail} failed.");
        $this->command->info('Re-sync in the app to pull updated descriptions into employee_jira_tasks.');
    }

    private function buildDescriptions(): array
    {
        return [

            // ── Rahul Kumar — Backend / Laravel ──────────────────────────────

            'SCRUM-5' => $this->doc(
                $this->h3('Overview'),
                $this->p('Design the core database schema to support multiple independent organisations on a single platform instance. Each organisation\'s data must be fully isolated — no cross-tenant data leakage under any circumstance.'),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All business tables carry a non-nullable organization_id foreign key.',
                    'A global query scope is applied at the Model level to filter by the authenticated org automatically.',
                    'Database ERD is documented in Notion under Architecture > Data Model.',
                    'Composite indexes on (organization_id, created_at) for all high-volume tables.',
                    'Migration tested on a fresh schema with two isolated seed orgs — no data bleeds across.'
                ),
                $this->h3('Technical Notes'),
                $this->p('Use Laravel\'s global scopes rather than manual where clauses in every query. Avoid cross-schema joins; keep all tenant data in a single database with row-level isolation.')
            ),

            'SCRUM-6' => $this->doc(
                $this->h3('Overview'),
                $this->p('Replace the existing session-based auth with a stateless JWT flow. Access tokens should be short-lived; refresh tokens rotate on each use to limit replay-attack windows.'),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Access tokens expire after 15 minutes; refresh tokens after 30 days.',
                    'Each refresh token is single-use — a new pair is issued on every refresh call.',
                    'Revoked refresh tokens are stored in Redis with a TTL matching the expiry window.',
                    'Endpoint POST /auth/refresh returns 401 if the token has already been used.',
                    'Auth flow tested with Postman collection checked into /tests/postman.'
                ),
                $this->h3('Security Notes'),
                $this->p('Refresh tokens must be stored in HttpOnly cookies, never in localStorage. Access token payload: { sub, org_id, role, iat, exp }.')
            ),

            'SCRUM-7' => $this->doc(
                $this->h3('Overview'),
                $this->p('Expose a full CRUD API for the Employee resource. This is the primary data layer for HR workflows and must support filtering, sorting, and pagination for large organisations.'),
                $this->h3('Endpoints'),
                $this->bullets(
                    'GET    /api/v1/employees          — paginated list (default 25 per page)',
                    'POST   /api/v1/employees          — create employee',
                    'GET    /api/v1/employees/{id}     — retrieve single record',
                    'PUT    /api/v1/employees/{id}     — full update',
                    'PATCH  /api/v1/employees/{id}     — partial update',
                    'DELETE /api/v1/employees/{id}     — soft delete (sets is_active = false)'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All endpoints require authentication and return 403 for cross-org access.',
                    'List supports ?search=, ?department_id=, ?designation= query params.',
                    'Validation errors return RFC 7807 Problem Details JSON.',
                    'Response time < 200ms for list of 500 employees.'
                )
            ),

            'SCRUM-8' => $this->doc(
                $this->h3('Overview'),
                $this->p('Implement a role-based access control system via Laravel middleware. Roles are hierarchical — higher roles inherit all permissions of lower roles.'),
                $this->h3('Role Hierarchy'),
                $this->bullets(
                    'super_admin — full platform access across all organisations',
                    'org_admin — full access within their organisation',
                    'hr_manager — manage candidates, jobs, applications',
                    'hiring_manager — view candidates, submit interview feedback',
                    'resource_manager — manage employees, projects, resource matching',
                    'employee — view own profile only'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'CheckRole middleware rejects requests with a 403 and descriptive error message.',
                    'Route groups use ->middleware([\'auth\', \'role:hr_manager,org_admin\']) syntax.',
                    'Unit tests cover all 6 roles against all protected route groups.',
                    'Permission matrix documented in /docs/rbac-matrix.md.'
                )
            ),

            'SCRUM-9' => $this->doc(
                $this->h3('Overview'),
                $this->p('Layer a Redis caching strategy on top of the most expensive read queries — primarily the employee list, project list, and aggregated skill reports — to reduce database load under concurrent usage.'),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Employee list query cached for 5 minutes; invalidated on any employee create/update/delete.',
                    'Project resource matches cached for 10 minutes; invalidated when "Find Best Resources" runs.',
                    'Cache key includes organization_id and query fingerprint to prevent cross-tenant leakage.',
                    'Cache hit rate ≥ 80% measurable via Redis INFO keyspace_hits/keyspace_misses.',
                    'Cache bypassed when ?nocache=1 is present (admin debugging only).'
                ),
                $this->h3('Technical Notes'),
                $this->p('Use Laravel\'s Cache::tags() for grouped invalidation. All cached data must be serialisable — avoid caching Eloquent model instances directly.')
            ),

            'SCRUM-10' => $this->doc(
                $this->h3('Overview'),
                $this->p('Decouple slow AI processing from the HTTP request cycle by dispatching analysis jobs to Laravel queues. Users should receive an immediate response while processing happens asynchronously in the background.'),
                $this->h3('Jobs to Queue'),
                $this->bullets(
                    'AnalyzeResumeJob — triggered on resume upload',
                    'MatchProjectResourcesJob — triggered when "Find Best Resources" is clicked',
                    'SyncJiraTasksJob — triggered on manual sync or scheduled hourly',
                    'ComputeEmployeeSignalsJob — triggered nightly via scheduler'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All jobs implement ShouldQueue and are dispatched with ->onQueue(\'ai\').',
                    'Failed jobs retry up to 3 times with exponential back-off (5s, 30s, 5min).',
                    'Permanently failed jobs are written to the failed_jobs table.',
                    'Queue worker started via Supervisor on production; artisan queue:work locally.',
                    'Job status visible in AiProcessingLog (endpoint, status, processing_time_ms).'
                )
            ),

            'SCRUM-11' => $this->doc(
                $this->h3('Overview'),
                $this->p('Produce machine-readable API documentation using the OpenAPI 3.0 specification. Documentation must stay in sync with the actual implementation and be accessible to frontend developers without prior access to the codebase.'),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All 30+ endpoints documented with request/response schemas and example payloads.',
                    'Swagger UI served at /api/docs (dev and staging environments only).',
                    'Authentication flow documented with example JWT headers.',
                    'Error responses documented with all possible HTTP status codes per endpoint.',
                    'openapi.json checked into /docs/openapi.json and kept up to date via CI lint step.'
                )
            ),

            // ── David Kim — Frontend / React ─────────────────────────────────

            'SCRUM-12' => $this->doc(
                $this->h3('Overview'),
                $this->p('Build the primary application shell used by all authenticated pages. The layout must include a collapsible sidebar, a top navigation bar, a main content area, and a notification drawer.'),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Sidebar collapses to icon-only mode at < 1024px viewport width.',
                    'Active navigation item is highlighted with primary colour underline.',
                    'Mobile: hamburger menu opens a full-height overlay drawer.',
                    'Layout tested on Chrome, Firefox, Safari, and Edge at 375px, 768px, 1440px.',
                    'Keyboard navigation between sidebar items works with Tab and arrow keys.',
                    'No layout shift (CLS < 0.1) on initial page load.'
                )
            ),

            'SCRUM-13' => $this->doc(
                $this->h3('Overview'),
                $this->p('Implement a performant, feature-rich data table component to display employee and candidate lists. The table must handle hundreds of rows without jank and support server-side operations for large datasets.'),
                $this->h3('Features Required'),
                $this->bullets(
                    'Column sorting (click header to cycle asc → desc → default)',
                    'Server-side pagination with configurable page sizes (10, 25, 50, 100)',
                    'Row-level actions menu (View, Edit, Delete)',
                    'Sticky column headers on vertical scroll',
                    'Row selection checkboxes for bulk operations',
                    'Empty and loading skeleton states'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Renders 500 rows in < 50ms (measured via React Profiler).',
                    'Sort state preserved in URL query params for shareability.',
                    'Fully keyboard-navigable (arrow keys, Enter to open row).',
                    'Accessible: ARIA grid role, aria-sort attributes on sortable headers.'
                )
            ),

            'SCRUM-14' => $this->doc(
                $this->h3('Overview'),
                $this->p('Create a library of form primitives that enforce consistent validation UX across the application. All components must integrate with React Hook Form for performant, schema-driven forms.'),
                $this->h3('Components to Build'),
                $this->bullets(
                    'TextInput — with prefix/suffix icon, character count, error state',
                    'SelectInput — native and custom dropdown variants, async option loading',
                    'DatePicker — range selection, min/max constraints, locale-aware formatting',
                    'Textarea — auto-resize, markdown preview toggle',
                    'TagInput — create-on-enter, delete-on-backspace, max tag limit',
                    'FileUpload — drag-and-drop, preview, progress bar, type restrictions'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All components accept register() from React Hook Form.',
                    'Error messages display below field using aria-describedby for accessibility.',
                    'Each component has Storybook stories with all states (default, focus, error, disabled).',
                    'Validation runs on blur by default, on change after first submit attempt.'
                )
            ),

            'SCRUM-15' => $this->doc(
                $this->h3('Overview'),
                $this->p('Integrate Apollo Client to support GraphQL queries alongside existing REST endpoints. The new analytics and reporting features require flexible graph queries that REST cannot efficiently serve.'),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Apollo Client configured with InMemoryCache and auth link for JWT header injection.',
                    'GraphQL Code Generator set up to produce TypeScript types from schema.',
                    'At least 5 existing REST calls migrated to GraphQL queries (analytics endpoints).',
                    'Apollo DevTools visible in development builds.',
                    'Error handling: network errors display a toast; GraphQL errors logged silently.',
                    'Bundle size increase < 15KB gzipped after tree-shaking.'
                )
            ),

            'SCRUM-16' => $this->doc(
                $this->h3('Overview'),
                $this->p('Add a dark colour scheme to the application using CSS custom properties. The mode should persist across sessions and respect the user\'s OS-level preference on first visit.'),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All colour values use CSS custom properties defined in :root and [data-theme="dark"].',
                    'Toggle button in user settings header; preference saved to localStorage.',
                    'On first visit, prefers-color-scheme media query sets the initial theme.',
                    'All text passes WCAG AA contrast ratios (4.5:1 for normal, 3:1 for large text) in both modes.',
                    'Animated transition between themes: 200ms ease on color and background-color.',
                    'No flash of unstyled content — theme class applied before first paint via inline script.'
                )
            ),

            'SCRUM-17' => $this->doc(
                $this->h3('Overview'),
                $this->p('Write comprehensive unit and integration tests for the dashboard module using Jest and React Testing Library. Tests should guard against regressions as the data model evolves.'),
                $this->h3('Coverage Targets'),
                $this->bullets(
                    'Dashboard summary cards — all metric formats and loading states',
                    'DataTable component — sorting, pagination, empty state, row click handler',
                    'FilterBar component — all filter combinations emit correct query params',
                    'SkillBadge and ScorePill components — edge cases (0%, 100%, null values)',
                    'Form validation hooks — required, min/max, email, custom validators'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Statement coverage ≥ 85% on src/components/dashboard/**.',
                    'No test mocks for business logic — only for external API calls.',
                    'Tests run in < 30s in CI.',
                    'Snapshot tests for all pure presentational components.'
                )
            ),

            'SCRUM-18' => $this->doc(
                $this->h3('Overview'),
                $this->p('Set up Storybook as the living documentation hub for all UI components. Every shared component must have stories covering its core states, making it easy for designers and new engineers to understand the design system.'),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Storybook 7.x configured with Vite builder for fast HMR.',
                    'All components in /src/components/shared have stories (default, variants, edge cases).',
                    'Knobs/Controls panel allows prop experimentation without code changes.',
                    'Chromatic visual regression tests configured and passing in CI.',
                    'Static Storybook build deployed to GitHub Pages on every main branch push.',
                    'Design tokens (colours, spacing, typography) documented in a Tokens story.'
                )
            ),

            // ── Aman Verma — Backend Python / DevOps ─────────────────────────

            'SCRUM-19' => $this->doc(
                $this->h3('Overview'),
                $this->p('Design and build the FastAPI microservice that exposes ML inference endpoints to the Laravel backend. The service must be stateless, horizontally scalable, and return structured JSON responses suitable for storage.'),
                $this->h3('Endpoints to Implement'),
                $this->bullets(
                    'POST /analyze-resume     — score a resume against a job description',
                    'POST /extract-jira-signals — infer skills from Jira task history',
                    'POST /match-project-resources — rank employees for a project',
                    'POST /parse-job-description  — extract structured fields from JD text',
                    'GET  /health             — liveness probe'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All endpoints validated with Pydantic v2 models; 422 on invalid input.',
                    'Median response time < 5s for resume analysis with Claude claude-haiku-4-5-20251001.',
                    'Service is stateless — no database connections, no local file writes.',
                    'OpenAPI docs auto-generated and accessible at /docs.',
                    'Rate limiting: 60 requests/minute per API key via slowapi.'
                )
            ),

            'SCRUM-20' => $this->doc(
                $this->h3('Overview'),
                $this->p('Configure Celery with a Redis message broker to handle long-running background tasks: ML batch processing, report generation, and scheduled data syncs. The queue must survive worker restarts without losing tasks.'),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Celery configured with Redis as both broker and result backend.',
                    'Task acknowledgement set to ACKS_LATE — tasks requeued if worker crashes mid-execution.',
                    'Separate queues: high (AI inference), default (sync jobs), low (reports).',
                    'Retry policy: 3 attempts with exponential back-off (2^attempt * 5s).',
                    'Celery Flower dashboard running on port 5555 in development.',
                    'Max worker concurrency set to 4; memory limit 512MB per worker process.'
                )
            ),

            'SCRUM-21' => $this->doc(
                $this->h3('Overview'),
                $this->p('Build a webhook consumer that receives real-time issue events from Jira and updates the employee task database. This replaces the polling approach with an event-driven push model, reducing sync latency from hours to seconds.'),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Webhook endpoint validates the Jira-supplied HMAC-SHA256 signature on every request.',
                    'Events processed: jira:issue_created, jira:issue_updated, jira:issue_deleted.',
                    'Idempotent: re-delivery of the same event does not create duplicates.',
                    'Processing is asynchronous — webhook returns 200 immediately, queues Celery task.',
                    'Failed events stored in a dead-letter queue for manual replay.',
                    'E2E tested using Jira\'s webhook test delivery feature.'
                )
            ),

            'SCRUM-22' => $this->doc(
                $this->h3('Overview'),
                $this->p('Package the FastAPI AI service into a production-ready Docker image and define a local development environment via docker-compose. The image must be lean, secure, and reproducibly buildable in CI.'),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Multi-stage Dockerfile: builder stage installs deps, final stage copies only runtime files.',
                    'Final image size < 800MB (using python:3.11-slim base).',
                    'Non-root USER specified in Dockerfile.',
                    'docker-compose.yml defines: api, worker, redis, and flower services.',
                    'Secrets passed via environment variables, never baked into the image.',
                    'Image build and push automated in CI; tagged with git SHA and semver on release.'
                )
            ),

            'SCRUM-23' => $this->doc(
                $this->h3('Overview'),
                $this->p('Write Kubernetes manifests for all production services. Deployments must support zero-downtime rolling updates, auto-scaling under load, and secret management via Kubernetes Secrets.'),
                $this->h3('Manifests Required'),
                $this->bullets(
                    'Deployment + Service for: api, ai-service, queue-worker',
                    'HorizontalPodAutoscaler: scale api from 2–10 pods at 70% CPU',
                    'Ingress with TLS termination via cert-manager (Let\'s Encrypt)',
                    'ConfigMap for non-secret environment variables',
                    'Namespace: production, staging (separate manifests via Kustomize overlays)'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Rolling update with maxSurge=1, maxUnavailable=0 for zero-downtime deploys.',
                    'Resource requests and limits defined for all containers.',
                    'Pod disruption budget ensures ≥ 1 replica always available.',
                    'Manifests linted by kubeval and kube-score in CI pipeline.'
                )
            ),

            'SCRUM-24' => $this->doc(
                $this->h3('Overview'),
                $this->p('Automate the test, build, and deploy pipeline using GitHub Actions. Every pull request should trigger tests; every merge to main should deploy to staging automatically, with a manual gate for production.'),
                $this->h3('Pipeline Stages'),
                $this->bullets(
                    'lint-test: PHPStan, ESLint, Pest tests, pytest — runs on every PR',
                    'build: Docker image built and pushed to GHCR with git-SHA tag',
                    'deploy-staging: kubectl rollout triggered on merge to main',
                    'deploy-production: manual workflow_dispatch with semver tag input'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Total CI time for lint-test stage < 4 minutes.',
                    'Secrets stored in GitHub repository/environment secrets (never in YAML).',
                    'Slack notification on deploy-staging success or failure.',
                    'Rollback: one-click GitHub Actions manual trigger to redeploy previous SHA.',
                    'Test coverage report posted as PR comment via Codecov.'
                )
            ),

            'SCRUM-25' => $this->doc(
                $this->h3('Overview'),
                $this->p('Add liveness and readiness HTTP endpoints to all services so Kubernetes can reliably manage pod lifecycle — restarting unhealthy pods and only routing traffic to fully initialised ones.'),
                $this->h3('Endpoints Required'),
                $this->bullets(
                    'GET /health  (liveness)  — returns 200 if process is alive; checks nothing external',
                    'GET /ready   (readiness) — returns 200 only if DB, Redis, and model cache are reachable'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    '/health responds in < 50ms; never returns 5xx unless the process is truly broken.',
                    '/ready returns 503 with a JSON reason if any dependency is unavailable.',
                    'K8s probes configured: initialDelaySeconds=10, periodSeconds=15, failureThreshold=3.',
                    'All services (api, ai-service, worker) have both probes configured.',
                    'Simulated dependency outage test: pod is removed from Service endpoints within 45s.'
                )
            ),

            // ── Sara Lim — UI/UX Designer ─────────────────────────────────────

            'SCRUM-26' => $this->doc(
                $this->h3('Overview'),
                $this->p('Build the definitive design system in Figma that serves as the single source of truth for all product UI. Every component must use auto-layout and reference shared style tokens so updates propagate automatically.'),
                $this->h3('Deliverables'),
                $this->bullets(
                    'Foundations: colour palette (primary, neutral, semantic), typography scale, spacing grid, elevation/shadow tokens',
                    'Components: Button (5 variants × 3 sizes), Input, Select, Checkbox, Radio, Toggle, Badge, Tag, Alert, Modal, Tooltip, Dropdown, Table, Card, Tabs, Progress, Spinner',
                    'Patterns: Form layouts, empty states, loading skeletons, error pages',
                    'Dark mode: every component has light and dark variants via Figma variable modes'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All components use auto-layout — no hardcoded dimensions.',
                    'Token library exported as JSON via Figma Tokens plugin.',
                    'Engineering hand-off notes on every component frame.',
                    'Component page includes do/don\'t usage examples.'
                )
            ),

            'SCRUM-27' => $this->doc(
                $this->h3('Overview'),
                $this->p('Design the new user onboarding experience — the critical path from account activation to the first meaningful action (running a resource match or screening a candidate). Goal: time-to-value < 5 minutes.'),
                $this->h3('Screens to Design'),
                $this->bullets(
                    '1. Welcome + role selection (HR Manager / Resource Manager / Admin)',
                    '2. Organisation profile setup (name, logo, domain)',
                    '3. Invite team members (email list input)',
                    '4. Role-specific quick-start (HR: add a job; RM: add an employee)',
                    '5. Integration connect (Jira / Zoho optional)',
                    '6. Completion + guided tour offer'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Annotated wireframes for all 6 screens, desktop and mobile viewports.',
                    'Interactive prototype linked from Figma cover page.',
                    'Copy reviewed and approved by product lead.',
                    'Presented in design review — feedback incorporated within one iteration.'
                )
            ),

            'SCRUM-28' => $this->doc(
                $this->h3('Overview'),
                $this->p('Run moderated usability testing sessions to validate the resource matching workflow before the feature ships. Sessions will uncover navigation confusion, unclear terminology, and confidence gaps in the AI recommendations.'),
                $this->h3('Session Plan'),
                $this->bullets(
                    'Participants: 5 resource managers from beta customers (recruited via Typeform)',
                    'Duration: 45 minutes per session (recorded via Loom)',
                    'Tasks: (1) Add an employee, (2) Create a project, (3) Run resource matching, (4) Assign a resource',
                    'Metrics: task completion rate, time-on-task, think-aloud observations, SUS score'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All 5 sessions completed and recordings saved to /research/usability-q1-2026.',
                    'Findings deck produced: top 3 usability issues with severity ratings.',
                    'Recommendations shared with engineering in sprint planning.',
                    'SUS score ≥ 70 (acceptable usability threshold).'
                )
            ),

            'SCRUM-29' => $this->doc(
                $this->h3('Overview'),
                $this->p('Extend the desktop-first designs for the five most-used screens so they work well on phones and tablets. Research shows 35% of HR managers check the platform on mobile daily.'),
                $this->h3('Screens to Adapt'),
                $this->bullets(
                    'Dashboard — summary cards stack vertically; charts use horizontal scroll',
                    'Employee list — condensed card view replaces table; filters in bottom sheet',
                    'Candidate list — same pattern as employee list',
                    'Employee profile — tabs become accordions; skill bars full width',
                    'Project detail — tabs scroll horizontally; resource matches in a list'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All designs delivered at 375px (iPhone SE) and 768px (iPad) breakpoints.',
                    'Touch targets ≥ 44×44px for all interactive elements (WCAG 2.5.5).',
                    'No horizontal overflow at any breakpoint.',
                    'Designs reviewed and approved by engineering before sprint close.'
                )
            ),

            'SCRUM-30' => $this->doc(
                $this->h3('Overview'),
                $this->p('Conduct a comprehensive accessibility audit of the current application against WCAG 2.1 Level AA standards. Produce a prioritised remediation plan so engineering can address critical issues in the next sprint.'),
                $this->h3('Audit Scope'),
                $this->bullets(
                    'Automated scan: axe DevTools on all main routes (dashboard, employees, candidates, projects, settings)',
                    'Manual check: keyboard-only navigation through core workflows',
                    'Screen reader test: NVDA on Windows, VoiceOver on macOS',
                    'Colour contrast: all text/interactive elements in light and dark mode'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Audit report in /docs/accessibility-audit-2026-q1.md.',
                    'Issues categorised: Critical (WCAG A/AA fail), Major (best practice), Minor (enhancement).',
                    '0 Critical issues after remediation sprint.',
                    'Remediation backlog tickets created in Jira for all Critical and Major issues.'
                )
            ),

            'SCRUM-31' => $this->doc(
                $this->h3('Overview'),
                $this->p('Redesign the employee profile page to better surface the AI-generated insights that differentiate the platform. The current design buries skill data below contact info — it should lead with what makes the platform valuable.'),
                $this->h3('New Layout'),
                $this->bullets(
                    'Hero: name, designation, department, Jira sync status',
                    'Tab 1 — Skills: radar chart of top 8 skills (confidence × depth), source badges (Jira / Resume)',
                    'Tab 2 — Project Matches: ranked list with score bar, strengths, and gaps',
                    'Tab 3 — Activity: Jira velocity chart (tasks completed per sprint), labels breakdown',
                    'Sidebar: quick stats (tasks done, avg story points, projects assigned)'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'High-fidelity Figma mockups for desktop and mobile.',
                    'Radar chart component designed and handed off with D3 spec.',
                    'Design reviewed in critique session — two iterations max.',
                    'Developers confirm implementation feasibility before sign-off.'
                )
            ),

            'SCRUM-32' => $this->doc(
                $this->h3('Overview'),
                $this->p('Migrate the design system from the legacy icon set to Phosphor Icons and update the type scale to use a more legible geometric sans-serif. All changes must be propagated through Figma tokens and communicated to engineering.'),
                $this->h3('Changes'),
                $this->bullets(
                    'Icons: replace Feather Icons → Phosphor Icons (regular weight, consistent 24px grid)',
                    'Type scale: update to Inter variable font; new scale: 11/12/13/14/16/18/20/24/32/40px',
                    'Token update: bump design system version to 2.0 in Figma variables',
                    'Migration guide: document icon name mapping (old → new) for engineering'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All 200+ icon usages in Figma migrated to Phosphor equivalents.',
                    'Typography tokens JSON exported and committed to /design-tokens/typography.json.',
                    'Migration guide reviewed and approved by lead engineer.',
                    'No open Figma issues after migration — all components updated.'
                )
            ),

            // ── Anita Patel — HR Specialist ───────────────────────────────────

            'SCRUM-33' => $this->doc(
                $this->h3('Overview'),
                $this->p('Create a standardised onboarding process for new hires that reduces time-to-productivity from the current 3 weeks to under 2 weeks. The process should be self-service where possible and cover IT, HR admin, and team integration.'),
                $this->h3('Checklist Sections'),
                $this->bullets(
                    'Before Day 1: equipment request, system accounts (email, Slack, GitHub, Jira), buddy assignment',
                    'Day 1: ID verification, contract signing, office access, intro meetings (HR, manager, team)',
                    'Week 1: product walkthrough, codebase overview, first task assignment',
                    'Week 2–4: 30-day check-in, probation goals set, optional pair-programming sessions'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Checklist published in Notion under HR > Onboarding Playbook.',
                    'Each item has an owner (HR, IT, Manager, or New Hire) and estimated time.',
                    'New hire satisfaction survey sent at 30 days — target NPS ≥ 40.',
                    'Process piloted with next two new hires; feedback incorporated.'
                )
            ),

            'SCRUM-34' => $this->doc(
                $this->h3('Overview'),
                $this->p('Configure the bidirectional integration between the HRIS (BambooHR) and the talent platform so that employee records are always in sync. New hires should be auto-provisioned; terminations should trigger deactivation within 24 hours.'),
                $this->h3('Sync Rules'),
                $this->bullets(
                    'New employee in HRIS → create Employee record + send welcome email',
                    'Employee status = Terminated in HRIS → set is_active = false within 24h',
                    'Field mappings: first_name, last_name, email, department, job_title, start_date, manager_id',
                    'Delta sync runs every 6 hours; full reconciliation runs weekly on Sunday 02:00 UTC'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Integration tested with 10 HRIS records; all synced correctly.',
                    'Conflict resolution: HRIS is source of truth for personal data; platform owns skills data.',
                    'Sync errors emailed to hr-ops@nalamsystems.work within 15 minutes.',
                    'Integration documented in /docs/hris-integration.md.'
                )
            ),

            'SCRUM-35' => $this->doc(
                $this->h3('Overview'),
                $this->p('Define clear, observable competency frameworks for all engineering levels to support fair performance reviews, career growth conversations, and calibration sessions. Based on the Dropbox Engineering Career Framework.'),
                $this->h3('Levels to Define'),
                $this->bullets(
                    'L1 — Associate Engineer: guided execution, learning fundamentals',
                    'L2 — Engineer: independent task delivery, code review participation',
                    'L3 — Senior Engineer: feature ownership, mentoring juniors, tech design',
                    'L4 — Staff Engineer: cross-team technical leadership, architecture decisions',
                    'L5 — Principal Engineer: org-wide impact, sets technical direction',
                    'EM — Engineering Manager: people leadership, team delivery, career growth'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Framework published in Notion under HR > Career Ladders.',
                    '4 competency dimensions per level: Technical Skill, Delivery, Communication, Leadership.',
                    '3–5 observable behaviours per dimension per level.',
                    'Reviewed and approved by VP Engineering and HR Director.',
                    'Framework used in Q1 performance calibration sessions.'
                )
            ),

            'SCRUM-36' => $this->doc(
                $this->h3('Overview'),
                $this->p('Facilitate Q1 2026 performance review calibration to ensure ratings are consistent, fair, and aligned across teams. Calibration prevents grade inflation and protects against unconscious bias in assessments.'),
                $this->h3('Process'),
                $this->bullets(
                    'Session 1: Engineering calibration (all L1–L3 engineers, 2 hours)',
                    'Session 2: Senior calibration (L4+, EMs, 1.5 hours)',
                    'Session 3: Final sign-off with VP Engineering (30 minutes)',
                    'Rating distribution target: 10% Exceeds, 70% Meets, 20% Developing'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'All 3 sessions completed by end of February 2026.',
                    'Calibration guidelines and rating rubric distributed 1 week before sessions.',
                    'Final ratings entered in HRIS and shared with managers by March 1.',
                    'Post-calibration: all employees receive written feedback by March 15.'
                )
            ),

            'SCRUM-37' => $this->doc(
                $this->h3('Overview'),
                $this->p('Update the company remote work policy to reflect the new hybrid working norms for 2026. The current policy was written in 2022 and does not address home office equipment allowances, travel requirements, or async-first communication standards.'),
                $this->h3('Sections to Update'),
                $this->bullets(
                    'Eligibility: all full-time employees; contractors excluded',
                    'Schedule: minimum 2 days in-office per month for team alignment; flexible otherwise',
                    'Equipment: £500 annual home office allowance (desk, chair, peripherals)',
                    'Connectivity: company pays broadband allowance of £30/month',
                    'Communication norms: async-first, response SLA within 4 business hours',
                    'Travel: quarterly team offsite (flights + accommodation fully expensed)'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Policy draft reviewed by Legal, Finance, and HR Director.',
                    'Published in Notion and BambooHR by February 28, 2026.',
                    'All employees acknowledge the updated policy via DocuSign by March 31.',
                    'FAQ document attached covering the 10 most common questions from the 2022 rollout.'
                )
            ),

            'SCRUM-38' => $this->doc(
                $this->h3('Overview'),
                $this->p('Train six senior engineers to conduct structured technical interviews as part of the hiring panel. Structured interviewing reduces time-to-hire and significantly improves signal quality compared to unstructured conversations.'),
                $this->h3('Training Content'),
                $this->bullets(
                    'Module 1 (30 min): Why structured interviews — evidence and internal data',
                    'Module 2 (45 min): Competency-based questions — writing and calibrating',
                    'Module 3 (30 min): Unconscious bias in technical hiring — recognition and mitigation',
                    'Module 4 (15 min): Scoring rubrics and feedback documentation in the ATS'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Workshop materials (slides, question bank, scoring rubric) reviewed by HR lead.',
                    'All 6 engineers attend and complete a post-training assessment (≥ 80% pass).',
                    'Each engineer shadows 1 live interview before conducting independently.',
                    'Interview quality tracked via candidate NPS for 90 days post-training.'
                )
            ),

            // ── Bug-fix tasks ─────────────────────────────────────────────────

            'SCRUM-39' => $this->doc(
                $this->h3('Bug Report'),
                $this->p('The GET /api/v1/employees endpoint fires a separate SQL query for each employee\'s department when listing 50+ records. This causes 51 queries for a 50-row page, increasing p95 response time from 120ms to 890ms under load.'),
                $this->h3('Steps to Reproduce'),
                $this->bullets(
                    '1. Seed 100+ employees across multiple departments.',
                    '2. Enable Laravel query log: DB::enableQueryLog().',
                    '3. Call GET /api/v1/employees?per_page=50.',
                    '4. Inspect DB::getQueryLog() — 51 queries instead of 2.'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Employee list loads with exactly 2 queries: one for employees, one for departments (eager load).',
                    'Response time < 150ms for 100 employees with department data.',
                    'Query log assertion added to the EmployeeApiTest to prevent regression.',
                    'Fix: use Employee::with([\'department\', \'jiraTasks\']) in the controller query.'
                )
            ),

            'SCRUM-40' => $this->doc(
                $this->h3('Bug Report'),
                $this->p('On viewports narrower than 400px (iPhone SE, Galaxy A32), the candidate and employee list tables overflow horizontally. Users must scroll right to see the action buttons, and the table header misaligns from the body on scroll.'),
                $this->h3('Steps to Reproduce'),
                $this->bullets(
                    '1. Open Chrome DevTools and set viewport to 375 × 812 (iPhone SE).',
                    '2. Navigate to /candidates or /employees.',
                    '3. Observe: table extends beyond viewport; header and body columns misalign after horizontal scroll.'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'No horizontal scrollbar on the page body at 375px viewport.',
                    'Table wraps into a card-based list view below 640px (mobile breakpoint).',
                    'Sticky table header removed on mobile to fix the misalignment.',
                    'Tested on: Chrome 375px, Firefox 375px, Safari on iOS 16 (BrowserStack).',
                    'Screenshot before/after attached to this ticket on close.'
                )
            ),

            'SCRUM-41' => $this->doc(
                $this->h3('Bug Report'),
                $this->p('Celery worker process memory grows from ~80MB at startup to > 1.2GB after approximately 24 hours of continuous operation. The process is then OOM-killed by the OS, causing a queue backlog. Suspected causes: object caching, large task payloads not being freed, or a third-party library leak.'),
                $this->h3('Investigation Steps'),
                $this->bullets(
                    '1. Profile memory with memray: celery worker --pool=solo (single-process for clean profiling).',
                    '2. Run for 2 hours processing 1,000 AI analysis tasks.',
                    '3. Capture memray flamegraph — identify which objects are not being GC\'d.',
                    '4. Check for large numpy/torch arrays retained in task closures.',
                    '5. Review CELERYD_MAX_TASKS_PER_CHILD setting — set to 100 to recycle workers.'
                ),
                $this->h3('Acceptance Criteria'),
                $this->bullets(
                    'Root cause documented in the post-mortem section of this ticket.',
                    'Worker memory stable ≤ 300MB after 48 hours of continuous operation (monitored via Prometheus).',
                    'CELERYD_MAX_TASKS_PER_CHILD = 200 added to celery config as short-term mitigation.',
                    'Alerting rule added: PagerDuty alert if any Celery worker exceeds 500MB RSS.',
                    'Fix merged and verified in staging before production deploy.'
                )
            ),

        ];
    }
}
