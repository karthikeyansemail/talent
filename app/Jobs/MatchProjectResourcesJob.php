<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectResourceMatch;
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

    public function __construct(public Project $project) {}

    public function handle(): void
    {
        $employees = Employee::where('organization_id', $this->project->organization_id)
            ->where('is_active', true)
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $client = new AiServiceClient();
        $result = $client->matchProjectResources([
            'project' => [
                'name' => $this->project->name,
                'description' => $this->project->description ?? '',
                'required_skills' => $this->project->required_skills ?? [],
                'required_technologies' => $this->project->required_technologies ?? [],
                'complexity_level' => $this->project->complexity_level,
                'domain_context' => $this->project->domain_context ?? '',
            ],
            'employees' => $employees->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->full_name,
                'skills_from_resume' => $e->skills_from_resume ?? [],
                'skills_from_jira' => $e->skills_from_jira ?? [],
                'combined_skill_profile' => $e->combined_skill_profile ?? [],
            ])->toArray(),
        ], $this->project->organization_id);

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
