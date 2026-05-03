<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('placement_drives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('company_name');
            $table->string('role_title');                     // "Software Engineer", "Business Analyst"
            $table->text('description')->nullable();          // raw JD or company brief
            $table->json('eligible_courses')->nullable();     // ["B.Tech CSE", "B.Tech IT", "MCA"]
            $table->json('eligible_batch_years')->nullable(); // [2026, 2027]
            $table->decimal('min_cgpa', 3, 2)->nullable();    // 7.50
            $table->json('required_skills')->nullable();      // ["DSA", "OOP", "DBMS"]
            $table->unsignedInteger('package_lpa')->nullable(); // package in lakhs per annum

            $table->date('drive_date')->nullable();
            $table->string('test_format', 32)->default('aptitude'); // aptitude | aptitude_plus_interview | technical
            $table->string('status', 16)->default('draft');         // draft | open | closed | completed

            // Document upload (parsed by AI for drive details)
            $table->string('source_doc_path')->nullable();
            $table->text('source_doc_text')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_drives');
    }
};
