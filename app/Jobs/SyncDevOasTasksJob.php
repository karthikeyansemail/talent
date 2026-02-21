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

class SyncDevOasTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private IntegrationConnection $connection
    ) {}

    public function handle(): void
    {
        $credentials  = $this->connection->credentials;
        $orgName      = $credentials['org_name'] ?? '';
        $projectName  = $credentials['project_name'] ?? '';
        $accessToken  = $credentials['access_token'] ?? '';
        $orgId        = $this->connection->organization_id;

        // Azure DevOps uses Basic auth with empty username + PAT
        $basicAuth = base64_encode(':' . $accessToken);

        try {
            $employees = Employee::where('organization_id', $orgId)
                ->whereNotNull('email')
                ->get()
                ->keyBy('email');

            $apiBase = "https://dev.azure.com/{$orgName}/{$projectName}/_apis";
            $http    = Http::withHeaders([
                'Authorization' => "Basic {$basicAuth}",
                'Accept'        => 'application/json',
            ]);

            // WIQL query: get all work items assigned to known emails
            $wiqlResponse = $http->post("{$apiBase}/wit/wiql?api-version=7.1", [
                'query' => "SELECT [System.Id] FROM WorkItems WHERE [System.TeamProject] = '{$projectName}' ORDER BY [System.ChangedDate] DESC",
            ]);

            if (!$wiqlResponse->successful()) {
                Log::warning('DevOps WIQL query failed', [
                    'connection_id' => $this->connection->id,
                    'status' => $wiqlResponse->status(),
                ]);
                return;
            }

            $workItemRefs = $wiqlResponse->json('workItems') ?? [];
            $synced       = 0;

            // Process in batches of 200
            foreach (array_chunk($workItemRefs, 200) as $chunk) {
                $ids = implode(',', array_column($chunk, 'id'));

                $detailsResponse = $http->get("{$apiBase}/wit/workitems?api-version=7.1", [
                    'ids'    => $ids,
                    'fields' => 'System.Id,System.Title,System.WorkItemType,System.State,Microsoft.VSTS.Common.Priority,Microsoft.VSTS.Scheduling.StoryPoints,System.AssignedTo,System.Tags,System.CreatedDate,Microsoft.VSTS.Common.ClosedDate,System.Description',
                ]);

                if (!$detailsResponse->successful()) {
                    continue;
                }

                $workItems = $detailsResponse->json('value') ?? [];

                foreach ($workItems as $item) {
                    $fields        = $item['fields'] ?? [];
                    $assignedTo    = $fields['System.AssignedTo'] ?? [];
                    $assigneeEmail = $assignedTo['uniqueName'] ?? '';

                    if (!$assigneeEmail || !isset($employees[$assigneeEmail])) {
                        continue;
                    }

                    $employee   = $employees[$assigneeEmail];
                    $externalId = (string) $item['id'];
                    $itemType   = $fields['System.WorkItemType'] ?? null;
                    $status     = $fields['System.State'] ?? null;
                    $closedAt   = $fields['Microsoft.VSTS.Common.ClosedDate'] ?? null;

                    // Parse tags
                    $rawTags = $fields['System.Tags'] ?? '';
                    $labels  = $rawTags ? array_filter(array_map('trim', explode(';', $rawTags))) : [];

                    EmployeeTask::updateOrCreate(
                        [
                            'employee_id'  => $employee->id,
                            'source_type'  => 'devops_boards',
                            'external_id'  => $externalId,
                        ],
                        [
                            'organization_id'   => $orgId,
                            'connection_id'     => $this->connection->id,
                            'title'             => $fields['System.Title'] ?? "Work Item #{$externalId}",
                            'description'       => strip_tags($fields['System.Description'] ?? ''),
                            'task_type'         => $itemType,
                            'status'            => $status,
                            'priority'          => $this->normalizePriority($fields['Microsoft.VSTS.Common.Priority'] ?? null),
                            'story_points'      => $fields['Microsoft.VSTS.Scheduling.StoryPoints'] ?? null,
                            'assignee_email'    => $assigneeEmail,
                            'labels'            => array_values($labels),
                            'completed_at'      => $closedAt ? \Carbon\Carbon::parse($closedAt) : null,
                            'source_created_at' => isset($fields['System.CreatedDate'])
                                ? \Carbon\Carbon::parse($fields['System.CreatedDate']) : null,
                            'metadata'          => [
                                'project_name' => $projectName,
                                'org_name'     => $orgName,
                                'work_item_url' => "https://dev.azure.com/{$orgName}/{$projectName}/_workitems/edit/{$externalId}",
                            ],
                        ]
                    );

                    $synced++;
                }
            }

            $this->connection->update(['last_synced_at' => now()]);

            Log::info("DevOps Boards sync complete: {$synced} tasks synced", [
                'connection_id' => $this->connection->id,
            ]);
        } catch (\Exception $e) {
            Log::error('DevOps Boards sync error: ' . $e->getMessage(), [
                'connection_id' => $this->connection->id,
            ]);
            throw $e;
        }
    }

    private function normalizePriority(mixed $priority): ?string
    {
        return match ((int) $priority) {
            1       => 'Critical',
            2       => 'High',
            3       => 'Medium',
            4       => 'Low',
            default => null,
        };
    }
}
