<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\SyncZohoPeopleJob;
use App\Jobs\SyncOrangeHRMJob;
use App\Jobs\SyncGitHubSignalsJob;
use App\Jobs\SyncDevOasTasksJob;
use App\Jobs\SyncGitHubProjectsJob;
use App\Jobs\SyncSlackMetricsJob;
use App\Jobs\SyncTeamsMetricsJob;
use App\Models\IntegrationConnection;
use App\Models\JiraConnection;
use App\Models\ZohoPeopleConnection;
use App\Models\ZohoProjectsConnection;
use App\Services\SpreadsheetParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IntegrationsController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();
        $jiraConnections = JiraConnection::where('organization_id', $orgId)->get();
        $zohoConnections = ZohoProjectsConnection::where('organization_id', $orgId)->get();
        $zohoPeopleConnections = ZohoPeopleConnection::where('organization_id', $orgId)->get();

        // Generic integration connections grouped by type
        $integrationConnections = IntegrationConnection::where('organization_id', $orgId)
            ->get()->groupBy('type');

        return view('settings.integrations.index', compact(
            'jiraConnections', 'zohoConnections', 'zohoPeopleConnections', 'integrationConnections'
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // Zoho Projects
    // ─────────────────────────────────────────────────────────────

    public function storeZohoProjects(Request $request)
    {
        $validated = $request->validate([
            'portal_name' => 'required|string|max:255',
            'auth_token' => 'required|string',
        ]);

        $validated['organization_id'] = Auth::user()->currentOrganizationId();
        ZohoProjectsConnection::create($validated);

        return redirect()->route('settings.integrations.index')
            ->with('success', 'Zoho Projects connection added.');
    }

    public function testZohoProjects(ZohoProjectsConnection $connection)
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) abort(403);
        return back()->with('success', 'Zoho Projects connection is valid.');
    }

    public function destroyZohoProjects(ZohoProjectsConnection $connection)
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) abort(403);
        $connection->delete();
        return back()->with('success', 'Zoho Projects connection removed.');
    }

    // ─────────────────────────────────────────────────────────────
    // Zoho People
    // ─────────────────────────────────────────────────────────────

    public function storeZohoPeople(Request $request)
    {
        $validated = $request->validate([
            'portal_name' => 'required|string|max:255',
            'auth_token' => 'required|string',
        ]);

        $validated['organization_id'] = Auth::user()->currentOrganizationId();
        ZohoPeopleConnection::create($validated);

        return redirect()->route('settings.integrations.index')
            ->with('success', 'Zoho People connection added.');
    }

    public function testZohoPeople(ZohoPeopleConnection $connection)
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) abort(403);
        return back()->with('success', 'Zoho People connection is valid.');
    }

    public function syncZohoPeople(ZohoPeopleConnection $connection)
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) abort(403);
        SyncZohoPeopleJob::dispatch($connection);
        return back()->with('success', 'Zoho People sync started. Employees will be updated shortly.');
    }

    public function destroyZohoPeople(ZohoPeopleConnection $connection)
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) abort(403);
        $connection->delete();
        return back()->with('success', 'Zoho People connection removed.');
    }

    // ─────────────────────────────────────────────────────────────
    // Task Spreadsheet Upload
    // ─────────────────────────────────────────────────────────────

    public function uploadTaskSpreadsheet(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,txt|max:5120',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'txt') $ext = 'csv';

        $path = $file->store('imports/tasks', 'public');
        $fullPath = Storage::disk('public')->path($path);

        try {
            $parser = new SpreadsheetParser();
            $rows = $parser->parse($fullPath, $ext);
        } catch (\Exception $e) {
            Storage::disk('public')->delete($path);
            return back()->with('error', 'Failed to parse file: ' . $e->getMessage());
        }

        Storage::disk('public')->delete($path);

        if (empty($rows)) {
            return back()->with('error', 'No data rows found in the file.');
        }

        $orgId = Auth::user()->currentOrganizationId();
        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $email = $row['employee_email'] ?? '';
            $taskKey = $row['task_key'] ?? '';
            $summary = $row['summary'] ?? '';

            if (!$email || !$taskKey || !$summary) { $skipped++; continue; }

            $employee = \App\Models\Employee::where('organization_id', $orgId)
                ->where('email', $email)->first();

            if (!$employee) { $skipped++; continue; }

            \App\Models\EmployeeTask::updateOrCreate(
                ['employee_id' => $employee->id, 'source_type' => 'jira', 'external_id' => $taskKey],
                [
                    'organization_id'   => $orgId,
                    'title'             => $summary,
                    'description'       => $row['description'] ?? null,
                    'status'            => $row['status'] ?? null,
                    'priority'          => $row['priority'] ?? null,
                    'story_points'      => isset($row['story_points']) && is_numeric($row['story_points']) ? (float) $row['story_points'] : null,
                    'assignee_email'    => $email,
                    'completed_at'      => !empty($row['completed_at']) ? $row['completed_at'] : null,
                    'metadata'          => ['sprint_name' => $row['sprint_name'] ?? null],
                ]
            );

            $imported++;
        }

        $message = "Task spreadsheet processed: {$imported} tasks imported";
        if ($skipped > 0) $message .= ", {$skipped} skipped";

        return back()->with('success', $message);
    }

    // ─────────────────────────────────────────────────────────────
    // OrangeHRM
    // ─────────────────────────────────────────────────────────────

    public function storeOrangeHRM(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'base_url'      => 'required|url|max:500',
            'client_id'     => 'required|string',
            'client_secret' => 'required|string',
        ]);

        IntegrationConnection::create([
            'organization_id' => Auth::user()->currentOrganizationId(),
            'type'            => 'orangehrm',
            'name'            => $validated['name'],
            'credentials'     => [
                'base_url'      => rtrim($validated['base_url'], '/'),
                'client_id'     => $validated['client_id'],
                'client_secret' => $validated['client_secret'],
            ],
        ]);

        return redirect()->route('settings.integrations.index')
            ->with('success', 'OrangeHRM connection added.');
    }

    public function testOrangeHRM(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        $creds = $connection->credentials;

        try {
            $response = Http::timeout(10)->asForm()->post($creds['base_url'] . '/oauth/issueToken', [
                'grant_type'    => 'client_credentials',
                'client_id'     => $creds['client_id'],
                'client_secret' => $creds['client_secret'],
            ]);
            if ($response->successful() && $response->json('access_token')) {
                return back()->with('success', 'OrangeHRM connection is valid — authentication successful.');
            }
            return back()->with('error', 'OrangeHRM test failed: ' . ($response->json('error_description') ?? 'Invalid credentials'));
        } catch (\Exception $e) {
            return back()->with('error', 'OrangeHRM test failed: ' . $e->getMessage());
        }
    }

    public function syncOrangeHRM(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        SyncOrangeHRMJob::dispatch($connection);
        return back()->with('success', 'OrangeHRM sync started. Employees will be updated shortly.');
    }

    public function destroyOrangeHRM(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        $connection->delete();
        return back()->with('success', 'OrangeHRM connection removed.');
    }

    // ─────────────────────────────────────────────────────────────
    // GitHub (Source Code Signals)
    // ─────────────────────────────────────────────────────────────

    public function storeGitHub(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'org_name'     => 'required|string|max:255',
            'access_token' => 'required|string',
        ]);

        IntegrationConnection::create([
            'organization_id' => Auth::user()->currentOrganizationId(),
            'type'            => 'github',
            'name'            => $validated['name'],
            'credentials'     => [
                'org_name'     => $validated['org_name'],
                'access_token' => $validated['access_token'],
            ],
        ]);

        return redirect()->route('settings.integrations.index')
            ->with('success', 'GitHub connection added.');
    }

    public function testGitHub(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        $creds = $connection->credentials;

        try {
            $response = Http::withToken($creds['access_token'])
                ->withHeaders(['Accept' => 'application/vnd.github+json'])
                ->timeout(10)
                ->get("https://api.github.com/orgs/{$creds['org_name']}");

            if ($response->successful()) {
                $org = $response->json('name') ?? $creds['org_name'];
                return back()->with('success', "GitHub connection is valid — connected to organisation: {$org}");
            }
            return back()->with('error', 'GitHub test failed: ' . ($response->json('message') ?? 'Invalid token or organisation'));
        } catch (\Exception $e) {
            return back()->with('error', 'GitHub test failed: ' . $e->getMessage());
        }
    }

    public function syncGitHub(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        SyncGitHubSignalsJob::dispatch($connection);
        return back()->with('success', 'GitHub signal sync started. Code activity signals will be updated shortly.');
    }

    public function destroyGitHub(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        $connection->delete();
        return back()->with('success', 'GitHub connection removed.');
    }

    // ─────────────────────────────────────────────────────────────
    // Microsoft DevOps Boards
    // ─────────────────────────────────────────────────────────────

    public function storeDevOps(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'org_name'     => 'required|string|max:255',
            'project_name' => 'required|string|max:255',
            'access_token' => 'required|string',
        ]);

        IntegrationConnection::create([
            'organization_id' => Auth::user()->currentOrganizationId(),
            'type'            => 'devops_boards',
            'name'            => $validated['name'],
            'credentials'     => [
                'org_name'     => $validated['org_name'],
                'project_name' => $validated['project_name'],
                'access_token' => $validated['access_token'],
            ],
        ]);

        return redirect()->route('settings.integrations.index')
            ->with('success', 'Microsoft DevOps Boards connection added.');
    }

    public function testDevOps(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        $creds = $connection->credentials;

        try {
            $response = Http::withBasicAuth('', $creds['access_token'])
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(10)
                ->get("https://dev.azure.com/{$creds['org_name']}/_apis/projects/{$creds['project_name']}?api-version=7.1");

            if ($response->successful()) {
                $project = $response->json('name') ?? $creds['project_name'];
                return back()->with('success', "DevOps Boards connection is valid — connected to project: {$project}");
            }
            return back()->with('error', 'DevOps test failed: ' . ($response->json('message') ?? 'Invalid PAT or project name'));
        } catch (\Exception $e) {
            return back()->with('error', 'DevOps test failed: ' . $e->getMessage());
        }
    }

    public function syncDevOps(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        SyncDevOasTasksJob::dispatch($connection);
        return back()->with('success', 'DevOps Boards sync started. Work items will be updated shortly.');
    }

    public function destroyDevOps(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        $connection->delete();
        return back()->with('success', 'DevOps Boards connection removed.');
    }

    // ─────────────────────────────────────────────────────────────
    // GitHub Projects Boards
    // ─────────────────────────────────────────────────────────────

    public function storeGitHubProjects(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'org_name'       => 'required|string|max:255',
            'project_number' => 'required|integer|min:1',
            'access_token'   => 'required|string',
        ]);

        IntegrationConnection::create([
            'organization_id' => Auth::user()->currentOrganizationId(),
            'type'            => 'github_projects',
            'name'            => $validated['name'],
            'credentials'     => [
                'org_name'       => $validated['org_name'],
                'project_number' => (int) $validated['project_number'],
                'access_token'   => $validated['access_token'],
            ],
        ]);

        return redirect()->route('settings.integrations.index')
            ->with('success', 'GitHub Projects Boards connection added.');
    }

    public function testGitHubProjects(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        $creds = $connection->credentials;

        try {
            $query = 'query($org: String!, $num: Int!) { organization(login: $org) { projectV2(number: $num) { id title } } }';
            $response = Http::withToken($creds['access_token'])
                ->withHeaders(['Accept' => 'application/vnd.github+json'])
                ->timeout(10)
                ->post('https://api.github.com/graphql', [
                    'query'     => $query,
                    'variables' => ['org' => $creds['org_name'], 'num' => (int) $creds['project_number']],
                ]);

            $title = $response->json('data.organization.projectV2.title');
            if ($title) {
                return back()->with('success', "GitHub Projects connection is valid — project: {$title}");
            }
            return back()->with('error', 'GitHub Projects test failed: project not found or insufficient permissions.');
        } catch (\Exception $e) {
            return back()->with('error', 'GitHub Projects test failed: ' . $e->getMessage());
        }
    }

    public function syncGitHubProjects(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        SyncGitHubProjectsJob::dispatch($connection);
        return back()->with('success', 'GitHub Projects sync started. Issues and cards will be updated shortly.');
    }

    public function destroyGitHubProjects(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        $connection->delete();
        return back()->with('success', 'GitHub Projects Boards connection removed.');
    }

    // ─────────────────────────────────────────────────────────────
    // Slack — OAuth 2.0
    // ─────────────────────────────────────────────────────────────

    public function oauthSlack()
    {
        $clientId = config('services.slack.client_id');
        if (!$clientId) {
            return back()->with('error', 'Slack OAuth is not configured. Add SLACK_CLIENT_ID and SLACK_CLIENT_SECRET to .env');
        }

        $state = Str::random(32);
        session(['slack_oauth_state' => $state]);

        $scopes = 'users:read,users:read.email,channels:read,channels:history,im:history,mpim:history,groups:history';
        $params = http_build_query([
            'client_id'    => $clientId,
            'scope'        => $scopes,
            'state'        => $state,
            'redirect_uri' => route('integrations.oauth.slack.callback'),
        ]);

        return redirect("https://slack.com/oauth/v2/authorize?{$params}");
    }

    public function oauthSlackCallback(Request $request)
    {
        if (!session('slack_oauth_state') || session('slack_oauth_state') !== $request->state) {
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Slack OAuth failed: invalid state. Please try again.');
        }
        session()->forget('slack_oauth_state');

        if ($request->error) {
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Slack OAuth denied: ' . $request->error);
        }

        try {
            $response = Http::asForm()->post('https://slack.com/api/oauth.v2.access', [
                'client_id'     => config('services.slack.client_id'),
                'client_secret' => config('services.slack.client_secret'),
                'code'          => $request->code,
                'redirect_uri'  => route('integrations.oauth.slack.callback'),
            ]);

            $data = $response->json();
            if (!($data['ok'] ?? false)) {
                return redirect()->route('settings.integrations.index')
                    ->with('error', 'Slack OAuth failed: ' . ($data['error'] ?? 'unknown error'));
            }

            $orgId = Auth::user()->currentOrganizationId();
            $teamName = $data['team']['name'] ?? 'Slack Workspace';

            IntegrationConnection::where('organization_id', $orgId)->where('type', 'slack')->delete();
            IntegrationConnection::create([
                'organization_id' => $orgId,
                'type'            => 'slack',
                'name'            => $teamName,
                'credentials'     => [
                    'access_token' => $data['access_token'],
                    'team_id'      => $data['team']['id'],
                    'team_name'    => $teamName,
                    'bot_user_id'  => $data['bot_user_id'] ?? null,
                ],
            ]);

            return redirect()->route('settings.integrations.index')
                ->with('success', "Slack workspace \"{$teamName}\" connected successfully.");
        } catch (\Exception $e) {
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Slack OAuth failed: ' . $e->getMessage());
        }
    }

    public function syncSlack(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        SyncSlackMetricsJob::dispatch($connection);
        return back()->with('success', 'Slack metrics sync started. Communication signals will be updated shortly.');
    }

    public function destroySlack(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        $connection->delete();
        return back()->with('success', 'Slack connection removed.');
    }

    // ─────────────────────────────────────────────────────────────
    // Microsoft Teams — OAuth 2.0
    // ─────────────────────────────────────────────────────────────

    public function oauthTeams()
    {
        $clientId = config('services.teams.client_id');
        $tenantId = config('services.teams.tenant_id', 'common');
        if (!$clientId) {
            return back()->with('error', 'Microsoft Teams OAuth is not configured. Add TEAMS_CLIENT_ID and TEAMS_CLIENT_SECRET to .env');
        }

        $state = Str::random(32);
        session(['teams_oauth_state' => $state]);

        $params = http_build_query([
            'client_id'     => $clientId,
            'response_type' => 'code',
            'redirect_uri'  => route('integrations.oauth.teams.callback'),
            'scope'         => 'https://graph.microsoft.com/Reports.Read.All https://graph.microsoft.com/User.Read.All offline_access',
            'state'         => $state,
        ]);

        return redirect("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?{$params}");
    }

    public function oauthTeamsCallback(Request $request)
    {
        if (!session('teams_oauth_state') || session('teams_oauth_state') !== $request->state) {
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Microsoft Teams OAuth failed: invalid state. Please try again.');
        }
        session()->forget('teams_oauth_state');

        if ($request->error) {
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Microsoft Teams OAuth denied: ' . $request->error_description);
        }

        $tenantId = config('services.teams.tenant_id', 'common');

        try {
            $response = Http::asForm()->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id'     => config('services.teams.client_id'),
                'client_secret' => config('services.teams.client_secret'),
                'grant_type'    => 'authorization_code',
                'code'          => $request->code,
                'redirect_uri'  => route('integrations.oauth.teams.callback'),
            ]);

            $data = $response->json();
            if (isset($data['error'])) {
                return redirect()->route('settings.integrations.index')
                    ->with('error', 'Teams OAuth failed: ' . ($data['error_description'] ?? $data['error']));
            }

            $orgId = Auth::user()->currentOrganizationId();
            $meResponse = Http::withToken($data['access_token'])->get('https://graph.microsoft.com/v1.0/organization');
            $tenantName = $meResponse->json('value.0.displayName') ?? 'Microsoft Tenant';

            IntegrationConnection::where('organization_id', $orgId)->where('type', 'teams')->delete();
            IntegrationConnection::create([
                'organization_id' => $orgId,
                'type'            => 'teams',
                'name'            => $tenantName,
                'credentials'     => [
                    'access_token'  => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'tenant_id'     => $tenantId,
                    'tenant_name'   => $tenantName,
                    'expires_in'    => $data['expires_in'] ?? 3600,
                ],
            ]);

            return redirect()->route('settings.integrations.index')
                ->with('success', "Microsoft Teams \"{$tenantName}\" connected successfully.");
        } catch (\Exception $e) {
            return redirect()->route('settings.integrations.index')
                ->with('error', 'Microsoft Teams OAuth failed: ' . $e->getMessage());
        }
    }

    public function syncTeams(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        SyncTeamsMetricsJob::dispatch($connection);
        return back()->with('success', 'Teams metrics sync started. Communication signals will be updated shortly.');
    }

    public function destroyTeams(IntegrationConnection $connection)
    {
        $this->authorizeConnection($connection);
        $connection->delete();
        return back()->with('success', 'Microsoft Teams connection removed.');
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    private function authorizeConnection(IntegrationConnection $connection): void
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }
    }
}
