<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained('interview_sessions')->cascadeOnDelete();
            $table->text('question_text');
            $table->string('question_type', 30)->default('follow_up');
            $table->string('difficulty', 15)->default('medium');
            $table->string('skill_area', 100)->nullable();
            $table->text('answer_text')->nullable();
            $table->json('evaluation')->nullable();
            $table->enum('status', ['suggested', 'asked', 'answered', 'skipped'])->default('suggested');
            $table->unsignedInteger('suggested_at_offset')->nullable();
            $table->unsignedInteger('asked_at_offset')->nullable();
            $table->timestamps();

            $table->index('interview_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_questions');
    }
};
