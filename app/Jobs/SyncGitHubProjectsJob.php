<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\EmployeeTask;
use App\Models\IntegrationConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncGitHubProjectsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private IntegrationConnection $connection
    ) {}

    public function handle(): void
    {
        $credentials   = $this->connection->credentials;
        $orgName       = $credentials['org_name'] ?? '';
        $projectNumber = (int) ($credentials['project_number'] ?? 0);
        $accessToken   = $credentials['access_token'] ?? '';
        $orgId         = $this->connection->organization_id;

        try {
            $employees = Employee::where('organization_id', $orgId)
                ->whereNotNull('email')
                ->get()
                ->keyBy('email');

            $synced   = 0;
            $cursor   = null;
            $hasMore  = true;

            while ($hasMore) {
                $afterArg = $cursor ? ', after: "' . $cursor . '"' : '';

                $query = <<<GRAPHQL
                {
                  organization(login: "{$orgName}") {
                    projectV2(number: {$projectNumber}) {
                      title
                      items(first: 100{$afterArg}) {
                        pageInfo { hasNextPage endCursor }
                        nodes {
                          id
                          type
                          createdAt
                          updatedAt
                          fieldValues(first: 20) {
                            nodes {
                              ... on ProjectV2ItemFieldTextValue { text field { ... on ProjectV2FieldCommon { name } } }
                              ... on ProjectV2ItemFieldSingleSelectValue { name field { ... on ProjectV2FieldCommon { name } } }
                              ... on ProjectV2ItemFieldNumberValue { number field { ... on ProjectV2FieldCommon { name } } }
                              ... on ProjectV2ItemFieldDateValue { date field { ... on ProjectV2FieldCommon { name } } }
                              ... on ProjectV2ItemFieldUserValue {
                                users(first: 1) { nodes { email login } }
                                field { ... on ProjectV2FieldCommon { name } }
                              }
                            }
                          }
                          content {
                            ... on Issue {
                              title
                              number
                              body
                              state
                              closedAt
                              createdAt
                              labels(first: 10) { nodes { name } }
                              assignees(first: 5) { nodes { email login } }
                            }
                            ... on DraftIssue {
                              title
                              body
                              createdAt
                              assignees(first: 5) { nodes { email login } }
                            }
                          }
                        }
                      }
                    }
                  }
                }
                GRAPHQL;

                $response = Http::withToken($accessToken)
                    ->withHeaders(['Accept' => 'application/vnd.github+json'])
                    ->post('https://api.github.com/graphql', ['query' => $query]);

                if (!$response->successful()) {
                    Log::warning('GitHub Projects GraphQL failed', [
                        'connection_id' => $this->connection->id,
                        'status' => $response->status(),
                    ]);
                    break;
                }

                $data  = $response->json();
                $items = $data['data']['organization']['projectV2']['items'] ?? [];
                $nodes = $items['nodes'] ?? [];

                foreach ($nodes as $item) {
                    $content = $item['content'] ?? [];
                    if (empty($content)) {
                        continue;
                    }

                    $title  = $content['title'] ?? '';
                    $body   = strip_tags($content['body'] ?? '');
                    $state  = $content['state'] ?? 'OPEN';
                    $closedAt = isset($content['closedAt']) ? \Carbon\Carbon::parse($content['closedAt']) : null;
                    $createdAt = isset($content['createdAt']) ? \Carbon\Carbon::parse($content['createdAt']) : null;

                    // Extract labels
                    $labels = array_column($content['labels']['nodes'] ?? [], 'name');

                    // Find assignee email from content assignees
                    $assigneeEmail = null;
                    $employee      = null;
                    foreach ($content['assignees']['nodes'] ?? [] as $assignee) {
                        $email = $assignee['email'] ?? '';
                        if ($email && isset($employees[$email])) {
                            $assigneeEmail = $email;
                            $employee      = $employees[$email];
                            break;
                        }
                        // Fallback: match by GitHub login stored in employee metadata
                        $login = $assignee['login'] ?? '';
                        if ($login) {
                            $found = $employees->first(fn($e) => ($e->metadata['github_login'] ?? '') === $login);
                            if ($found) {
                                $employee      = $found;
                                $assigneeEmail = $found->email;
                                break;
                            }
                        }
                    }

                    if (!$employee) {
                        continue;
                    }

                    // Extract custom fields (Status, Priority, Story Points)
                    $fieldMap = [];
                    foreach ($item['fieldValues']['nodes'] ?? [] as $fv) {
                        $fieldName = $fv['field']['name'] ?? '';
                        $fieldMap[$fieldName] = $fv['name'] ?? $fv['text'] ?? $fv['number'] ?? $fv['date'] ?? null;
                    }

                    $externalId = $item['id']; // NodeID

                    EmployeeTask::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'source_type' => 'github_projects',
                            'external_id' => $externalId,
                        ],
                        [
                            'organization_id'   => $orgId,
                            'connection_id'     => $this->connection->id,
                            'title'             => $title,
                            'description'       => $body,
                            'task_type'         => $item['type'] ?? 'Issue',
                            'status'            => $fieldMap['Status'] ?? ($state === 'CLOSED' ? 'Done' : 'In Progress'),
                            'priority'          => $fieldMap['Priority'] ?? null,
                            'story_points'      => isset($fieldMap['Story Points']) ? (float) $fieldMap['Story Points'] : null,
                            'assignee_email'    => $assigneeEmail,
                            'labels'            => $labels,
                            'completed_at'      => $closedAt,
                            'source_created_at' => $createdAt,
                            'metadata'          => [
                                'org_name'       => $orgName,
                                'project_number' => $projectNumber,
                                'github_state'   => $state,
                            ],
                        ]
                    );

                    $synced++;
                }

                $pageInfo = $items['pageInfo'] ?? [];
                $hasMore  = $pageInfo['hasNextPage'] ?? false;
                $cursor   = $pageInfo['endCursor'] ?? null;
            }

            $this->connection->update(['last_synced_at' => now()]);

            Log::info("GitHub Projects sync complete: {$synced} tasks synced", [
                'connection_id' => $this->connection->id,
            ]);
        } catch (\Exception $e) {
            Log::error('GitHub Projects sync error: ' . $e->getMessage(), [
                'connection_id' => $this->connection->id,
            ]);
            throw $e;
        }
    }
}
