<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained('job_postings')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('resume_id')->constrained('resumes')->cascadeOnDelete();
            $table->enum('stage', [
                'applied', 'ai_shortlisted', 'hr_screening',
                'technical_round_1', 'technical_round_2',
                'offer', 'hired', 'rejected'
            ])->default('applied');
            $table->text('stage_notes')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->decimal('ai_score', 5, 2)->nullable();
            $table->json('ai_analysis')->nullable();
            $table->timestamp('ai_analyzed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['job_posting_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
