<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_optimization_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->unsignedInteger('version_before');
            $table->unsignedInteger('version_after')->nullable();
            $table->unsignedInteger('applications_analyzed')->default(0);
            $table->decimal('correlation_before', 5, 4)->nullable();
            $table->decimal('correlation_after', 5, 4)->nullable();
            $table->decimal('mae_before', 5, 2)->nullable();
            $table->decimal('mae_after', 5, 2)->nullable();
            $table->json('weight_deltas')->nullable();
            $table->enum('status', ['completed', 'failed', 'skipped'])->default('completed');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_optimization_runs');
    }
};
