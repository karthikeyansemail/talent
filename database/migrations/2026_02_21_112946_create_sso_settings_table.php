<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sso_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();      // 'google' | 'microsoft' | 'okta'
            $table->boolean('is_enabled')->default(false);
            $table->text('client_id')->nullable();     // encrypted
            $table->text('client_secret')->nullable(); // encrypted
            $table->text('extra_config')->nullable();  // encrypted:array cast — must be text, not json
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sso_settings');
    }
};
