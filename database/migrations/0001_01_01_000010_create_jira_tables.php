<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jira_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('jira_base_url');
            $table->string('jira_email');
            $table->text('jira_api_token');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_jira_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('jira_connection_id')->constrained('jira_connections')->cascadeOnDelete();
            $table->string('jira_task_key');
            $table->string('summary');
            $table->text('description')->nullable();
            $table->string('task_type')->nullable();
            $table->string('status')->nullable();
            $table->string('priority')->nullable();
            $table->json('labels')->nullable();
            $table->json('components')->nullable();
            $table->decimal('story_points', 5, 1)->nullable();
            $table->string('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_in_jira_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'jira_task_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_jira_tasks');
        Schema::dropIfExists('jira_connections');
    }
};
