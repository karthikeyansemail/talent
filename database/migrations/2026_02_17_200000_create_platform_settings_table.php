<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed default branding
        DB::table('platform_settings')->insert([
            ['key' => 'app_name', 'value' => 'Nalam Compass', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'app_name_short', 'value' => 'Nalam', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'app_name_accent', 'value' => 'Compass', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'app_logo_path', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
