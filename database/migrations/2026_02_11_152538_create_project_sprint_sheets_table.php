<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_sprint_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('file_path');
            $table->unsignedInteger('file_size')->default(0);
            $table->unsignedInteger('row_count')->default(0);
            $table->json('parsed_summary')->nullable();
            $table->enum('status', ['uploaded', 'parsed', 'failed'])->default('uploaded');
            $table->string('error_message')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_sprint_sheets');
    }
};
