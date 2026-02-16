<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('weights_snapshot');
            $table->enum('trigger', ['manual', 'auto_optimization'])->default('manual');
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metrics_at_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_rule_versions');
    }
};
