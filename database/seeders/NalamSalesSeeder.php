<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

/**
 * Nalam Sales — CRM-driven demo org (industry_template='sales').
 * Auto-enables: hiring + interviews + crm
 *
 * 5 sales reps with realistic CRM activity profiles.
 * Run: php artisan db:seed --class=NalamSalesSeeder
 */
class NalamSalesSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::create([
            'name'                     => 'Nalam Sales',
            'slug'                     => 'nalam-sales',
            'domain'                   => 'nalamsales.work',
            'is_active'                => true,
            'is_premium'               => true,
            'subscription_plan'        => 'cloud_enterprise',
            'subscription_expires_at'  => '2027-12-31 23:59:59',
            'premium_expires_at'       => '2027-12-31 23:59:59',
            'industry_template'        => 'sales',
            'enabled_modules'          => ['hiring', 'interviews', 'crm'],
        ]);

        $this->command->info("Created organization: Nalam Sales (ID: {$org->id})");

        // ── Users ─────────────────────────────────────────────────────
        $arjun = User::create([
            'name'            => 'Arjun Mehra',
            'email'           => 'arjun.mehra@nalamsales.work',
            'password'        => 'NalamDemo@Sales1',
            'role'            => 'org_admin',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $arjun->id, 'role' => 'org_admin']);

        $priya = User::create([
            'name'            => 'Priya Iyer',
            'email'           => 'priya.iyer@nalamsales.work',
            'password'        => 'NalamDemo@Sales1',
            'role'            => 'hr_manager',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $priya->id, 'role' => 'hr_manager']);

        $rahul = User::create([
            'name'            => 'Rahul Verma',
            'email'           => 'rahul.verma@nalamsales.work',
            'password'        => 'NalamDemo@Sales1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $rahul->id, 'role' => 'employee']);

        $deepa = User::create([
            'name'            => 'Deepa Singh',
            'email'           => 'deepa.singh@nalamsales.work',
            'password'        => 'NalamDemo@Sales1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $deepa->id, 'role' => 'employee']);

        $vikram = User::create([
            'name'            => 'Vikram Joshi',
            'email'           => 'vikram.joshi@nalamsales.work',
            'password'        => 'NalamDemo@Sales1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $vikram->id, 'role' => 'employee']);

        $this->command->info("Created 5 users");

        // ── Departments ───────────────────────────────────────────────
        $sales = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Sales',
            'description'     => 'Inside sales, field sales, and account management',
        ]);

        $marketing = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Marketing',
            'description'     => 'Demand generation, content, and brand',
        ]);

        $cs = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Customer Success',
            'description'     => 'Onboarding, retention, and renewals',
        ]);

        $this->command->info("Created 3 departments");

        // ── Employees ─────────────────────────────────────────────────
        Employee::create([
            'user_id'         => $arjun->id,
            'organization_id' => $org->id,
            'first_name'      => 'Arjun',
            'last_name'       => 'Mehra',
            'email'           => 'arjun.mehra@nalamsales.work',
            'department_id'   => $sales->id,
            'designation'     => 'VP Sales',
            'is_active'       => true,
            'combined_skill_profile' => ['Salesforce', 'Pipeline Management', 'Enterprise Sales', 'SaaS', 'Negotiation', 'Forecasting'],
        ]);

        Employee::create([
            'user_id'         => $priya->id,
            'organization_id' => $org->id,
            'first_name'      => 'Priya',
            'last_name'       => 'Iyer',
            'email'           => 'priya.iyer@nalamsales.work',
            'department_id'   => $sales->id,
            'designation'     => 'Senior Account Executive',
            'is_active'       => true,
            'combined_skill_profile' => ['HubSpot', 'Mid-Market', 'Cold Outreach', 'Demo Skills', 'Account Planning'],
        ]);

        Employee::create([
            'user_id'         => $rahul->id,
            'organization_id' => $org->id,
            'first_name'      => 'Rahul',
            'last_name'       => 'Verma',
            'email'           => 'rahul.verma@nalamsales.work',
            'department_id'   => $sales->id,
            'designation'     => 'Account Executive',
            'is_active'       => true,
            'combined_skill_profile' => ['Salesforce', 'SMB', 'Discovery Calls', 'Pipeline Hygiene'],
        ]);

        Employee::create([
            'user_id'         => $deepa->id,
            'organization_id' => $org->id,
            'first_name'      => 'Deepa',
            'last_name'       => 'Singh',
            'email'           => 'deepa.singh@nalamsales.work',
            'department_id'   => $cs->id,
            'designation'     => 'Customer Success Manager',
            'is_active'       => true,
            'combined_skill_profile' => ['Renewals', 'Customer Health', 'Upsell', 'Product Training'],
        ]);

        Employee::create([
            'user_id'         => $vikram->id,
            'organization_id' => $org->id,
            'first_name'      => 'Vikram',
            'last_name'       => 'Joshi',
            'email'           => 'vikram.joshi@nalamsales.work',
            'department_id'   => $sales->id,
            'designation'     => 'Sales Development Representative',
            'is_active'       => true,
            'combined_skill_profile' => ['Outreach', 'LinkedIn Sales Nav', 'Cold Email', 'Lead Qualification', 'BANT'],
        ]);

        $this->command->info("Created 5 sales employees");

        $this->command->info("Nalam Sales seeding complete. Run `php artisan demo:refresh --org=6` to populate CRM signals.");
    }
}
