<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->string('outcome', 20)->nullable()->after('status');
            $table->index(['organization_id', 'outcome']);
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'outcome']);
            $table->dropColumn('outcome');
        });
    }
};
