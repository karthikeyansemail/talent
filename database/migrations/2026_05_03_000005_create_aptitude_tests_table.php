<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aptitude_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_drive_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('time_limit_minutes')->default(30);
            $table->unsignedInteger('passing_score_pct')->default(60); // % to pass
            $table->string('public_token', 64)->unique();              // for /placement/test/{token}
            $table->string('status', 16)->default('draft');            // draft | published | closed

            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aptitude_test_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('order')->default(0);
            $table->string('type', 16)->default('mcq');     // mcq | descriptive
            $table->text('question_text');
            $table->text('context')->nullable();             // optional code snippet, scenario, image url
            $table->string('topic', 64)->nullable();         // "Quantitative", "Verbal", "Logic", "DSA"
            $table->string('difficulty', 16)->default('medium'); // easy | medium | hard
            $table->unsignedInteger('marks')->default(1);

            // MCQ-specific
            $table->json('options')->nullable();             // ["Option A", "Option B", ...]
            $table->unsignedInteger('correct_option')->nullable(); // index 0-based

            // Descriptive-specific
            $table->text('ideal_answer')->nullable();        // gold-standard answer
            $table->json('rubric_points')->nullable();       // ["Mentions X", "Explains Y", "Applies Z"]
            $table->unsignedInteger('expected_word_count')->nullable();

            $table->timestamps();

            $table->index('aptitude_test_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_questions');
        Schema::dropIfExists('aptitude_tests');
    }
};
