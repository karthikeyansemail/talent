<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->enum('subscription_plan', ['free', 'cloud_enterprise', 'self_hosted'])
                  ->default('free')
                  ->after('is_premium');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_plan');
            $table->timestamp('support_expires_at')->nullable()->after('subscription_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'subscription_expires_at', 'support_expires_at']);
        });
    }
};
