<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Organization;
use App\Models\PlacementDrive;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

/**
 * Nalam Institute of Technology — Education / Placement Training demo org.
 * Uses industry_template='education' which enables:
 *   placement_drives + aptitude_tests + student_tracking + interviews
 *
 * Run with: php artisan db:seed --class=NalamInstituteSeeder
 */
class NalamInstituteSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::create([
            'name'                     => 'Nalam Institute of Technology',
            'slug'                     => 'nalam-institute',
            'domain'                   => 'nalaminstitute.edu',
            'currency'                 => 'INR',
            'is_active'                => true,
            'is_premium'               => true,
            'subscription_plan'        => 'cloud_enterprise',
            'subscription_expires_at'  => '2027-12-31 23:59:59',
            'premium_expires_at'       => '2027-12-31 23:59:59',
            'industry_template'        => 'education',
            'enabled_modules'          => ['placement_drives', 'aptitude_tests', 'student_tracking', 'interviews'],
        ]);

        $this->command->info("Created organization: Nalam Institute (ID: {$org->id})");

        // ── Staff users ──────────────────────────────────────────────
        $headPlacement = User::create([
            'name'            => 'Dr. Arvind Krishnan',
            'email'           => 'arvind.krishnan@nalaminstitute.edu',
            'password'        => 'NalamDemo@Edu1',
            'role'            => 'org_admin',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $headPlacement->id, 'role' => 'org_admin']);

        $coordinator = User::create([
            'name'            => 'Lakshmi Subramanian',
            'email'           => 'lakshmi.s@nalaminstitute.edu',
            'password'        => 'NalamDemo@Edu1',
            'role'            => 'hr_manager',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $coordinator->id, 'role' => 'hr_manager']);

        // Faculty interviewer — base role is 'employee', interviewer capability
        // comes from UserRole pivot (which the sidebar checks via hasAnyRole)
        $facultyInterviewer = User::create([
            'name'            => 'Prof. Rajesh Iyengar',
            'email'           => 'rajesh.iyengar@nalaminstitute.edu',
            'password'        => 'NalamDemo@Edu1',
            'role'            => 'employee',
            'organization_id' => $org->id,
            'is_active'       => true,
        ]);
        UserRole::create(['user_id' => $facultyInterviewer->id, 'role' => 'interviewer']);

        $this->command->info("Created 3 staff users");

        // ── Departments (academic divisions) ─────────────────────────
        Department::create([
            'organization_id' => $org->id,
            'name'            => 'Computer Science & Engineering',
            'description'     => 'B.Tech and M.Tech programs in CSE, AI/ML, and Data Science',
        ]);
        Department::create([
            'organization_id' => $org->id,
            'name'            => 'Electronics & Communication',
            'description'     => 'B.Tech program in ECE with VLSI, Embedded Systems specializations',
        ]);
        Department::create([
            'organization_id' => $org->id,
            'name'            => 'Information Technology',
            'description'     => 'B.Tech program in IT and MCA',
        ]);

        $this->command->info("Created 3 departments");

        // ── Students (stored in candidates table with student fields) ──
        // Mix of profiles: high performers, average, struggling — for
        // realistic progress dashboards once Commit E ships.
        $students = [
            // High performers
            ['Aditya',   'Sharma',  'aditya.sharma@nalaminstitute.edu',   'CSE_2026_001', 'B.Tech CSE', 2026, ['DSA', 'Python', 'System Design', 'Java', 'SQL']],
            ['Sneha',    'Reddy',   'sneha.reddy@nalaminstitute.edu',     'CSE_2026_002', 'B.Tech CSE', 2026, ['Python', 'Machine Learning', 'TensorFlow', 'Statistics', 'SQL']],

            // Average performers
            ['Karan',    'Patel',   'karan.patel@nalaminstitute.edu',     'CSE_2026_003', 'B.Tech CSE', 2026, ['Java', 'OOP', 'DBMS', 'Networking']],
            ['Ananya',   'Iyer',    'ananya.iyer@nalaminstitute.edu',     'IT_2026_004',  'B.Tech IT',  2026, ['JavaScript', 'React', 'CSS', 'HTML', 'Node.js']],
            ['Vivek',    'Nair',    'vivek.nair@nalaminstitute.edu',      'IT_2026_005',  'B.Tech IT',  2026, ['C++', 'DSA', 'OS', 'Linux']],

            // Need improvement
            ['Riya',     'Gupta',   'riya.gupta@nalaminstitute.edu',      'ECE_2026_006', 'B.Tech ECE', 2026, ['VLSI', 'Embedded C', 'MATLAB']],
            ['Suresh',   'Pillai',  'suresh.pillai@nalaminstitute.edu',   'CSE_2027_007', 'B.Tech CSE', 2027, ['Python', 'C']],
            ['Meena',    'Joshi',   'meena.joshi@nalaminstitute.edu',     'IT_2027_008',  'B.Tech IT',  2027, ['HTML', 'CSS', 'JavaScript']],
        ];

        foreach ($students as [$first, $last, $email, $enr, $course, $year, $skills]) {
            Candidate::create([
                'organization_id'   => $org->id,
                'first_name'        => $first,
                'last_name'         => $last,
                'email'             => $email,
                'enrollment_number' => $enr,
                'course'            => $course,
                'batch_year'        => $year,
                'skills'            => $skills,
                'source'            => 'upload',
            ]);
        }

        $this->command->info("Created " . count($students) . " students");

        // ── One sample placement drive (placeholder until Commit B's UI ships) ──
        PlacementDrive::create([
            'organization_id'      => $org->id,
            'company_name'         => 'TechCorp India',
            'role_title'           => 'Software Engineer (Graduate Trainee)',
            'description'          => 'TechCorp is hiring 2026 graduates for their Bangalore engineering hub. Looking for strong fundamentals in DSA, OOP, and at least one production language (Java/Python/Go). Training period: 3 months. Permanent placement based on training performance.',
            'eligible_courses'     => ['B.Tech CSE', 'B.Tech IT', 'MCA'],
            'eligible_batch_years' => [2026],
            'min_cgpa'             => 7.50,
            'required_skills'      => ['DSA', 'OOP', 'DBMS', 'Java or Python'],
            'package_lpa'          => 12,
            'drive_date'           => now()->addDays(14)->toDateString(),
            'test_format'          => 'aptitude_plus_interview',
            'status'               => 'open',
            'created_by'           => $headPlacement->id,
        ]);

        $this->command->info("Created 1 placement drive (TechCorp India)");
        $this->command->info("");
        $this->command->info("Login: arvind.krishnan@nalaminstitute.edu / NalamDemo@Edu1");
        $this->command->info("Nalam Institute seeding complete.");
    }
}
