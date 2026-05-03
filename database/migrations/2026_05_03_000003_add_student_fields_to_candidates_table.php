<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Education vertical reuses the candidates table for students (same shape:
 * person, email, resume, skills) and adds optional student-specific fields.
 * For non-education orgs these stay null and are ignored.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('enrollment_number', 64)->nullable()->after('phone');
            $table->string('course', 128)->nullable()->after('enrollment_number');
            $table->unsignedSmallInteger('batch_year')->nullable()->after('course');

            $table->index(['organization_id', 'enrollment_number']);
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'enrollment_number']);
            $table->dropColumn(['enrollment_number', 'course', 'batch_year']);
        });
    }
};
