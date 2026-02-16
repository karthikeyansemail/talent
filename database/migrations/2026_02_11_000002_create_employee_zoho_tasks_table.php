<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_zoho_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('zoho_connection_id')->constrained('zoho_projects_connections')->cascadeOnDelete();
            $table->string('task_key');
            $table->string('project_name')->nullable();
            $table->string('summary');
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->string('priority')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_in_zoho_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'task_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_zoho_tasks');
    }
};
