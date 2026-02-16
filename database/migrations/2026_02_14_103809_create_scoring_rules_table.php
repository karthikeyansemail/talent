<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('signal_key', 50);
            $table->string('signal_label', 100);
            $table->decimal('weight', 5, 4)->default(0.0000);
            $table->boolean('is_active')->default(true);
            $table->string('category', 30)->default('core');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'signal_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scoring_rules');
    }
};
