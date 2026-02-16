<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('import_source')->default('manual')->after('is_active'); // manual, spreadsheet, zoho_people
            $table->string('external_id')->nullable()->after('import_source');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['import_source', 'external_id']);
        });
    }
};
