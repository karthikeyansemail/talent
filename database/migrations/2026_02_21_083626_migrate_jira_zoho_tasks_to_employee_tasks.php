<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // --- Migrate employee_jira_tasks → employee_tasks ---
        $jiraTasks = DB::table('employee_jira_tasks')->get();
        foreach ($jiraTasks as $task) {
            $employee = DB::table('employees')->where('id', $task->employee_id)->first();
            if (!$employee) continue;

            $labels = null;
            if ($task->labels) {
                $decoded = json_decode($task->labels, true);
                $labels = is_array($decoded) ? json_encode($decoded) : null;
            }

            DB::table('employee_tasks')->insertOrIgnore([
                'employee_id'       => $task->employee_id,
                'organization_id'   => $employee->organization_id,
                'connection_id'     => null,
                'source_type'       => 'jira',
                'external_id'       => $task->jira_task_key,
                'title'             => $task->summary,
                'description'       => $task->description,
                'task_type'         => $task->task_type,
                'status'            => $task->status,
                'priority'          => $task->priority,
                'story_points'      => $task->story_points,
                'assignee_email'    => $employee->email,
                'labels'            => $labels,
                'completed_at'      => $task->resolved_at,
                'source_created_at' => $task->created_in_jira_at,
                'metadata'          => json_encode(['resolution' => $task->resolution]),
                'created_at'        => $task->created_at,
                'updated_at'        => $task->updated_at,
            ]);
        }

        // --- Migrate employee_zoho_tasks → employee_tasks ---
        if (DB::getSchemaBuilder()->hasTable('employee_zoho_tasks')) {
            $zohoTasks = DB::table('employee_zoho_tasks')->get();
            foreach ($zohoTasks as $task) {
                $employee = DB::table('employees')->where('id', $task->employee_id)->first();
                if (!$employee) continue;

                $labels = null;
                if (isset($task->tags) && $task->tags) {
                    $decoded = json_decode($task->tags, true);
                    $labels = is_array($decoded) ? json_encode($decoded) : null;
                }

                DB::table('employee_tasks')->insertOrIgnore([
                    'employee_id'       => $task->employee_id,
                    'organization_id'   => $employee->organization_id,
                    'connection_id'     => null,
                    'source_type'       => 'zoho_projects',
                    'external_id'       => $task->task_key,
                    'title'             => $task->summary,
                    'description'       => $task->description,
                    'task_type'         => null,
                    'status'            => $task->status,
                    'priority'          => $task->priority,
                    'story_points'      => null,
                    'assignee_email'    => $employee->email,
                    'labels'            => $labels,
                    'completed_at'      => $task->completed_at,
                    'source_created_at' => $task->created_in_zoho_at ?? null,
                    'metadata'          => json_encode(['project_name' => $task->project_name]),
                    'created_at'        => $task->created_at,
                    'updated_at'        => $task->updated_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove migrated records (source_type jira/zoho_projects) — original tables still intact
        DB::table('employee_tasks')->whereIn('source_type', ['jira', 'zoho_projects'])->delete();
    }
};
