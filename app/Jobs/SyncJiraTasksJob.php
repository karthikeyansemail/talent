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
            $response = Http::withBasicAuth($connection->jira_email, $connection->jira_api_token)
                ->withHeaders(['Accept' => 'application/json'])
                ->timeout(60)
                ->get($connection->jira_base_url . '/rest/api/3/search/jql', [
                    'jql'        => 'assignee="' . $this->employee->email . '" ORDER BY updated DESC',
                    'maxResults' => 100,
                    'fields'     => 'summary,description,issuetype,status,priority,labels,components,customfield_10016,resolution,resolutiondate,created',
                ]);

            if (!$response->successful()) {
                return;
            }

            $issues = $response->json('issues', []);

            foreach ($issues as $issue) {
                $fields = $issue['fields'];
                $components = collect($fields['components'] ?? [])->pluck('name')->toArray();
                EmployeeTask::updateOrCreate(
                    [
                        'employee_id' => $this->employee->id,
                        'source_type' => 'jira',
                        'external_id' => $issue['key'],
                    ],
                    [
                        'organization_id'   => $this->employee->organization_id,
                        'connection_id'     => null, // Jira uses dedicated jira_connections table
                        'title'             => $fields['summary'] ?? '',
                        'description'       => is_array($fields['description'] ?? null)
                            ? $this->extractAdfText($fields['description'])
                            : ($fields['description'] ?? ''),
                        'task_type'         => $fields['issuetype']['name'] ?? null,
                        'status'            => $fields['status']['name'] ?? null,
                        'priority'          => $fields['priority']['name'] ?? null,
                        'labels'            => $fields['labels'] ?? [],
                        'story_points'      => $fields['customfield_10016'] ?? null,
                        'assignee_email'    => $this->employee->email,
                        'completed_at'      => $fields['resolutiondate'] ?? null,
                        'source_created_at' => $fields['created'] ?? null,
                        'metadata'          => [
                            'resolution'  => $fields['resolution']['name'] ?? null,
                            'components'  => $components,
                        ],
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
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('SyncJiraTasksJob failed for employee ' . $this->employee->id . ': ' . $e->getMessage());
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
