<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeJiraTask;
use App\Models\EmployeeSignal;
use App\Models\EmployeeZohoTask;
use App\Models\SprintSheet;
use Illuminate\Support\Carbon;

class SignalComputer
{
    /**
     * Compute raw signals from local data (Jira tasks, Zoho tasks, sprint sheets)
     * for a given employee and period.
     */
    public function computeForEmployee(Employee $employee, string $period): array
    {
        $signals = [];

        // Compute Jira signals
        $jiraSignals = $this->computeJiraSignals($employee, $period);
        $signals = array_merge($signals, $jiraSignals);

        // Compute Zoho Projects signals
        $zohoSignals = $this->computeZohoSignals($employee, $period);
        $signals = array_merge($signals, $zohoSignals);

        // Compute Sprint Sheet signals
        $sprintSignals = $this->computeSprintSignals($employee, $period);
        $signals = array_merge($signals, $sprintSignals);

        return $signals;
    }

    private function computeJiraSignals(Employee $employee, string $period): array
    {
        $signals = [];
        $tasks = $employee->jiraTasks;

        if ($tasks->isEmpty()) {
            return $signals;
        }

        $total = $tasks->count();
        $completed = $tasks->whereNotNull('completed_at')->count();

        // Task completion rate
        $signals[] = [
            'metric_key' => 'task_completion_rate',
            'metric_value' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'metric_unit' => 'percent',
            'source_type' => 'jira',
        ];

        // Average story points
        $avgPoints = $tasks->avg('story_points');
        if ($avgPoints !== null) {
            $signals[] = [
                'metric_key' => 'avg_story_points',
                'metric_value' => round($avgPoints, 2),
                'metric_unit' => 'points',
                'source_type' => 'jira',
            ];
        }

        // Story points velocity (completed points)
        $completedPoints = $tasks->whereNotNull('completed_at')->sum('story_points');
        $signals[] = [
            'metric_key' => 'story_points_velocity',
            'metric_value' => $completedPoints ?? 0,
            'metric_unit' => 'points',
            'source_type' => 'jira',
        ];

        // Priority distribution (high priority tasks ratio)
        $highPriority = $tasks->whereIn('priority', ['High', 'Highest', 'Critical'])->count();
        $signals[] = [
            'metric_key' => 'high_priority_ratio',
            'metric_value' => $total > 0 ? round(($highPriority / $total) * 100, 2) : 0,
            'metric_unit' => 'percent',
            'source_type' => 'jira',
        ];

        return $signals;
    }

    private function computeZohoSignals(Employee $employee, string $period): array
    {
        $signals = [];
        $tasks = $employee->zohoTasks ?? collect();

        if ($tasks->isEmpty()) {
            return $signals;
        }

        $total = $tasks->count();
        $completed = $tasks->whereNotNull('completed_at')->count();

        $signals[] = [
            'metric_key' => 'zoho_task_completion_rate',
            'metric_value' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'metric_unit' => 'percent',
            'source_type' => 'zoho_projects',
        ];

        // Work distribution across projects
        $projectCount = $tasks->pluck('project_name')->unique()->filter()->count();
        $signals[] = [
            'metric_key' => 'project_spread',
            'metric_value' => $projectCount,
            'metric_unit' => 'count',
            'source_type' => 'zoho_projects',
        ];

        return $signals;
    }

    private function computeSprintSignals(Employee $employee, string $period): array
    {
        $signals = [];
        $sheets = SprintSheet::where('employee_id', $employee->id)
            ->where('organization_id', $employee->organization_id)
            ->get();

        if ($sheets->isEmpty()) {
            return $signals;
        }

        // Planning accuracy
        $totalPlanned = $sheets->sum('planned_points');
        $totalCompleted = $sheets->sum('completed_points');

        if ($totalPlanned > 0) {
            $signals[] = [
                'metric_key' => 'planning_accuracy',
                'metric_value' => round(($totalCompleted / $totalPlanned) * 100, 2),
                'metric_unit' => 'percent',
                'source_type' => 'sprint_sheet',
            ];
        }

        // Task completion ratio from sprints
        $tasksPlanned = $sheets->sum('tasks_planned');
        $tasksCompleted = $sheets->sum('tasks_completed');

        if ($tasksPlanned > 0) {
            $signals[] = [
                'metric_key' => 'sprint_task_completion',
                'metric_value' => round(($tasksCompleted / $tasksPlanned) * 100, 2),
                'metric_unit' => 'percent',
                'source_type' => 'sprint_sheet',
            ];
        }

        // Over-allocation indicator
        $overAllocated = $sheets->filter(function ($s) {
            return $s->planned_points > 0 && ($s->completed_points / $s->planned_points) < 0.5;
        })->count();

        $signals[] = [
            'metric_key' => 'over_allocation_count',
            'metric_value' => $overAllocated,
            'metric_unit' => 'count',
            'source_type' => 'sprint_sheet',
        ];

        return $signals;
    }
}
