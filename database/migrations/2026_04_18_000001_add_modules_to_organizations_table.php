<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // 'industry_template' picks defaults for enabled_modules.
            // Values: 'software', 'sales', 'support', 'hybrid', 'custom'.
            // Null = legacy org, treated as 'software' (full access).
            $table->string('industry_template', 32)->nullable()->after('settings');

            // 'enabled_modules' is a JSON array of module keys: ['hiring','interviews','work_signals','resource_allocation','crm','customer_support'].
            // Null = legacy org, all modules enabled (backward compatible).
            $table->json('enabled_modules')->nullable()->after('industry_template');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['industry_template', 'enabled_modules']);
        });
    }
};
