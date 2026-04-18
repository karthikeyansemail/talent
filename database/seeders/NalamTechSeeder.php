<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

/**
 * Seeder for Nalam Tech — Microsoft-stack demo org.
 * Uses Azure DevOps Boards (instead of Jira) and Microsoft Teams (instead of Slack).
 *
 * Run with: php artisan db:seed --class=NalamTechSeeder
 */
class NalamTechSeeder extends Seeder
{
    public function run(): void
    {
        // ── Organization ─────────────────────────────────────────────
        $org = Organization::create([
            'name'                     => 'Nalam Tech',
            'slug'                     => 'nalam-tech',
            'domain'                   => 'nalamtech.work',
            'is_active'                => true,
            'is_premium'               => true,
            'subscription_plan'        => 'cloud_enterprise',
            'subscription_expires_at'  => '2027-12-31 23:59:59',
            'premium_expires_at'       => '2027-12-31 23:59:59',
        ]);

        $this->command->info("Created organization: Nalam Tech (ID: {$org->id})");

        // ── Users ─────────────────────────────────────────────────────
        $kevin = User::create([
            'name'            => 'Kevin Lee',
            'email'           => 'kevin.lee@nalamtech.work',
            'password'        => 'NalamDemo@Tech1',
            'role'            => 'org_admin',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $kevin->id, 'role' => 'org_admin']);

        // Kevin is also HR manager (dual role for small team)
        $omar = User::create([
            'name'            => 'Omar Ali',
            'email'           => 'omar.ali@nalamtech.work',
            'password'        => 'NalamDemo@Tech1',
            'role'            => 'resource_manager',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $omar->id, 'role' => 'resource_manager']);

        // Employee-role user accounts
        $employeeUsers = [];
        foreach ([
            ['Neha Sharma',  'neha.sharma@nalamtech.work'],
            ['Nina Tan',     'nina.tan@nalamtech.work'],
            ['Maya Chen',    'maya.chen@nalamtech.work'],
        ] as $emp) {
            $u = User::create([
                'name'            => $emp[0],
                'email'           => $emp[1],
                'password'        => 'NalamDemo@Tech1',
                'role'            => 'employee',
                'organization_id' => $org->id,
                'is_active'       => true,
            ]);
            UserRole::create(['user_id' => $u->id, 'role' => 'employee']);
            $employeeUsers[$emp[1]] = $u;
        }

        $this->command->info('Created 5 user accounts');

        // ── Departments ───────────────────────────────────────────────
        $engineering = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Engineering',
            'description'     => 'Full-stack development and platform engineering',
        ]);

        $product = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Product',
            'description'     => 'Product management, design, and strategy',
        ]);

        $ops = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Operations',
            'description'     => 'DevOps, infrastructure, and cloud operations',
        ]);

        $this->command->info('Created 3 departments');

        // ── Employees ─────────────────────────────────────────────────

        // Kevin Lee — Org Admin / Tech Lead
        Employee::create([
            'organization_id'        => $org->id,
            'user_id'                => $kevin->id,
            'first_name'             => 'Kevin',
            'last_name'              => 'Lee',
            'email'                  => 'kevin.lee@nalamtech.work',
            'department_id'          => $engineering->id,
            'designation'            => 'Engineering Lead',
            'skills_from_resume'     => ['C#', '.NET', 'Azure', 'SQL Server', 'React', 'TypeScript', 'Docker', 'Kubernetes'],
            'combined_skill_profile' => [
                'top_skills' => ['C#', '.NET', 'Azure', 'React', 'Docker'],
                'experience' => '8+ years full-stack .NET development',
                'strengths'  => ['Architecture design', 'Azure cloud', 'Team leadership'],
            ],
            'is_active'       => true,
            'import_source'   => 'manual',
        ]);

        // Omar Ali — Resource/Project Manager
        Employee::create([
            'organization_id'        => $org->id,
            'user_id'                => $omar->id,
            'first_name'             => 'Omar',
            'last_name'              => 'Ali',
            'email'                  => 'omar.ali@nalamtech.work',
            'department_id'          => $product->id,
            'designation'            => 'Product Manager',
            'skills_from_resume'     => ['Agile', 'Scrum', 'Azure DevOps', 'Product Strategy', 'Stakeholder Management', 'Data Analysis'],
            'combined_skill_profile' => [
                'top_skills' => ['Agile', 'Azure DevOps', 'Product Strategy'],
                'experience' => '6 years product management',
                'strengths'  => ['Sprint planning', 'Backlog grooming', 'Cross-team coordination'],
            ],
            'is_active'       => true,
            'import_source'   => 'manual',
        ]);

        // Neha Sharma — Backend Developer
        Employee::create([
            'organization_id'        => $org->id,
            'user_id'                => $employeeUsers['neha.sharma@nalamtech.work']->id,
            'first_name'             => 'Neha',
            'last_name'              => 'Sharma',
            'email'                  => 'neha.sharma@nalamtech.work',
            'department_id'          => $engineering->id,
            'designation'            => 'Senior Backend Developer',
            'skills_from_resume'     => ['C#', '.NET Core', 'Azure Functions', 'SQL Server', 'Entity Framework', 'Redis', 'RabbitMQ', 'REST APIs'],
            'combined_skill_profile' => [
                'top_skills' => ['C#', '.NET Core', 'Azure Functions', 'SQL Server'],
                'experience' => '5 years .NET backend development',
                'strengths'  => ['API design', 'Database optimization', 'Microservices'],
            ],
            'is_active'       => true,
            'import_source'   => 'manual',
        ]);

        // Nina Tan — Frontend Developer
        Employee::create([
            'organization_id'        => $org->id,
            'user_id'                => $employeeUsers['nina.tan@nalamtech.work']->id,
            'first_name'             => 'Nina',
            'last_name'              => 'Tan',
            'email'                  => 'nina.tan@nalamtech.work',
            'department_id'          => $engineering->id,
            'designation'            => 'Frontend Developer',
            'skills_from_resume'     => ['React', 'TypeScript', 'Next.js', 'Tailwind CSS', 'Azure Static Web Apps', 'Playwright', 'Storybook'],
            'combined_skill_profile' => [
                'top_skills' => ['React', 'TypeScript', 'Next.js', 'Tailwind CSS'],
                'experience' => '4 years frontend development',
                'strengths'  => ['UI components', 'Performance optimization', 'Accessibility'],
            ],
            'is_active'       => true,
            'import_source'   => 'manual',
        ]);

        // Maya Chen — DevOps Engineer
        Employee::create([
            'organization_id'        => $org->id,
            'user_id'                => $employeeUsers['maya.chen@nalamtech.work']->id,
            'first_name'             => 'Maya',
            'last_name'              => 'Chen',
            'email'                  => 'maya.chen@nalamtech.work',
            'department_id'          => $ops->id,
            'designation'            => 'DevOps Engineer',
            'skills_from_resume'     => ['Azure DevOps', 'Terraform', 'Docker', 'Kubernetes', 'CI/CD', 'PowerShell', 'Bash', 'Monitoring'],
            'combined_skill_profile' => [
                'top_skills' => ['Azure DevOps', 'Terraform', 'Docker', 'Kubernetes'],
                'experience' => '4 years DevOps & cloud infrastructure',
                'strengths'  => ['CI/CD pipelines', 'Infrastructure as Code', 'Container orchestration'],
            ],
            'is_active'       => true,
            'import_source'   => 'manual',
        ]);

        $this->command->info('Created 5 employee records');

        // ── Projects ──────────────────────────────────────────────────
        Project::create([
            'organization_id'       => $org->id,
            'name'                  => 'Azure Migration Platform',
            'description'           => 'Enterprise cloud migration toolkit — automated assessment, migration planning, and execution for on-premises .NET applications moving to Azure.',
            'required_skills'       => ['C#', '.NET Core', 'Azure', 'Docker', 'Terraform'],
            'required_technologies' => ['.NET 8', 'Azure App Service', 'Azure SQL', 'Docker', 'Terraform'],
            'complexity_level'      => 'high',
            'domain_context'        => 'Enterprise cloud migration tooling with automated dependency analysis',
            'start_date'            => now()->subWeeks(6),
            'end_date'              => now()->addWeeks(10),
            'status'                => 'active',
            'created_by'            => $omar->id,
        ]);

        Project::create([
            'organization_id'       => $org->id,
            'name'                  => 'Customer Analytics Dashboard',
            'description'           => 'React-based analytics dashboard with real-time Azure SignalR integration for enterprise customers to monitor usage, performance, and costs.',
            'required_skills'       => ['React', 'TypeScript', 'C#', 'Azure SignalR', 'SQL Server'],
            'required_technologies' => ['React 18', 'TypeScript', 'Next.js', '.NET 8 API', 'Azure SignalR'],
            'complexity_level'      => 'medium',
            'domain_context'        => 'Customer-facing SaaS analytics with real-time data streaming',
            'start_date'            => now()->subWeeks(3),
            'end_date'              => now()->addWeeks(8),
            'status'                => 'active',
            'created_by'            => $omar->id,
        ]);

        Project::create([
            'organization_id'       => $org->id,
            'name'                  => 'CI/CD Pipeline Modernization',
            'description'           => 'Migrate legacy Jenkins pipelines to Azure DevOps YAML pipelines with Terraform-managed infrastructure, container-based builds, and automated security scanning.',
            'required_skills'       => ['Azure DevOps', 'Terraform', 'Docker', 'Kubernetes', 'PowerShell'],
            'required_technologies' => ['Azure DevOps Pipelines', 'Terraform', 'AKS', 'Trivy', 'SonarQube'],
            'complexity_level'      => 'critical',
            'domain_context'        => 'DevOps modernization — CI/CD, IaC, and container orchestration',
            'start_date'            => now()->subWeeks(1),
            'end_date'              => now()->addMonths(4),
            'status'                => 'planning',
            'created_by'            => $kevin->id,
        ]);

        $this->command->info('Created 3 projects');

        // ── Summary ───────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('=== Nalam Tech setup complete ===');
        $this->command->info("Org ID: {$org->id}");
        $this->command->info('');
        $this->command->info('Login credentials:');
        $this->command->info("  Admin:   kevin.lee@nalamtech.work / NalamDemo@Tech1");
        $this->command->info("  PM:      omar.ali@nalamtech.work  / NalamDemo@Tech1");
        $this->command->info('');
        $this->command->info('Next steps:');
        $this->command->info('  1. Login as kevin.lee@nalamtech.work');
        $this->command->info('  2. Go to Settings → Integrations');
        $this->command->info('  3. Add Azure DevOps connection (org + project + PAT)');
        $this->command->info('  4. Add Microsoft Teams connection (tenant + client ID + secret)');
        $this->command->info('  5. Sync both to pull real work data');
    }
}
