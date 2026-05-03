<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

/**
 * Nalam Support — BPO/customer-support demo org (industry_template='support').
 * Auto-enables: hiring + interviews + work_signals + customer_support
 *
 * 5 support agents with realistic ticket activity profiles.
 * Run: php artisan db:seed --class=NalamSupportSeeder
 */
class NalamSupportSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::create([
            'name'                     => 'Nalam Support',
            'slug'                     => 'nalam-support',
            'domain'                   => 'nalamsupport.work',
            'is_active'                => true,
            'is_premium'               => true,
            'subscription_plan'        => 'cloud_enterprise',
            'subscription_expires_at'  => '2027-12-31 23:59:59',
            'premium_expires_at'       => '2027-12-31 23:59:59',
            'industry_template'        => 'support',
            'enabled_modules'          => ['hiring', 'interviews', 'work_signals', 'customer_support'],
        ]);

        $this->command->info("Created organization: Nalam Support (ID: {$org->id})");

        // ── Users ─────────────────────────────────────────────────────
        $sundar = User::create([
            'name'            => 'Sundar Pillai',
            'email'           => 'sundar.pillai@nalamsupport.work',
            'password'        => 'NalamDemo@Support1',
            'role'            => 'org_admin',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $sundar->id, 'role' => 'org_admin']);

        $aisha = User::create([
            'name'            => 'Aisha Banu',
            'email'           => 'aisha.banu@nalamsupport.work',
            'password'        => 'NalamDemo@Support1',
            'role'            => 'hr_manager',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $aisha->id, 'role' => 'hr_manager']);

        $ramesh = User::create([
            'name'            => 'Ramesh Kannan',
            'email'           => 'ramesh.kannan@nalamsupport.work',
            'password'        => 'NalamDemo@Support1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $ramesh->id, 'role' => 'employee']);

        $fatima = User::create([
            'name'            => 'Fatima Rahman',
            'email'           => 'fatima.rahman@nalamsupport.work',
            'password'        => 'NalamDemo@Support1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $fatima->id, 'role' => 'employee']);

        $arjun = User::create([
            'name'            => 'Arjun Bhat',
            'email'           => 'arjun.bhat@nalamsupport.work',
            'password'        => 'NalamDemo@Support1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $arjun->id, 'role' => 'employee']);

        $this->command->info("Created 5 users");

        // ── Departments ───────────────────────────────────────────────
        $tier1 = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Tier 1 Support',
            'description'     => 'First-line customer support, common issues',
        ]);

        $tier2 = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Tier 2 Support',
            'description'     => 'Technical escalations and complex issues',
        ]);

        $qa = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Quality & Training',
            'description'     => 'QA monitoring, training, knowledge base',
        ]);

        $this->command->info("Created 3 departments");

        // ── Employees ─────────────────────────────────────────────────
        Employee::create([
            'user_id'         => $sundar->id,
            'organization_id' => $org->id,
            'first_name'      => 'Sundar',
            'last_name'       => 'Pillai',
            'email'           => 'sundar.pillai@nalamsupport.work',
            'department_id'   => $tier2->id,
            'designation'     => 'Support Operations Lead',
            'is_active'       => true,
            'combined_skill_profile' => ['Zendesk', 'Team Leadership', 'Escalation Management', 'SLA Tracking', 'Customer Success', 'Process Improvement'],
        ]);

        Employee::create([
            'user_id'         => $aisha->id,
            'organization_id' => $org->id,
            'first_name'      => 'Aisha',
            'last_name'       => 'Banu',
            'email'           => 'aisha.banu@nalamsupport.work',
            'department_id'   => $qa->id,
            'designation'     => 'Senior Quality Analyst',
            'is_active'       => true,
            'combined_skill_profile' => ['QA Monitoring', 'Coaching', 'Training Design', 'Knowledge Base', 'CSAT Analysis'],
        ]);

        Employee::create([
            'user_id'         => $ramesh->id,
            'organization_id' => $org->id,
            'first_name'      => 'Ramesh',
            'last_name'       => 'Kannan',
            'email'           => 'ramesh.kannan@nalamsupport.work',
            'department_id'   => $tier2->id,
            'designation'     => 'Senior Support Engineer',
            'is_active'       => true,
            'combined_skill_profile' => ['Freshdesk', 'Linux', 'API Debugging', 'Network Troubleshooting', 'Customer Empathy'],
        ]);

        Employee::create([
            'user_id'         => $fatima->id,
            'organization_id' => $org->id,
            'first_name'      => 'Fatima',
            'last_name'       => 'Rahman',
            'email'           => 'fatima.rahman@nalamsupport.work',
            'department_id'   => $tier1->id,
            'designation'     => 'Customer Support Specialist',
            'is_active'       => true,
            'combined_skill_profile' => ['Zendesk', 'Live Chat', 'Email Support', 'Multilingual (English/Tamil/Hindi)', 'Patience'],
        ]);

        Employee::create([
            'user_id'         => $arjun->id,
            'organization_id' => $org->id,
            'first_name'      => 'Arjun',
            'last_name'       => 'Bhat',
            'email'           => 'arjun.bhat@nalamsupport.work',
            'department_id'   => $tier1->id,
            'designation'     => 'Customer Support Associate',
            'is_active'       => true,
            'combined_skill_profile' => ['Freshdesk', 'Email Support', 'Order Fulfillment', 'Returns Processing'],
        ]);

        $this->command->info("Created 5 support employees");

        $this->command->info("Nalam Support seeding complete. Run `php artisan demo:refresh --org=7` to populate signals.");
    }
}
