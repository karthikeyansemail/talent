<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create organization
        $org = Organization::create([
            'name' => 'Acme Technologies',
            'slug' => 'acme-tech',
            'domain' => 'acme.com',
            'is_active' => true,
        ]);

        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@acme.com',
            'password' => 'password',
            'role' => 'org_admin',
            'organization_id' => $org->id,
            'is_active' => true,
        ]);

        // Create other role users
        $hrManager = User::create([
            'name' => 'Sarah HR',
            'email' => 'hr@acme.com',
            'password' => 'password',
            'role' => 'hr_manager',
            'organization_id' => $org->id,
            'is_active' => true,
        ]);

        $resourceManager = User::create([
            'name' => 'Mike Resources',
            'email' => 'rm@acme.com',
            'password' => 'password',
            'role' => 'resource_manager',
            'organization_id' => $org->id,
            'is_active' => true,
        ]);

        // Create departments
        $engineering = Department::create(['organization_id' => $org->id, 'name' => 'Engineering', 'description' => 'Software Engineering']);
        $product = Department::create(['organization_id' => $org->id, 'name' => 'Product', 'description' => 'Product Management']);
        $design = Department::create(['organization_id' => $org->id, 'name' => 'Design', 'description' => 'UI/UX Design']);
        $data = Department::create(['organization_id' => $org->id, 'name' => 'Data Science', 'description' => 'Data & Analytics']);

        // Create job postings
        $job1 = JobPosting::create([
            'organization_id' => $org->id,
            'department_id' => $engineering->id,
            'title' => 'Senior Backend Developer',
            'description' => 'We are looking for an experienced backend developer proficient in Python, Node.js, and cloud services. You will design and build scalable APIs and microservices.',
            'requirements' => "5+ years backend development\nExperience with Python or Node.js\nCloud services (AWS/GCP)\nDatabase design (SQL + NoSQL)",
            'min_experience' => 5,
            'max_experience' => 10,
            'required_skills' => ['Python', 'Node.js', 'AWS', 'PostgreSQL', 'Docker'],
            'nice_to_have_skills' => ['Kubernetes', 'GraphQL', 'Redis'],
            'employment_type' => 'full_time',
            'location' => 'Remote',
            'salary_min' => 120000,
            'salary_max' => 180000,
            'status' => 'open',
            'created_by' => $admin->id,
        ]);

        $job2 = JobPosting::create([
            'organization_id' => $org->id,
            'department_id' => $engineering->id,
            'title' => 'React Frontend Developer',
            'description' => 'Join our team to build modern, responsive web applications using React and TypeScript.',
            'requirements' => "3+ years frontend development\nStrong React/TypeScript skills\nCSS/responsive design",
            'min_experience' => 3,
            'max_experience' => 7,
            'required_skills' => ['React', 'TypeScript', 'CSS', 'JavaScript'],
            'nice_to_have_skills' => ['Next.js', 'Tailwind', 'Testing'],
            'employment_type' => 'full_time',
            'location' => 'Hybrid - NYC',
            'salary_min' => 100000,
            'salary_max' => 150000,
            'status' => 'open',
            'created_by' => $hrManager->id,
        ]);

        $job3 = JobPosting::create([
            'organization_id' => $org->id,
            'department_id' => $data->id,
            'title' => 'ML Engineer',
            'description' => 'Build and deploy machine learning models for our recommendation engine.',
            'requirements' => "4+ years ML experience\nPython, TensorFlow/PyTorch\nMLOps experience",
            'min_experience' => 4,
            'max_experience' => 8,
            'required_skills' => ['Python', 'Machine Learning', 'TensorFlow', 'MLOps'],
            'employment_type' => 'full_time',
            'location' => 'Remote',
            'status' => 'open',
            'created_by' => $admin->id,
        ]);

        // Create candidates
        $candidates = [
            ['first_name' => 'John', 'last_name' => 'Smith', 'email' => 'john.smith@email.com', 'phone' => '555-0101', 'current_company' => 'Google', 'current_title' => 'Senior Developer', 'experience_years' => 7, 'source' => 'direct'],
            ['first_name' => 'Emily', 'last_name' => 'Chen', 'email' => 'emily.chen@email.com', 'phone' => '555-0102', 'current_company' => 'Meta', 'current_title' => 'Software Engineer', 'experience_years' => 5, 'source' => 'referral'],
            ['first_name' => 'Alex', 'last_name' => 'Johnson', 'email' => 'alex.j@email.com', 'phone' => '555-0103', 'current_company' => 'Startup Inc', 'current_title' => 'Full Stack Developer', 'experience_years' => 4, 'source' => 'upload'],
            ['first_name' => 'Priya', 'last_name' => 'Patel', 'email' => 'priya.p@email.com', 'phone' => '555-0104', 'current_company' => 'Amazon', 'current_title' => 'ML Engineer', 'experience_years' => 6, 'source' => 'direct'],
            ['first_name' => 'David', 'last_name' => 'Kim', 'email' => 'david.kim@email.com', 'phone' => '555-0105', 'current_company' => 'Netflix', 'current_title' => 'Frontend Lead', 'experience_years' => 8, 'source' => 'referral'],
        ];

        foreach ($candidates as $cData) {
            $cData['organization_id'] = $org->id;
            $candidate = Candidate::create($cData);

            $resume = Resume::create([
                'candidate_id' => $candidate->id,
                'file_path' => 'resumes/sample.pdf',
                'file_name' => strtolower(str_replace(' ', '_', $candidate->full_name)) . '_resume.pdf',
                'file_type' => 'pdf',
                'extracted_text' => "Resume of {$candidate->full_name}\n\nExperience: {$candidate->experience_years} years\nCompany: {$candidate->current_company}\nTitle: {$candidate->current_title}\n\nSkills: Python, JavaScript, React, AWS, Docker, PostgreSQL, Machine Learning\n\nExperience Details:\n- Led development of microservices architecture serving 1M+ users\n- Designed and implemented RESTful APIs with 99.9% uptime\n- Mentored junior developers and conducted code reviews\n- Implemented CI/CD pipelines using GitHub Actions and Docker",
                'uploaded_by' => $hrManager->id,
            ]);

            // Create application for first job
            if ($candidate->id <= 3) {
                JobApplication::create([
                    'job_posting_id' => $job1->id,
                    'candidate_id' => $candidate->id,
                    'resume_id' => $resume->id,
                    'stage' => ['applied', 'ai_shortlisted', 'hr_screening'][($candidate->id - 1) % 3],
                    'applied_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        // Create employees
        $empData = [
            ['first_name' => 'Alice', 'last_name' => 'Wang', 'email' => 'alice.w@acme.com', 'department_id' => $engineering->id, 'designation' => 'Senior Engineer', 'skills_from_resume' => ['Python', 'Django', 'AWS', 'PostgreSQL'], 'combined_skill_profile' => ['top_skills' => ['Python', 'Django', 'AWS']]],
            ['first_name' => 'Bob', 'last_name' => 'Martinez', 'email' => 'bob.m@acme.com', 'department_id' => $engineering->id, 'designation' => 'Tech Lead', 'skills_from_resume' => ['Java', 'Spring Boot', 'Kubernetes', 'Microservices'], 'combined_skill_profile' => ['top_skills' => ['Java', 'Kubernetes', 'Microservices']]],
            ['first_name' => 'Carol', 'last_name' => 'Davis', 'email' => 'carol.d@acme.com', 'department_id' => $data->id, 'designation' => 'Data Scientist', 'skills_from_resume' => ['Python', 'Machine Learning', 'TensorFlow', 'SQL'], 'combined_skill_profile' => ['top_skills' => ['Python', 'ML', 'TensorFlow']]],
            ['first_name' => 'Dan', 'last_name' => 'Wilson', 'email' => 'dan.w@acme.com', 'department_id' => $engineering->id, 'designation' => 'Frontend Developer', 'skills_from_resume' => ['React', 'TypeScript', 'CSS', 'Next.js'], 'combined_skill_profile' => ['top_skills' => ['React', 'TypeScript', 'Next.js']]],
            ['first_name' => 'Eva', 'last_name' => 'Brown', 'email' => 'eva.b@acme.com', 'department_id' => $design->id, 'designation' => 'UX Designer', 'skills_from_resume' => ['Figma', 'CSS', 'React', 'User Research'], 'combined_skill_profile' => ['top_skills' => ['Figma', 'UX', 'React']]],
        ];

        foreach ($empData as $ed) {
            $ed['organization_id'] = $org->id;
            $ed['is_active'] = true;
            Employee::create($ed);
        }

        // Create a project
        Project::create([
            'organization_id' => $org->id,
            'name' => 'Customer Portal Redesign',
            'description' => 'Complete redesign of the customer-facing portal with new features including real-time dashboards, self-service tools, and improved UX.',
            'required_skills' => ['React', 'TypeScript', 'Python', 'AWS', 'PostgreSQL'],
            'required_technologies' => ['React', 'FastAPI', 'AWS Lambda', 'PostgreSQL'],
            'complexity_level' => 'high',
            'domain_context' => 'B2B SaaS customer portal with real-time data visualization',
            'start_date' => now()->addWeeks(2),
            'end_date' => now()->addMonths(4),
            'status' => 'planning',
            'created_by' => $resourceManager->id,
        ]);

        Project::create([
            'organization_id' => $org->id,
            'name' => 'ML Recommendation Engine',
            'description' => 'Build a recommendation engine using collaborative filtering and content-based approaches.',
            'required_skills' => ['Python', 'Machine Learning', 'TensorFlow', 'Docker'],
            'required_technologies' => ['Python', 'TensorFlow', 'Docker', 'Redis'],
            'complexity_level' => 'critical',
            'domain_context' => 'E-commerce product recommendation system',
            'start_date' => now()->addMonth(),
            'end_date' => now()->addMonths(6),
            'status' => 'planning',
            'created_by' => $resourceManager->id,
        ]);
    }
}
