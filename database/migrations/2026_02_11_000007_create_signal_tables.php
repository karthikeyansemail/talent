<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Signal sources configured per organization
        Schema::create('signal_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('type'); // jira, zoho_projects, slack, teams, sprint_sheet
            $table->string('name');
            $table->json('config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Individual metric data points per employee per period
        Schema::create('employee_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('source_type'); // jira, zoho_projects, slack, sprint_sheet
            $table->string('metric_key'); // task_completion_rate, avg_cycle_time, response_time, etc.
            $table->decimal('metric_value', 12, 4);
            $table->string('metric_unit')->nullable(); // percent, hours, days, count
            $table->string('period'); // 2026-W06, 2026-01, 2026-Q1
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'metric_key', 'period']);
            $table->index(['organization_id', 'period']);
        });

        // AI-computed meta-signals aggregation per employee
        Schema::create('signal_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('period'); // 2026-W06, 2026-01
            $table->decimal('consistency_index', 5, 2)->nullable();
            $table->decimal('recovery_signal', 5, 2)->nullable();
            $table->decimal('workload_pressure', 5, 2)->nullable();
            $table->decimal('context_switching_index', 5, 2)->nullable();
            $table->decimal('collaboration_density', 5, 2)->nullable();
            $table->json('raw_signals')->nullable(); // all raw signal data used
            $table->json('ai_analysis')->nullable(); // full AI response
            $table->text('ai_summary')->nullable(); // brief objective summary
            $table->timestamps();

            $table->unique(['employee_id', 'period']);
            $table->index(['organization_id', 'period']);
        });

        // Uploaded sprint spreadsheet data
        Schema::create('sprint_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('sprint_name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->integer('planned_points')->nullable();
            $table->integer('completed_points')->nullable();
            $table->integer('tasks_planned')->nullable();
            $table->integer('tasks_completed')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'sprint_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sprint_sheets');
        Schema::dropIfExists('signal_snapshots');
        Schema::dropIfExists('employee_signals');
        Schema::dropIfExists('signal_sources');
    }
};
