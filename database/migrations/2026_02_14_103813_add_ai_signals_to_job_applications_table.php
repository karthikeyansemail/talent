<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->json('ai_signals')->nullable()->after('ai_analysis');
            $table->unsignedInteger('ai_score_version')->nullable()->after('ai_signals');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['ai_signals', 'ai_score_version']);
        });
    }
};
