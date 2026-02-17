<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->string('jd_file_path')->nullable()->after('closed_at');
            $table->string('jd_file_name')->nullable()->after('jd_file_path');
            $table->string('jd_file_type')->nullable()->after('jd_file_name');
            $table->longText('jd_extracted_text')->nullable()->after('jd_file_type');
            $table->text('key_responsibilities')->nullable()->after('requirements');
            $table->text('expectations')->nullable()->after('key_responsibilities');
            $table->text('skill_experience_details')->nullable()->after('nice_to_have_skills');
            $table->text('notes')->nullable()->after('skill_experience_details');
        });
    }

    public function down(): void
    {
        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn([
                'jd_file_path', 'jd_file_name', 'jd_file_type', 'jd_extracted_text',
                'key_responsibilities', 'expectations', 'skill_experience_details', 'notes',
            ]);
        });
    }
};
