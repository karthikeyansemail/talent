<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectResourceMatch;
use App\Models\ProjectSprintSheet;
use App\Services\AiServiceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MatchProjectResourcesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(
        public Project $project,
        public ?array $employeeIds = null,
    ) {}

    public function handle(): void
    {
        $query = Employee::where('organization_id', $this->project->organization_id)
            ->where('is_active', true);

        if ($this->employeeIds !== null) {
            $query->whereIn('id', $this->employeeIds);
        }

        $employees = $query->get();

        if ($employees->isEmpty()) {
            return;
        }

        // Load current project assignments for context
        $assignments = ProjectResourceMatch::where('is_assigned', true)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->with('project:id,name')
            ->get()
            ->groupBy('employee_id');

        // Gather sprint sheet data as additional context
        $sprintData = ProjectSprintSheet::where('project_id', $this->project->id)
            ->where('status', 'parsed')
            ->get()
            ->map(fn($sheet) => [
                'filename' => $sheet->original_filename,
                'summary' => $sheet->parsed_summary ?? [],
            ])
            ->toArray();

        $client = new AiServiceClient();
        $payload = [
            'project' => [
                'name' => $this->project->name,
                'description' => $this->project->description ?? '',
                'required_skills' => $this->project->required_skills ?? [],
                'required_technologies' => $this->project->required_technologies ?? [],
                'complexity_level' => $this->project->complexity_level ?? 'medium',
                'domain_context' => $this->project->domain_context ?? '',
            ],
            'employees' => $employees->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->full_name,
                'skills_from_resume' => (object)($e->skills_from_resume ?? []),
                'skills_from_jira' => (object)($e->skills_from_jira ?? []),
                'combined_skill_profile' => (object)($e->combined_skill_profile ?? []),
                'current_projects' => ($assignments[$e->id] ?? collect())
                    ->pluck('project.name')
                    ->filter()
                    ->values()
                    ->toArray(),
            ])->toArray(),
        ];

        if (!empty($sprintData)) {
            $payload['sprint_data'] = $sprintData;
        }

        $result = $client->matchProjectResources($payload, $this->project->organization_id);

        if (isset($result['matches'])) {
            // Clear old matches
            ProjectResourceMatch::where('project_id', $this->project->id)->where('is_assigned', false)->delete();

            foreach ($result['matches'] as $match) {
                ProjectResourceMatch::updateOrCreate(
                    ['project_id' => $this->project->id, 'employee_id' => $match['employee_id']],
                    [
                        'match_score' => $match['match_score'] ?? 0,
                        'strength_areas' => $match['strength_areas'] ?? [],
                        'skill_gaps' => $match['skill_gaps'] ?? [],
                        'explanation' => $match['explanation'] ?? '',
                    ]
                );
            }
        }
    }
}
