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
 * Seeder for Nalam Labs — Zoho + Slack stack demo org.
 * Uses Zoho Projects (sprints), Zoho People (HR), Slack (communication).
 *
 * Run with: php artisan db:seed --class=NalamLabsSeeder
 */
class NalamLabsSeeder extends Seeder
{
    public function run(): void
    {
        // ── Organization ─────────────────────────────────────────────
        $org = Organization::create([
            'name'                     => 'Nalam Labs',
            'slug'                     => 'nalam-labs',
            'domain'                   => 'nalamlabs.work',
            'is_active'                => true,
            'is_premium'               => true,
            'subscription_plan'        => 'cloud_enterprise',
            'subscription_expires_at'  => '2027-12-31 23:59:59',
            'premium_expires_at'       => '2027-12-31 23:59:59',
        ]);

        $this->command->info("Created organization: Nalam Labs (ID: {$org->id})");

        // ── Users ─────────────────────────────────────────────────────
        $ravi = User::create([
            'name'            => 'Ravi Das',
            'email'           => 'ravi.das@nalamlabs.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'org_admin',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $ravi->id, 'role' => 'org_admin']);

        $sara = User::create([
            'name'            => 'Sara Noor',
            'email'           => 'sara.noor@nalamlabs.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'resource_manager',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $sara->id, 'role' => 'resource_manager']);

        $kai = User::create([
            'name'            => 'Kai Chen',
            'email'           => 'kai.chen@nalamlabs.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $kai->id, 'role' => 'employee']);

        $zara = User::create([
            'name'            => 'Zara Khan',
            'email'           => 'zara.khan@nalamlabs.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $zara->id, 'role' => 'employee']);

        $leo = User::create([
            'name'            => 'Leo Kim',
            'email'           => 'leo.kim@nalamlabs.work',
            'password'        => 'NalamDemo@Systems1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $leo->id, 'role' => 'employee']);

        $this->command->info("Created 5 users");

        // ── Departments ───────────────────────────────────────────────
        $engineering = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Engineering',
            'description'     => 'Full-stack development and architecture',
        ]);

        $data = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Data Science',
            'description'     => 'ML, analytics, and data engineering',
        ]);

        $design = Department::create([
            'organization_id' => $org->id,
            'name'            => 'Design',
            'description'     => 'UX/UI design and research',
        ]);

        $this->command->info("Created 3 departments");

        // ── Employees ─────────────────────────────────────────────────
        Employee::create([
            'user_id'         => $ravi->id,
            'organization_id' => $org->id,
            'first_name'      => 'Ravi',
            'last_name'       => 'Das',
            'email'           => 'ravi.das@nalamlabs.work',
            'department_id'   => $engineering->id,
            'designation'     => 'Engineering Lead',
            'is_active'       => true,
            'combined_skill_profile' => ['Python', 'Django', 'FastAPI', 'PostgreSQL', 'AWS', 'Docker', 'Kubernetes', 'System Design'],
        ]);

        Employee::create([
            'user_id'         => $sara->id,
            'organization_id' => $org->id,
            'first_name'      => 'Sara',
            'last_name'       => 'Noor',
            'email'           => 'sara.noor@nalamlabs.work',
            'department_id'   => $data->id,
            'designation'     => 'Data Science Lead',
            'is_active'       => true,
            'combined_skill_profile' => ['Python', 'TensorFlow', 'PyTorch', 'Pandas', 'SQL', 'Spark', 'MLOps', 'Statistics'],
        ]);

        Employee::create([
            'user_id'         => $kai->id,
            'organization_id' => $org->id,
            'first_name'      => 'Kai',
            'last_name'       => 'Chen',
            'email'           => 'kai.chen@nalamlabs.work',
            'department_id'   => $engineering->id,
            'designation'     => 'Senior Backend Developer',
            'is_active'       => true,
            'combined_skill_profile' => ['Python', 'Go', 'PostgreSQL', 'Redis', 'gRPC', 'Microservices', 'AWS Lambda', 'CI/CD'],
        ]);

        Employee::create([
            'user_id'         => $zara->id,
            'organization_id' => $org->id,
            'first_name'      => 'Zara',
            'last_name'       => 'Khan',
            'email'           => 'zara.khan@nalamlabs.work',
            'department_id'   => $design->id,
            'designation'     => 'UX Designer',
            'is_active'       => true,
            'combined_skill_profile' => ['Figma', 'User Research', 'Prototyping', 'Design Systems', 'CSS', 'React', 'Accessibility', 'Interaction Design'],
        ]);

        Employee::create([
            'user_id'         => $leo->id,
            'organization_id' => $org->id,
            'first_name'      => 'Leo',
            'last_name'       => 'Kim',
            'email'           => 'leo.kim@nalamlabs.work',
            'department_id'   => $engineering->id,
            'designation'     => 'Full Stack Developer',
            'is_active'       => true,
            'combined_skill_profile' => ['JavaScript', 'TypeScript', 'React', 'Node.js', 'PostgreSQL', 'GraphQL', 'Next.js', 'Tailwind CSS'],
        ]);

        $this->command->info("Created 5 employees");

        // ── Projects ──────────────────────────────────────────────────
        Project::create([
            'organization_id'      => $org->id,
            'name'                 => 'AI-Powered Document Processor',
            'description'          => 'Intelligent document processing pipeline using LLMs for extraction, classification, and summarization of enterprise documents.',
            'required_skills'      => ['Python', 'FastAPI', 'TensorFlow', 'PostgreSQL'],
            'required_technologies'=> ['AWS', 'Docker', 'Redis', 'Elasticsearch'],
            'complexity_level'     => 'high',
            'domain_context'       => 'AI/ML, Document Processing, Enterprise SaaS',
            'start_date'           => '2026-01-15',
            'end_date'             => '2026-06-30',
            'status'               => 'active',
            'created_by'           => $ravi->id,
        ]);

        Project::create([
            'organization_id'      => $org->id,
            'name'                 => 'Customer Insights Dashboard',
            'description'          => 'Real-time analytics dashboard for customer behavior tracking, churn prediction, and engagement scoring.',
            'required_skills'      => ['React', 'TypeScript', 'Python', 'SQL'],
            'required_technologies'=> ['Next.js', 'PostgreSQL', 'Spark', 'Grafana'],
            'complexity_level'     => 'medium',
            'domain_context'       => 'Analytics, SaaS, Customer Intelligence',
            'start_date'           => '2026-02-01',
            'end_date'             => '2026-05-31',
            'status'               => 'active',
            'created_by'           => $ravi->id,
        ]);

        Project::create([
            'organization_id'      => $org->id,
            'name'                 => 'API Gateway Modernization',
            'description'          => 'Migrate legacy REST APIs to a modern GraphQL gateway with rate limiting, caching, and observability.',
            'required_skills'      => ['Go', 'GraphQL', 'Node.js', 'Docker'],
            'required_technologies'=> ['Kubernetes', 'Redis', 'Prometheus', 'AWS'],
            'complexity_level'     => 'high',
            'domain_context'       => 'Platform Engineering, API Infrastructure',
            'start_date'           => '2026-03-01',
            'end_date'             => '2026-08-31',
            'status'               => 'planning',
            'created_by'           => $ravi->id,
        ]);

        $this->command->info("Created 3 projects");
        $this->command->info("Nalam Labs seeding complete!");
    }
}
