<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('required_skills')->nullable();
            $table->json('required_technologies')->nullable();
            $table->enum('complexity_level', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->text('domain_context')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['planning', 'active', 'completed', 'on_hold'])->default('planning');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('project_resource_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('match_score', 5, 2)->nullable();
            $table->json('strength_areas')->nullable();
            $table->json('skill_gaps')->nullable();
            $table->text('explanation')->nullable();
            $table->boolean('is_assigned')->default(false);
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_resource_matches');
        Schema::dropIfExists('projects');
    }
};
