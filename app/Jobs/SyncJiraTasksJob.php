<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\EmployeeTask;
use App\Models\JiraConnection;
use App\Services\AiServiceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SyncJiraTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public Employee $employee) {}

    public static function cacheKey(int $employeeId): string
    {
        return "work_data_sync_status_{$employeeId}";
    }

    public function handle(): void
    {
        $cacheKey = self::cacheKey($this->employee->id);
        Cache::put($cacheKey, ['status' => 'running', 'pct' => 10, 'phase' => 'Connecting to Jira...'], now()->addMinutes(10));

        $connection = JiraConnection::where('organization_id', $this->employee->organization_id)
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            Cache::put($cacheKey, ['status' => 'failed', 'pct' => 0, 'phase' => 'No Jira connection configured'], now()->addMinutes(5));
            return;
        }

        try {
            Cache::put($cacheKey, ['status' => 'running', 'pct' => 25, 'phase' => 'Fetching tasks from Jira...'], now()->addMinutes(10));

            $response = Http::withBasicAuth($connection->jira_email, $connection->jira_api_token)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(60)
                ->get($connection->jira_base_url . '/rest/api/3/search/jql', [
                    'jql'        => 'assignee="' . $this->employee->email . '" ORDER BY updated DESC',
                    'maxResults' => 100,
                    'fields'     => 'summary,description,issuetype,status,priority,labels,components,customfield_10016,resolution,resolutiondate,created',
                ]);

            if (!$response->successful()) {
                Cache::put($cacheKey, ['status' => 'failed', 'pct' => 0, 'phase' => 'Jira API error: ' . $response->status()], now()->addMinutes(5));
                return;
            }

            $issues = $response->json('issues', []);
            $total  = count($issues);
            Cache::put($cacheKey, ['status' => 'running', 'pct' => 40, 'phase' => "Processing {$total} tasks..."], now()->addMinutes(10));

            foreach ($issues as $i => $issue) {
                $fields     = $issue['fields'];
                $components = collect($fields['components'] ?? [])->pluck('name')->toArray();
                $labels     = $fields['labels'] ?? [];

                // Detect "Bug" from label when Jira project has no Bug issue type
                $jiraTypeName = $fields['issuetype']['name'] ?? null;
                $taskType = (in_array('Bug', $labels) && $jiraTypeName !== 'Bug')
                    ? 'Bug'
                    : $jiraTypeName;

                // Check if task already exists with historical dates from seeding
                $existing = EmployeeTask::where([
                    'employee_id' => $this->employee->id,
                    'source_type' => 'jira',
                    'external_id' => $issue['key'],
                ])->first();

                // Preserve seeded historical dates — only use Jira dates if DB doesn't have them
                $sourceCreatedAt = ($existing && $existing->source_created_at)
                    ? $existing->source_created_at
                    : ($fields['created'] ?? null);

                $jiraCompletedAt = $fields['resolutiondate'] ?? null;
                $completedAt = ($existing && $existing->completed_at && !$jiraCompletedAt)
                    ? $existing->completed_at
                    : $jiraCompletedAt;

                // Preserve seeded story_points if Jira doesn't have them
                $storyPoints = $fields['customfield_10016'] ?? ($existing?->story_points);

                EmployeeTask::updateOrCreate(
                    [
                        'employee_id' => $this->employee->id,
                        'source_type' => 'jira',
                        'external_id' => $issue['key'],
                    ],
                    [
                        'organization_id'   => $this->employee->organization_id,
                        'connection_id'     => null,
                        'title'             => $fields['summary'] ?? '',
                        'description'       => is_array($fields['description'] ?? null)
                            ? $this->extractAdfText($fields['description'])
                            : ($fields['description'] ?? ''),
                        'task_type'         => $taskType,
                        'status'            => $fields['status']['name'] ?? null,
                        'priority'          => $fields['priority']['name'] ?? null,
                        'labels'            => $labels,
                        'story_points'      => $storyPoints,
                        'assignee_email'    => $this->employee->email,
                        'completed_at'      => $completedAt,
                        'source_created_at' => $sourceCreatedAt,
                        'metadata'          => [
                            'resolution'  => $fields['resolution']['name'] ?? null,
                            'components'  => $components,
                        ],
                    ]
                );
            }

            Cache::put($cacheKey, ['status' => 'running', 'pct' => 75, 'phase' => 'Extracting skill signals...'], now()->addMinutes(10));

            // Extract skill signals from Jira tasks via AI
            $tasks = $this->employee->jiraTasks()->get();
            if ($tasks->isNotEmpty()) {
                $client = new AiServiceClient();
                $result = $client->extractJiraSignals([
                    'employee_name' => $this->employee->full_name,
                    'tasks' => $tasks->map(fn($t) => [
                        'key'          => $t->external_id,
                        'summary'      => $t->title,
                        'description'  => $t->description ?? '',
                        'type'         => $t->task_type ?? 'Task',
                        'status'       => $t->status ?? 'Done',
                        'priority'     => $t->priority ?? 'Medium',
                        'labels'       => $t->labels ?? [],
                        'story_points' => $t->story_points,
                        'resolved_at'  => $t->completed_at?->toDateString(),
                    ])->toArray(),
                ], $this->employee->organization_id);

                if (isset($result['extracted_skills'])) {
                    $this->employee->update(['skills_from_jira' => $result]);
                }
            }

            // Stamp employee-level sync timestamp
            $this->employee->update(['work_data_synced_at' => now()]);

            // Mark connection as synced
            \App\Models\JiraConnection::where('organization_id', $this->employee->organization_id)
                ->update(['last_synced_at' => now()]);

            Cache::put($cacheKey, ['status' => 'completed', 'pct' => 100, 'phase' => 'Sync complete!', 'completed_at' => now()->toIso8601String()], now()->addMinutes(10));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SyncJiraTasksJob failed for employee ' . $this->employee->id . ': ' . $e->getMessage());
            Cache::put($cacheKey, ['status' => 'failed', 'pct' => 0, 'phase' => 'Sync failed: ' . $e->getMessage()], now()->addMinutes(5));
        }
    }

    /** Extract plain text from Atlassian Document Format (ADF) JSON */
    private function extractAdfText(?array $adf): string
    {
        if (empty($adf['content'])) {
            return '';
        }
        $text = '';
        array_walk_recursive($adf['content'], function ($value, $key) use (&$text) {
            if ($key === 'text' && is_string($value)) {
                $text .= $value . ' ';
            }
        });
        return trim($text);
    }
}
