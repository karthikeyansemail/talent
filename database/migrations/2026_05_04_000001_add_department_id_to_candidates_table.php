<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Education vertical needs department-level grouping for students
 * (Mechatronics, ECE, CSE, Marine Engineering, etc.). Reuses the
 * existing departments table that staff already use.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('batch_year')
                ->constrained('departments')
                ->nullOnDelete();

            $table->index(['organization_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'department_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
