<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin','org_admin','hr_manager','hiring_manager','resource_manager','management','employee') NOT NULL DEFAULT 'employee'");
    }

    public function down(): void
    {
        // Change management users to employee before removing the value
        DB::statement("UPDATE users SET role = 'employee' WHERE role = 'management'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin','org_admin','hr_manager','hiring_manager','resource_manager','employee') NOT NULL DEFAULT 'employee'");
    }
};
