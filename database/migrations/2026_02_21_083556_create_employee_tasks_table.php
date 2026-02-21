<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('connection_id')->nullable()->constrained('integration_connections')->nullOnDelete();
            // Source identification
            $table->string('source_type');          // jira | zoho_projects | devops_boards | github_projects
            $table->string('external_id');           // task key / issue number / work item ID from source
            // Normalized task fields (same across all sources)
            $table->string('title');                 // summary / name / title
            $table->text('description')->nullable();
            $table->string('task_type')->nullable(); // Story / Task / Bug / Issue / UserStory
            $table->string('status')->nullable();
            $table->string('priority')->nullable();
            $table->decimal('story_points', 5, 1)->nullable();
            $table->string('assignee_email')->nullable();
            $table->json('labels')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('source_created_at')->nullable();
            $table->json('metadata')->nullable();    // source-specific extras (sprint_name, component, etc.)
            $table->timestamps();

            $table->unique(['employee_id', 'source_type', 'external_id']);
            $table->index(['organization_id', 'source_type']);
            $table->index(['employee_id', 'source_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_tasks');
    }
};
