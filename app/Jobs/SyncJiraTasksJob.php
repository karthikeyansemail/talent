<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\EmployeeJiraTask;
use App\Models\JiraConnection;
use App\Services\AiServiceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SyncJiraTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public Employee $employee) {}

    public function handle(): void
    {
        $connection = JiraConnection::where('organization_id', $this->employee->organization_id)
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            return;
        }

        try {
            $jql = urlencode('assignee="' . $this->employee->email . '" ORDER BY updated DESC');
            $response = Http::withBasicAuth($connection->jira_email, $connection->jira_api_token)
                ->get($connection->jira_base_url . '/rest/api/2/search', [
                    'jql' => 'assignee="' . $this->employee->email . '" ORDER BY updated DESC',
                    'maxResults' => 100,
                    'fields' => 'summary,description,issuetype,status,priority,labels,components,customfield_10016,resolution,resolutiondate,created',
                ]);

            if (!$response->successful()) {
                return;
            }

            $issues = $response->json('issues', []);

            foreach ($issues as $issue) {
                $fields = $issue['fields'];
                EmployeeJiraTask::updateOrCreate(
                    ['employee_id' => $this->employee->id, 'jira_task_key' => $issue['key']],
                    [
                        'jira_connection_id' => $connection->id,
                        'summary' => $fields['summary'] ?? '',
                        'description' => $fields['description'] ?? '',
                        'task_type' => $fields['issuetype']['name'] ?? null,
                        'status' => $fields['status']['name'] ?? null,
                        'priority' => $fields['priority']['name'] ?? null,
                        'labels' => $fields['labels'] ?? [],
                        'components' => collect($fields['components'] ?? [])->pluck('name')->toArray(),
                        'story_points' => $fields['customfield_10016'] ?? null,
                        'resolution' => $fields['resolution']['name'] ?? null,
                        'resolved_at' => $fields['resolutiondate'] ?? null,
                        'created_in_jira_at' => $fields['created'] ?? null,
                    ]
                );
            }

            // Extract skill signals from Jira tasks via AI
            $tasks = $this->employee->jiraTasks()->get();
            if ($tasks->isNotEmpty()) {
                $client = new AiServiceClient();
                $result = $client->extractJiraSignals([
                    'employee_name' => $this->employee->full_name,
                    'tasks' => $tasks->map(fn($t) => [
                        'key' => $t->jira_task_key,
                        'summary' => $t->summary,
                        'description' => $t->description ?? '',
                        'type' => $t->task_type ?? 'Task',
                        'status' => $t->status ?? 'Done',
                        'priority' => $t->priority ?? 'Medium',
                        'labels' => $t->labels ?? [],
                        'story_points' => $t->story_points,
                        'resolved_at' => $t->resolved_at?->toDateString(),
                    ])->toArray(),
                ], $this->employee->organization_id);

                if (isset($result['extracted_skills'])) {
                    $this->employee->update(['skills_from_jira' => $result]);
                }
            }
        } catch (\Exception $e) {
            // Log error silently
        }
    }
}
