<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30);
            $table->timestamps();
            $table->unique(['user_id', 'role']);
        });

        // Migrate existing role data from users table into pivot
        DB::table('users')->whereNotNull('role')->orderBy('id')->each(function ($user) {
            if ($user->role === 'management') {
                // management was a workaround — split into hr_manager + resource_manager
                DB::table('role_user')->insert([
                    ['user_id' => $user->id, 'role' => 'hr_manager', 'created_at' => now(), 'updated_at' => now()],
                    ['user_id' => $user->id, 'role' => 'resource_manager', 'created_at' => now(), 'updated_at' => now()],
                ]);
            } else {
                DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
