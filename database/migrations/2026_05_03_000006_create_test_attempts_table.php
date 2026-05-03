<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aptitude_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('placement_drive_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // Student identification (no login required for test-takers)
            $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('student_email');                // captured on landing page
            $table->string('student_name');
            $table->string('student_enrollment')->nullable();
            $table->ipAddress('ip_address')->nullable();

            // Timing
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('time_taken_seconds')->nullable();

            // Scoring (filled after auto-grade + AI grade pass)
            $table->unsignedInteger('total_marks_available')->default(0);
            $table->decimal('score_obtained', 6, 2)->default(0);
            $table->decimal('score_pct', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->string('grading_status', 16)->default('pending'); // pending | grading | complete | failed

            $table->timestamps();

            $table->index(['aptitude_test_id', 'student_email']);
            $table->index(['organization_id', 'placement_drive_id']);
        });

        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_question_id')->constrained()->cascadeOnDelete();

            // MCQ answer: selected option index. Descriptive: prose stored in answer_text.
            $table->unsignedInteger('selected_option')->nullable();
            $table->text('answer_text')->nullable();

            // Grading
            $table->decimal('marks_awarded', 5, 2)->default(0);
            $table->boolean('is_correct')->nullable();        // null for descriptive (use score)
            $table->decimal('understanding_score', 5, 2)->nullable(); // 0-100, descriptive only
            $table->text('ai_feedback')->nullable();          // brief feedback for descriptive
            $table->json('rubric_coverage')->nullable();      // which rubric points were hit

            $table->timestamps();

            $table->index('test_attempt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_answers');
        Schema::dropIfExists('test_attempts');
    }
};
