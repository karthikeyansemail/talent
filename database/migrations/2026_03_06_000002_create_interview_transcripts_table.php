<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained('interview_sessions')->cascadeOnDelete();
            $table->enum('speaker', ['interviewer', 'candidate']);
            $table->text('text');
            $table->unsignedInteger('offset_seconds');
            $table->float('confidence')->nullable();
            $table->timestamps();

            $table->index(['interview_session_id', 'offset_seconds']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_transcripts');
    }
};
