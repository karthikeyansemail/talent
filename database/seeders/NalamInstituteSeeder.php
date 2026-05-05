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
 * Seeds a realistic multi-discipline engineering institute spanning
 * 12 academic departments and ~30 students across all of them, so the
 * dashboards demonstrate variety beyond a CSE-only setup.
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

        // ── Academic departments (12 disciplines) ───────────────────
        $deptDefs = [
            ['Computer Science & Engineering',  'B.Tech CSE program with specializations in AI/ML and Software Engineering'],
            ['Information Technology',          'B.Tech IT and MCA programs'],
            ['Artificial Intelligence & Data Science', 'B.Tech AI/DS — newer high-demand stream'],
            ['Electronics & Communication',     'B.Tech ECE with VLSI, Embedded Systems, Signal Processing'],
            ['Electrical & Electronics',        'B.Tech EEE — power systems, control systems, electric vehicles'],
            ['Mechanical Engineering',          'B.Tech Mechanical — design, manufacturing, automotive'],
            ['Mechatronics',                    'B.Tech Mechatronics — robotics, automation, IoT-integrated mechanical'],
            ['Civil Engineering',               'B.Tech Civil — structural, transport, environmental'],
            ['Marine Engineering',              'B.Tech Marine Engineering — ship systems, marine machinery'],
            ['Chemical Engineering',            'B.Tech Chemical — process engineering, petrochemicals'],
            ['Aerospace Engineering',           'B.Tech Aerospace — aerodynamics, propulsion, avionics'],
            ['Biotechnology',                   'B.Tech Biotech — bioprocess, genetic engineering, biopharma'],
        ];
        $depts = [];
        foreach ($deptDefs as [$name, $desc]) {
            $depts[$name] = Department::create([
                'organization_id' => $org->id,
                'name'            => $name,
                'description'     => $desc,
            ]);
        }
        $this->command->info("Created " . count($depts) . " academic departments");

        // ── Students — spread across all departments ───────────────
        // Format: [first, last, email_local, enr_prefix, dept_name, course_label, batch_year, skills]
        $students = [
            // Computer Science & Engineering
            ['Aditya',   'Sharma',     'aditya.sharma',   'CSE2026001', 'Computer Science & Engineering', 'B.Tech CSE',         2026, ['DSA', 'Python', 'System Design', 'Java', 'SQL']],
            ['Sneha',    'Reddy',      'sneha.reddy',     'CSE2026002', 'Computer Science & Engineering', 'B.Tech CSE',         2026, ['Python', 'Machine Learning', 'TensorFlow', 'Statistics']],
            ['Karan',    'Patel',      'karan.patel',     'CSE2026003', 'Computer Science & Engineering', 'B.Tech CSE',         2026, ['Java', 'OOP', 'DBMS', 'Networking']],
            ['Suresh',   'Pillai',     'suresh.pillai',   'CSE2027004', 'Computer Science & Engineering', 'B.Tech CSE',         2027, ['Python', 'C']],

            // Information Technology
            ['Ananya',   'Iyer',       'ananya.iyer',     'IT2026005',  'Information Technology',         'B.Tech IT',          2026, ['JavaScript', 'React', 'CSS', 'Node.js']],
            ['Vivek',    'Nair',       'vivek.nair',      'IT2026006',  'Information Technology',         'B.Tech IT',          2026, ['C++', 'DSA', 'OS', 'Linux']],
            ['Meena',    'Joshi',      'meena.joshi',     'IT2027007',  'Information Technology',         'B.Tech IT',          2027, ['HTML', 'CSS', 'JavaScript']],

            // AI & Data Science
            ['Priya',    'Venkatesh',  'priya.v',         'AIDS2026008','Artificial Intelligence & Data Science', 'B.Tech AI/DS', 2026, ['Python', 'PyTorch', 'NLP', 'SQL', 'Pandas']],
            ['Rohit',    'Bhatt',      'rohit.bhatt',     'AIDS2026009','Artificial Intelligence & Data Science', 'B.Tech AI/DS', 2026, ['Python', 'Computer Vision', 'OpenCV', 'Statistics']],

            // Electronics & Communication
            ['Riya',     'Gupta',      'riya.gupta',      'ECE2026010', 'Electronics & Communication',    'B.Tech ECE',         2026, ['VLSI', 'Embedded C', 'MATLAB', 'Verilog']],
            ['Karthik',  'Murugan',    'karthik.m',       'ECE2026011', 'Electronics & Communication',    'B.Tech ECE',         2026, ['DSP', 'Communication Systems', 'Antenna Design']],
            ['Divya',    'Krishnan',   'divya.k',         'ECE2027012', 'Electronics & Communication',    'B.Tech ECE',         2027, ['Embedded Systems', 'IoT', 'Arduino']],

            // Electrical & Electronics
            ['Arjun',    'Menon',      'arjun.menon',     'EEE2026013', 'Electrical & Electronics',       'B.Tech EEE',         2026, ['Power Systems', 'MATLAB', 'Control Systems', 'PLC']],
            ['Kavya',    'Rao',        'kavya.rao',       'EEE2026014', 'Electrical & Electronics',       'B.Tech EEE',         2026, ['Electric Vehicles', 'Power Electronics', 'Simulink']],

            // Mechanical Engineering
            ['Prakash',  'Naidu',      'prakash.naidu',   'MECH2026015','Mechanical Engineering',         'B.Tech Mechanical',  2026, ['SolidWorks', 'AutoCAD', 'Thermodynamics', 'Manufacturing']],
            ['Lavanya',  'Bose',       'lavanya.bose',    'MECH2026016','Mechanical Engineering',         'B.Tech Mechanical',  2026, ['CAD', 'CFD', 'Heat Transfer', 'Materials']],
            ['Ramesh',   'Kulkarni',   'ramesh.k',        'MECH2027017','Mechanical Engineering',         'B.Tech Mechanical',  2027, ['Welding', 'CNC Programming', 'Quality Control']],

            // Mechatronics
            ['Naveen',   'Subhash',    'naveen.subhash',  'MTRX2026018','Mechatronics',                   'B.Tech Mechatronics',2026, ['Robotics', 'ROS', 'Sensor Integration', 'PLC', 'Python']],
            ['Pooja',    'Hegde',      'pooja.hegde',     'MTRX2026019','Mechatronics',                   'B.Tech Mechatronics',2026, ['Industrial Automation', 'IoT', 'Embedded C', 'SCADA']],

            // Civil Engineering
            ['Sanjay',   'Desai',      'sanjay.desai',    'CIV2026020', 'Civil Engineering',              'B.Tech Civil',       2026, ['STAAD Pro', 'AutoCAD', 'Structural Analysis', 'Surveying']],
            ['Bhavana',  'Pillay',     'bhavana.pillay',  'CIV2027021', 'Civil Engineering',              'B.Tech Civil',       2027, ['Construction Management', 'Concrete Tech', 'Estimation']],

            // Marine Engineering
            ['Vishal',   'D Souza',    'vishal.dsouza',   'MAR2026022', 'Marine Engineering',             'B.Tech Marine Eng.', 2026, ['Marine Diesel Engines', 'Ship Design', 'Naval Architecture']],
            ['Ishaan',   'Fernandes',  'ishaan.f',        'MAR2026023', 'Marine Engineering',             'B.Tech Marine Eng.', 2026, ['Marine Auxiliary Machinery', 'Hydraulics', 'Maritime Safety']],

            // Chemical Engineering
            ['Tanvi',    'Verma',      'tanvi.verma',     'CHEM2026024','Chemical Engineering',           'B.Tech Chemical',    2026, ['Process Design', 'Aspen Plus', 'Reaction Engineering', 'Heat Transfer']],
            ['Manoj',    'Khanna',     'manoj.khanna',    'CHEM2027025','Chemical Engineering',           'B.Tech Chemical',    2027, ['Separation Processes', 'Process Control', 'Petrochemicals']],

            // Aerospace Engineering
            ['Aakash',   'Sehgal',     'aakash.sehgal',   'AERO2026026','Aerospace Engineering',          'B.Tech Aerospace',   2026, ['Aerodynamics', 'CATIA', 'CFD', 'Propulsion']],
            ['Nitya',    'Banerjee',   'nitya.b',         'AERO2026027','Aerospace Engineering',          'B.Tech Aerospace',   2026, ['Avionics', 'Flight Mechanics', 'MATLAB', 'Composite Materials']],

            // Biotechnology
            ['Shreya',   'Mathew',     'shreya.mathew',   'BIO2026028', 'Biotechnology',                  'B.Tech Biotech',     2026, ['Genetic Engineering', 'Bioprocess', 'Cell Culture', 'PCR']],
            ['Harsh',    'Tripathi',   'harsh.tripathi',  'BIO2027029', 'Biotechnology',                  'B.Tech Biotech',     2027, ['Bioinformatics', 'Molecular Biology', 'Microbiology']],
        ];

        foreach ($students as [$first, $last, $local, $enr, $deptName, $course, $year, $skills]) {
            Candidate::create([
                'organization_id'   => $org->id,
                'first_name'        => $first,
                'last_name'         => $last,
                'email'             => $local . '@nalaminstitute.edu',
                'enrollment_number' => $enr,
                'department_id'     => $depts[$deptName]->id,
                'course'            => $course,
                'batch_year'        => $year,
                'skills'            => $skills,
                'source'            => 'upload',
            ]);
        }

        $this->command->info("Created " . count($students) . " students across " . count($depts) . " departments");

        // ── Sample placement drive ─────────────────────────────────
        PlacementDrive::create([
            'organization_id'      => $org->id,
            'company_name'         => 'TechCorp India',
            'role_title'           => 'Software Engineer (Graduate Trainee)',
            'description'          => 'TechCorp is hiring 2026 graduates for their Bangalore engineering hub. Looking for strong fundamentals in DSA, OOP, and at least one production language (Java/Python/Go). Training period: 3 months. Permanent placement based on training performance.',
            'eligible_courses'     => ['B.Tech CSE', 'B.Tech IT', 'B.Tech AI/DS', 'MCA'],
            'eligible_batch_years' => [2026],
            'min_cgpa'             => 7.50,
            'required_skills'      => ['DSA', 'OOP', 'DBMS', 'Java or Python'],
            'package_lpa'          => 12,
            'drive_date'           => now()->addDays(14)->toDateString(),
            'test_format'          => 'aptitude_plus_interview',
            'status'               => 'open',
            'created_by'           => $headPlacement->id,
        ]);

        $this->command->info("Created 1 sample placement drive");
        $this->command->info("");
        $this->command->info("Login: arvind.krishnan@nalaminstitute.edu / NalamDemo@Edu1");
        $this->command->info("Run NalamInstituteAttemptsSeeder next to populate progress charts.");
    }
}
