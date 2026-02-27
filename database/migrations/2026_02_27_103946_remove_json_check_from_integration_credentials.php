<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The credentials column is cast as encrypted:array in the model, which stores
     * an encrypted base64 string — not raw JSON. The JSON_VALID check constraint
     * must be removed so encrypted credentials can be saved.
     * (The config column keeps its JSON_VALID constraint; it uses plain array cast.)
     */
    public function up(): void
    {
        // Re-declare the column without the CHECK (json_valid(...)) constraint.
        DB::statement("ALTER TABLE `integration_connections` MODIFY `credentials` longtext DEFAULT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `integration_connections` MODIFY `credentials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`credentials`))");
    }
};
