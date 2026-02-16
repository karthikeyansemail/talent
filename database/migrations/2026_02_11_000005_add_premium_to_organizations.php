<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('is_premium')->default(false)->after('is_active');
            $table->timestamp('premium_expires_at')->nullable()->after('is_premium');
            $table->json('premium_features')->nullable()->after('premium_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['is_premium', 'premium_expires_at', 'premium_features']);
        });
    }
};
