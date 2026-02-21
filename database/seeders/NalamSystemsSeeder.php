<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\JiraConnection;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder for Nalam Systems — simulation org for testing
 * Jira integration, resource allocation, and signal intelligence.
 *
 * Run with: php artisan db:seed --class=NalamSystemsSeeder
 */
class NalamSystemsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Organization ─────────────────────────────────────────────
        $org = Organization::create([
            'name'      => 'Nalam Systems',
            'slug'      => 'nalam-systems',
            'domain'    => 'nalamsystems.work',
            'is_active' => true,
        ]);

        $this->command->info("Created organization: Nalam Systems (ID: {$org->id})");

        // ── Users ─────────────────────────────────────────────────────
        $admin = User::create([
            'name'            => 'Administrator',
            'email'           => 'admin@nalamsystems.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'org_admin',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);

        $hrm = User::create([
            'name'            => 'Human Resource Manager',
            'email'           => 'hrm@nalamsystems.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'hr_manager',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);

        $pm = User::create([
            'name'            => 'Program Manager',
            'email'           => 'pm@nalamsystems.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'resource_manager',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);

        // Employee-role user accounts for the 5 developers
        $rahulUser = User::create([
            'name'            => 'Rahul Kumar',
            'email'           => 'rahul.kumar@nalamsystems.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);

        $davidUser = User::create([
            'name'            => 'David Kim',
            'email'           => 'david.kim@nalamsystems.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);

        $amanUser = User::create([
            'name'            => 'Aman Verma',
            'email'           => 'aman.verma@nalamsystems.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);

        $saraUser = User::create([
            'name'            => 'Sara Lim',
            'email'           => 'sara.lim@nalamsystems.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);

        $anitaUser = User::create([
            'name'            => 'Anita Patel',
            'email'           => 'anita.patel@nalamsystems.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);

        $this->command->info('Created 8 user accounts');

        // ── Departments ───────────────────────────────────────────────
        $engineering = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Engineering',
            'description'     => 'Full-stack software engineering and platform development',
        ]);

        $design = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Design',
            'description'     => 'UI/UX design and user research',
        ]);

        $hr = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Human Resources',
            'description'     => 'People operations, recruitment, and employee relations',
        ]);

        $management = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Management',
            'description'     => 'Program management and leadership',
        ]);

        $this->command->info('Created 4 departments');

        // ── Employees ─────────────────────────────────────────────────
        $rahul = Employee::create([
            'organization_id'       => $org->id,
            'user_id'               => $rahulUser->id,
            'first_name'            => 'Rahul',
            'last_name'             => 'Kumar',
            'email'                 => 'rahul.kumar@nalamsystems.work',
            'department_id'         => $engineering->id,
            'designation'           => 'Senior Full Stack Developer',
            'skills_from_resume'    => ['Laravel', 'PHP', 'Vue.js', 'MySQL', 'Docker', 'AWS', 'REST APIs', 'Redis'],
            'combined_skill_profile' => [
                'top_skills'  => ['Laravel', 'PHP', 'Vue.js', 'MySQL', 'Docker'],
                'experience'  => '5+ years full-stack development',
                'strengths'   => ['Backend architecture', 'Database design', 'API development'],
            ],
            'is_active'     => true,
            'import_source' => 'manual',
        ]);

        $david = Employee::create([
            'organization_id'       => $org->id,
            'user_id'               => $davidUser->id,
            'first_name'            => 'David',
            'last_name'             => 'Kim',
            'email'                 => 'david.kim@nalamsystems.work',
            'department_id'         => $engineering->id,
            'designation'           => 'Frontend Developer',
            'skills_from_resume'    => ['React', 'TypeScript', 'Next.js', 'Tailwind CSS', 'GraphQL', 'Jest', 'Storybook'],
            'combined_skill_profile' => [
                'top_skills'  => ['React', 'TypeScript', 'Next.js', 'GraphQL'],
                'experience'  => '4 years frontend development',
                'strengths'   => ['Component design', 'Performance optimisation', 'Testing'],
            ],
            'is_active'     => true,
            'import_source' => 'manual',
        ]);

        $aman = Employee::create([
            'organization_id'       => $org->id,
            'user_id'               => $amanUser->id,
            'first_name'            => 'Aman',
            'last_name'             => 'Verma',
            'email'                 => 'aman.verma@nalamsystems.work',
            'department_id'         => $engineering->id,
            'designation'           => 'Backend Developer',
            'skills_from_resume'    => ['Python', 'FastAPI', 'PostgreSQL', 'Redis', 'Celery', 'Docker', 'Kubernetes', 'CI/CD'],
            'combined_skill_profile' => [
                'top_skills'  => ['Python', 'FastAPI', 'PostgreSQL', 'Kubernetes'],
                'experience'  => '4 years backend & DevOps',
                'strengths'   => ['Microservices', 'API design', 'DevOps pipelines'],
            ],
            'is_active'     => true,
            'import_source' => 'manual',
        ]);

        $sara = Employee::create([
            'organization_id'       => $org->id,
            'user_id'               => $saraUser->id,
            'first_name'            => 'Sara',
            'last_name'             => 'Lim',
            'email'                 => 'sara.lim@nalamsystems.work',
            'department_id'         => $design->id,
            'designation'           => 'UI/UX Designer',
            'skills_from_resume'    => ['Figma', 'Adobe XD', 'CSS', 'React', 'User Research', 'Prototyping', 'Accessibility'],
            'combined_skill_profile' => [
                'top_skills'  => ['Figma', 'User Research', 'CSS', 'Prototyping'],
                'experience'  => '3 years UI/UX design',
                'strengths'   => ['Design systems', 'Usability testing', 'Wireframing'],
            ],
            'is_active'     => true,
            'import_source' => 'manual',
        ]);

        $anita = Employee::create([
            'organization_id'       => $org->id,
            'user_id'               => $anitaUser->id,
            'first_name'            => 'Anita',
            'last_name'             => 'Patel',
            'email'                 => 'anita.patel@nalamsystems.work',
            'department_id'         => $hr->id,
            'designation'           => 'HR Specialist',
            'skills_from_resume'    => ['Recruitment', 'Onboarding', 'Employee Relations', 'HRIS', 'Compliance', 'Performance Management'],
            'combined_skill_profile' => [
                'top_skills'  => ['Recruitment', 'Employee Relations', 'HRIS'],
                'experience'  => '4 years HR operations',
                'strengths'   => ['Talent acquisition', 'Policy compliance', 'Onboarding'],
            ],
            'is_active'     => true,
            'import_source' => 'manual',
        ]);

        // Admin, HRM, PM as employees too
        Employee::create([
            'organization_id' => $org->id,
            'user_id'         => $admin->id,
            'first_name'      => 'Administrator',
            'last_name'       => '',
            'email'           => 'admin@nalamsystems.work',
            'department_id'   => $management->id,
            'designation'     => 'Organization Administrator',
            'is_active'       => true,
            'import_source'   => 'manual',
        ]);

        Employee::create([
            'organization_id' => $org->id,
            'user_id'         => $hrm->id,
            'first_name'      => 'Human Resource',
            'last_name'       => 'Manager',
            'email'           => 'hrm@nalamsystems.work',
            'department_id'   => $hr->id,
            'designation'     => 'HR Manager',
            'skills_from_resume' => ['Recruitment', 'HR Strategy', 'Employee Development', 'Labor Law', 'Performance Reviews'],
            'combined_skill_profile' => [
                'top_skills' => ['HR Strategy', 'Recruitment', 'Employee Development'],
            ],
            'is_active'     => true,
            'import_source' => 'manual',
        ]);

        Employee::create([
            'organization_id' => $org->id,
            'user_id'         => $pm->id,
            'first_name'      => 'Program',
            'last_name'       => 'Manager',
            'email'           => 'pm@nalamsystems.work',
            'department_id'   => $management->id,
            'designation'     => 'Program Manager',
            'skills_from_resume' => ['Agile', 'Scrum', 'Jira', 'Stakeholder Management', 'Risk Management', 'Resource Planning'],
            'combined_skill_profile' => [
                'top_skills' => ['Agile', 'Scrum', 'Resource Planning', 'Stakeholder Management'],
            ],
            'is_active'     => true,
            'import_source' => 'manual',
        ]);

        $this->command->info('Created 8 employee records');

        // ── Sample Projects ───────────────────────────────────────────
        Project::create([
            'organization_id'       => $org->id,
            'name'                  => 'Platform Core API',
            'description'           => 'Build the core REST API layer for the Nalam Systems platform. Includes authentication, multi-tenancy, and core business logic endpoints.',
            'required_skills'       => ['Laravel', 'PHP', 'PostgreSQL', 'Redis', 'Docker'],
            'required_technologies' => ['Laravel 12', 'PostgreSQL', 'Redis', 'Docker', 'AWS'],
            'complexity_level'      => 'high',
            'domain_context'        => 'SaaS platform backend — multi-tenant REST API',
            'start_date'            => now()->subWeeks(4),
            'end_date'              => now()->addWeeks(8),
            'status'                => 'active',
            'created_by'            => $pm->id,
        ]);

        Project::create([
            'organization_id'       => $org->id,
            'name'                  => 'Customer Dashboard UI',
            'description'           => 'React-based dashboard for customers to view analytics, manage their account, and interact with platform features.',
            'required_skills'       => ['React', 'TypeScript', 'GraphQL', 'CSS', 'Figma'],
            'required_technologies' => ['React 18', 'TypeScript', 'Next.js', 'Tailwind CSS'],
            'complexity_level'      => 'medium',
            'domain_context'        => 'Customer-facing SaaS dashboard with real-time data',
            'start_date'            => now()->subWeeks(2),
            'end_date'              => now()->addWeeks(10),
            'status'                => 'active',
            'created_by'            => $pm->id,
        ]);

        Project::create([
            'organization_id'       => $org->id,
            'name'                  => 'Data Pipeline & ML Services',
            'description'           => 'Build async data processing pipeline and ML inference services using FastAPI and Celery workers.',
            'required_skills'       => ['Python', 'FastAPI', 'Celery', 'PostgreSQL', 'Kubernetes'],
            'required_technologies' => ['Python 3.12', 'FastAPI', 'Celery', 'Redis', 'Kubernetes'],
            'complexity_level'      => 'critical',
            'domain_context'        => 'Backend data processing and AI inference infrastructure',
            'start_date'            => now()->subWeeks(1),
            'end_date'              => now()->addMonths(3),
            'status'                => 'planning',
            'created_by'            => $pm->id,
        ]);

        $this->command->info('Created 3 projects');
        $this->command->info('');
        $this->command->info('=== Nalam Systems setup complete ===');
        $this->command->info("Org ID: {$org->id}");
        $this->command->info('Admin login: admin@nalamsystems.work / NalamDemo@Systems1');
        $this->command->info('HRM login:   hrm@nalamsystems.work  / NalamDemo@Systems1');
        $this->command->info('PM login:    pm@nalamsystems.work   / NalamDemo@Systems1');
        $this->command->info('');
        $this->command->info('Next step: Add Jira connection via Settings → Integrations in the app,');
        $this->command->info('or run: php artisan db:seed --class=NalamJiraSeeder (after providing API details)');
    }
}
