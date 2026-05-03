<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // ISO 4217 currency code (USD, EUR, GBP, INR, AED, AUD, SGD, JPY, etc.)
            // Display only — we don't convert values, just format with the right symbol.
            $table->string('currency', 3)->default('USD')->after('domain');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
