<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSignal;
use App\Models\InterviewFeedback;
use App\Models\InterviewSession;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Organization;
use App\Models\ProjectResourceMatch;
use App\Models\Resume;
use App\Models\SignalSnapshot;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Comprehensive demo data for Nalam Tech — Hiring, Signals, Resource Matching.
 * Run with: php artisan db:seed --class=NalamTechDemoDataSeeder
 *
 * Prerequisites: NalamTechSeeder must have already run (creates org, users, employees, projects).
 */
class NalamTechDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'nalam-tech')->firstOrFail();
        $orgId = $org->id;

        $this->command->info("Seeding demo data for Nalam Tech (ID: {$orgId})...");

        $deptEngineering = Department::where('organization_id', $orgId)->where('name', 'Engineering')->first();
        $deptProduct     = Department::where('organization_id', $orgId)->where('name', 'Product')->first();
        $deptOps         = Department::where('organization_id', $orgId)->where('name', 'Operations')->first();

        $kevin = User::where('email', 'kevin.lee@nalamtech.work')->first();
        $omar  = User::where('email', 'omar.ali@nalamtech.work')->first();

        // ════════════════════════════════════════════════════════════
        // HIRING / ATS
        // ════════════════════════════════════════════════════════════

        $this->command->info('Creating job postings...');

        $job1 = JobPosting::create([
            'organization_id' => $orgId,
            'department_id'   => $deptEngineering->id,
            'title'           => 'Senior .NET Backend Developer',
            'description'     => 'We are looking for an experienced .NET backend developer to join our platform engineering team. You will design and build microservices powering our Azure Migration Platform, working with C#, .NET 8, Azure SQL, and Redis. This is a high-impact role where you will directly influence the architecture of our core migration engine.',
            'requirements'    => "- 5+ years of professional C# / .NET development\n- Strong experience with ASP.NET Core Web API and Entity Framework Core\n- Hands-on experience with Azure services (App Service, Azure SQL, Service Bus, Key Vault)\n- Solid understanding of microservices architecture and distributed systems\n- Experience with Redis or similar caching solutions\n- Familiarity with CI/CD pipelines (Azure DevOps preferred)\n- Strong SQL skills and database design experience\n- Excellent problem-solving and communication skills",
            'min_experience'  => 5,
            'max_experience'  => 10,
            'required_skills' => ['C#', '.NET Core', 'ASP.NET Core', 'Azure SQL', 'Entity Framework', 'Redis', 'REST APIs'],
            'nice_to_have_skills' => ['RabbitMQ', 'Docker', 'Kubernetes', 'GraphQL', 'Terraform'],
            'employment_type' => 'full_time',
            'location'        => 'Hybrid - Bangalore',
            'salary_min'      => 2500000,
            'salary_max'      => 4000000,
            'status'          => 'open',
            'created_by'      => $kevin->id,
        ]);

        $job2 = JobPosting::create([
            'organization_id' => $orgId,
            'department_id'   => $deptEngineering->id,
            'title'           => 'Cloud Solutions Architect',
            'description'     => 'We need a Cloud Solutions Architect to lead the design of our next-generation Azure infrastructure. You will work across teams to define cloud architecture patterns, optimize costs, ensure security compliance, and mentor the engineering team on cloud-native development practices.',
            'requirements'    => "- 8+ years in software engineering with 4+ years in cloud architecture\n- Deep expertise in Microsoft Azure (AKS, App Service, Azure SQL, Cosmos DB, Service Bus)\n- Experience designing multi-tenant SaaS platforms\n- Strong knowledge of infrastructure as code (Terraform, ARM templates, or Bicep)\n- Azure certifications preferred (AZ-305, AZ-104)\n- Experience with cost optimization and FinOps practices\n- Ability to communicate complex technical concepts to non-technical stakeholders",
            'min_experience'  => 8,
            'max_experience'  => 15,
            'required_skills' => ['Azure', 'Cloud Architecture', 'Terraform', 'Microservices', 'Security', '.NET'],
            'nice_to_have_skills' => ['Cosmos DB', 'AKS', 'FinOps', 'Multi-tenant Architecture', 'ARM Templates'],
            'employment_type' => 'full_time',
            'location'        => 'Remote',
            'salary_min'      => 4000000,
            'salary_max'      => 6500000,
            'status'          => 'open',
            'created_by'      => $kevin->id,
        ]);

        $job3 = JobPosting::create([
            'organization_id' => $orgId,
            'department_id'   => $deptEngineering->id,
            'title'           => 'React Frontend Developer',
            'description'     => 'Join our frontend team to build beautiful, performant React applications for our customer analytics dashboard. You will work with TypeScript, Next.js, and real-time data streaming via Azure SignalR to create delightful user experiences.',
            'requirements'    => "- 3+ years of React development with TypeScript\n- Experience with Next.js or similar React frameworks\n- Strong CSS skills (Tailwind CSS, CSS Modules, or styled-components)\n- Experience with data visualization libraries (Recharts, D3, or Chart.js)\n- Understanding of web accessibility (WCAG 2.1)\n- Experience with real-time data (WebSockets, Server-Sent Events)\n- Familiarity with testing (Jest, Playwright, or Cypress)",
            'min_experience'  => 3,
            'max_experience'  => 7,
            'required_skills' => ['React', 'TypeScript', 'Next.js', 'CSS', 'Data Visualization'],
            'nice_to_have_skills' => ['Azure SignalR', 'Storybook', 'Playwright', 'Accessibility', 'Figma'],
            'employment_type' => 'full_time',
            'location'        => 'Hybrid - Bangalore',
            'salary_min'      => 1800000,
            'salary_max'      => 3200000,
            'status'          => 'open',
            'created_by'      => $omar->id,
        ]);

        $job4 = JobPosting::create([
            'organization_id' => $orgId,
            'department_id'   => $deptOps->id,
            'title'           => 'Site Reliability Engineer',
            'description'     => 'We are looking for an SRE to ensure the reliability, scalability, and performance of our Azure-hosted SaaS platform. You will build monitoring, alerting, and incident response systems, and work closely with the development team on reliability engineering practices.',
            'requirements'    => "- 4+ years in DevOps/SRE roles\n- Strong experience with Azure (AKS, Azure Monitor, Application Insights)\n- Proficiency in Terraform for infrastructure as code\n- Experience with monitoring tools (Prometheus, Grafana, Datadog)\n- Strong scripting skills (PowerShell, Bash, Python)\n- Understanding of SLO/SLI/SLA frameworks\n- On-call experience and incident management skills",
            'min_experience'  => 4,
            'max_experience'  => 8,
            'required_skills' => ['Azure', 'Kubernetes', 'Terraform', 'Monitoring', 'CI/CD', 'Linux'],
            'nice_to_have_skills' => ['Prometheus', 'Grafana', 'Chaos Engineering', 'Go', 'Incident Management'],
            'employment_type' => 'full_time',
            'location'        => 'Remote',
            'salary_min'      => 2200000,
            'salary_max'      => 3800000,
            'status'          => 'on_hold',
            'created_by'      => $kevin->id,
        ]);

        $this->command->info('Created 4 job postings');

        // ── Candidates ───────────────────────────────────────────

        $this->command->info('Creating candidates and applications...');

        $candidatesData = [
            // Job 1: Senior .NET Backend Developer
            [
                'first_name' => 'Arjun', 'last_name' => 'Patel',
                'email' => 'arjun.patel@outlook.com', 'phone' => '+91 98765 43210',
                'current_company' => 'Infosys', 'current_title' => 'Senior Software Engineer',
                'experience_years' => 6.5,
                'skills' => ['C#', '.NET Core', 'ASP.NET Core', 'Azure SQL', 'Entity Framework', 'Redis', 'RabbitMQ', 'Docker', 'SQL Server'],
                'source' => 'direct',
                'job' => $job1, 'stage' => 'technical_round_1',
                'ai_score' => 87.5,
                'ai_analysis' => [
                    'overall_assessment' => 'Strong .NET backend candidate with solid Azure experience. 6.5 years at Infosys working on enterprise .NET applications. Good match for our microservices architecture needs.',
                    'skill_match' => ['C# — Expert', '.NET Core — Expert', 'Azure SQL — Advanced', 'Entity Framework — Advanced', 'Redis — Intermediate'],
                    'strengths' => ['Deep .NET ecosystem expertise', 'Production Azure experience', 'Microservices architecture'],
                    'concerns' => ['Limited open-source contributions', 'No Kubernetes experience mentioned'],
                ],
            ],
            [
                'first_name' => 'Priya', 'last_name' => 'Menon',
                'email' => 'priya.menon@gmail.com', 'phone' => '+91 87654 32109',
                'current_company' => 'Wipro', 'current_title' => 'Technical Lead',
                'experience_years' => 8.0,
                'skills' => ['C#', '.NET', 'Azure', 'SQL Server', 'WCF', 'Entity Framework', 'Angular', 'Team Leadership'],
                'source' => 'referral',
                'job' => $job1, 'stage' => 'offer',
                'ai_score' => 91.2,
                'ai_analysis' => [
                    'overall_assessment' => 'Excellent candidate with 8 years of .NET experience and team leadership. Strong architecture skills. Currently leading a team of 6 at Wipro on an Azure migration project — directly relevant experience.',
                    'skill_match' => ['C# — Expert', '.NET — Expert', 'Azure — Advanced', 'SQL Server — Expert', 'Entity Framework — Advanced'],
                    'strengths' => ['Team leadership experience', 'Azure migration experience (directly relevant)', 'Strong architecture skills', 'Referral from Kevin Lee'],
                    'concerns' => ['Higher salary expectations', 'Frontend skills limited to Angular (we use React)'],
                ],
            ],
            [
                'first_name' => 'Rahul', 'last_name' => 'Gupta',
                'email' => 'rahul.g@yahoo.com', 'phone' => '+91 76543 21098',
                'current_company' => 'TCS', 'current_title' => 'Software Developer',
                'experience_years' => 4.0,
                'skills' => ['C#', '.NET Core', 'SQL Server', 'JavaScript', 'HTML', 'CSS', 'Azure Basics'],
                'source' => 'upload',
                'job' => $job1, 'stage' => 'rejected',
                'ai_score' => 52.3,
                'ai_analysis' => [
                    'overall_assessment' => 'Junior developer with 4 years experience. Below our minimum requirement of 5 years. Limited Azure and microservices experience. Skills are foundational but lack depth for a senior role.',
                    'skill_match' => ['C# — Intermediate', '.NET Core — Intermediate', 'SQL Server — Basic'],
                    'strengths' => ['Solid fundamentals in C# and SQL'],
                    'concerns' => ['Below minimum experience requirement', 'No microservices experience', 'No Redis/caching experience', 'Limited Azure exposure'],
                ],
                'rejection_reason' => 'Does not meet minimum experience requirement (5 years). Limited Azure and distributed systems experience for a senior role.',
            ],

            // Job 2: Cloud Solutions Architect
            [
                'first_name' => 'Deepak', 'last_name' => 'Krishnan',
                'email' => 'deepak.krishnan@microsoft.com', 'phone' => '+91 65432 10987',
                'current_company' => 'Microsoft', 'current_title' => 'Senior Cloud Engineer',
                'experience_years' => 10.0,
                'skills' => ['Azure', 'Cloud Architecture', 'Terraform', '.NET', 'Kubernetes', 'Cosmos DB', 'Security', 'Multi-tenant SaaS', 'DevOps'],
                'source' => 'direct',
                'job' => $job2, 'stage' => 'technical_round_2',
                'ai_score' => 94.8,
                'ai_analysis' => [
                    'overall_assessment' => 'Outstanding candidate from Microsoft with 10 years experience. Deep Azure expertise from working at the source. AZ-305 and AZ-104 certified. Has designed multi-tenant SaaS platforms at scale. Top-tier match.',
                    'skill_match' => ['Azure — Expert', 'Cloud Architecture — Expert', 'Terraform — Advanced', 'Multi-tenant SaaS — Expert', 'Security — Advanced'],
                    'strengths' => ['Direct Microsoft/Azure experience', 'Azure certified (AZ-305, AZ-104)', 'Multi-tenant SaaS design experience', 'Cost optimization expertise'],
                    'concerns' => ['High salary expectations (currently at Microsoft)', 'May find our scale too small initially'],
                ],
            ],
            [
                'first_name' => 'Sanjay', 'last_name' => 'Raghavan',
                'email' => 'sanjay.r@hotmail.com', 'phone' => '+91 54321 09876',
                'current_company' => 'Cognizant', 'current_title' => 'Solution Architect',
                'experience_years' => 9.0,
                'skills' => ['Azure', 'AWS', 'Cloud Architecture', '.NET', 'Java', 'Terraform', 'ARM Templates'],
                'source' => 'upload',
                'job' => $job2, 'stage' => 'hr_screening',
                'ai_score' => 78.4,
                'ai_analysis' => [
                    'overall_assessment' => 'Good multi-cloud architect with 9 years experience. Stronger on AWS than Azure. Has .NET and Java experience which gives breadth. Needs deeper Azure specialization for this role.',
                    'skill_match' => ['Cloud Architecture — Advanced', 'Azure — Intermediate', 'Terraform — Advanced', '.NET — Intermediate'],
                    'strengths' => ['Multi-cloud experience', 'Broad technology stack', 'Solution architecture methodology'],
                    'concerns' => ['Stronger in AWS than Azure', 'No Azure certifications', 'No specific multi-tenant SaaS experience'],
                ],
            ],

            // Job 3: React Frontend Developer
            [
                'first_name' => 'Ananya', 'last_name' => 'Reddy',
                'email' => 'ananya.reddy@gmail.com', 'phone' => '+91 43210 98765',
                'current_company' => 'Zoho', 'current_title' => 'Frontend Developer',
                'experience_years' => 4.5,
                'skills' => ['React', 'TypeScript', 'Next.js', 'Tailwind CSS', 'Recharts', 'Jest', 'Playwright', 'Figma', 'Accessibility'],
                'source' => 'direct',
                'job' => $job3, 'stage' => 'technical_round_1',
                'ai_score' => 89.7,
                'ai_analysis' => [
                    'overall_assessment' => 'Excellent React developer from Zoho with 4.5 years experience. Strong TypeScript and Next.js skills. Has direct experience with Recharts and accessibility — both critical for our dashboard project. Very strong match.',
                    'skill_match' => ['React — Expert', 'TypeScript — Advanced', 'Next.js — Advanced', 'Recharts — Advanced', 'Accessibility — Advanced'],
                    'strengths' => ['Direct Recharts experience', 'WCAG accessibility expertise', 'Playwright testing experience', 'Strong design collaboration skills (Figma)'],
                    'concerns' => ['No real-time/WebSocket experience mentioned', 'Salary expectations may be above range'],
                ],
            ],
            [
                'first_name' => 'Vikram', 'last_name' => 'Nair',
                'email' => 'vikram.nair@proton.me', 'phone' => '+91 32109 87654',
                'current_company' => 'Freshworks', 'current_title' => 'UI Developer',
                'experience_years' => 3.0,
                'skills' => ['React', 'JavaScript', 'CSS', 'HTML', 'Redux', 'Sass', 'Bootstrap', 'jQuery'],
                'source' => 'upload',
                'job' => $job3, 'stage' => 'applied',
                'ai_score' => 61.5,
                'ai_analysis' => [
                    'overall_assessment' => 'Junior frontend developer with 3 years experience. React skills are present but lacks TypeScript and Next.js. Still using older patterns (Redux, jQuery). Needs upskilling in modern frontend practices.',
                    'skill_match' => ['React — Intermediate', 'CSS — Intermediate', 'JavaScript — Intermediate'],
                    'strengths' => ['React fundamentals are solid', 'Good CSS skills'],
                    'concerns' => ['No TypeScript experience', 'No Next.js experience', 'Using legacy patterns (jQuery, Redux)', 'No data visualization experience', 'No accessibility experience'],
                ],
            ],
            [
                'first_name' => 'Meera', 'last_name' => 'Joshi',
                'email' => 'meera.joshi@outlook.com', 'phone' => '+91 21098 76543',
                'current_company' => 'Flipkart', 'current_title' => 'Senior Frontend Engineer',
                'experience_years' => 5.5,
                'skills' => ['React', 'TypeScript', 'Next.js', 'GraphQL', 'D3.js', 'CSS Modules', 'Storybook', 'Performance Optimization'],
                'source' => 'referral',
                'job' => $job3, 'stage' => 'ai_shortlisted',
                'ai_score' => 85.2,
                'ai_analysis' => [
                    'overall_assessment' => 'Strong senior frontend engineer from Flipkart. 5.5 years of React/TypeScript experience. Uses D3.js for data visualization which shows transferable skills to Recharts. Performance optimization experience is valuable.',
                    'skill_match' => ['React — Expert', 'TypeScript — Expert', 'Next.js — Advanced', 'Data Visualization — Advanced', 'Storybook — Advanced'],
                    'strengths' => ['Senior-level React expertise', 'Data visualization with D3.js', 'Performance optimization at Flipkart scale', 'Storybook component library experience'],
                    'concerns' => ['No specific Recharts experience (uses D3.js)', 'No accessibility mentioned', 'Higher seniority than role requires — may leave quickly'],
                ],
            ],

            // Job 4: SRE (on hold) — one candidate applied before hold
            [
                'first_name' => 'Kiran', 'last_name' => 'Das',
                'email' => 'kiran.das@gmail.com', 'phone' => '+91 10987 65432',
                'current_company' => 'Razorpay', 'current_title' => 'DevOps Engineer',
                'experience_years' => 5.0,
                'skills' => ['Azure', 'AWS', 'Kubernetes', 'Terraform', 'Prometheus', 'Grafana', 'Python', 'Bash', 'Docker', 'Linux'],
                'source' => 'direct',
                'job' => $job4, 'stage' => 'applied',
                'ai_score' => 82.1,
                'ai_analysis' => [
                    'overall_assessment' => 'Solid DevOps engineer from Razorpay with 5 years experience. Good mix of cloud platforms. Strong monitoring stack experience. Position is currently on hold.',
                    'skill_match' => ['Kubernetes — Advanced', 'Terraform — Advanced', 'Azure — Intermediate', 'Monitoring — Advanced', 'Linux — Advanced'],
                    'strengths' => ['Production Kubernetes experience at scale', 'Strong monitoring/observability skills', 'Multi-cloud experience'],
                    'concerns' => ['Stronger in AWS than Azure', 'No .NET ecosystem experience'],
                ],
            ],
        ];

        foreach ($candidatesData as $cd) {
            $candidate = Candidate::create([
                'organization_id'  => $orgId,
                'first_name'       => $cd['first_name'],
                'last_name'        => $cd['last_name'],
                'email'            => $cd['email'],
                'phone'            => $cd['phone'],
                'current_company'  => $cd['current_company'],
                'current_title'    => $cd['current_title'],
                'experience_years' => $cd['experience_years'],
                'skills'           => $cd['skills'],
                'source'           => $cd['source'],
                'notes'            => $cd['notes'] ?? null,
            ]);

            $resume = Resume::create([
                'candidate_id' => $candidate->id,
                'file_path'    => "resumes/nalam-tech/{$candidate->id}_" . strtolower($cd['first_name']) . '_' . strtolower($cd['last_name']) . '_resume.pdf',
                'file_name'    => $cd['first_name'] . '_' . $cd['last_name'] . '_Resume.pdf',
                'file_type'    => 'pdf',
                'extracted_text' => "Resume of {$cd['first_name']} {$cd['last_name']}\n{$cd['current_title']} at {$cd['current_company']}\nExperience: {$cd['experience_years']} years\nSkills: " . implode(', ', $cd['skills']),
                'uploaded_by'  => $kevin->id,
            ]);

            $application = JobApplication::create([
                'job_posting_id'  => $cd['job']->id,
                'candidate_id'    => $candidate->id,
                'resume_id'       => $resume->id,
                'stage'           => $cd['stage'],
                'applied_at'      => now()->subDays(rand(3, 21)),
                'ai_score'        => $cd['ai_score'],
                'ai_analysis'     => $cd['ai_analysis'],
                'ai_analyzed_at'  => now()->subDays(rand(2, 18)),
                'rejection_reason' => $cd['rejection_reason'] ?? null,
            ]);

            // Store application for interview creation
            $cd['_application'] = $application;
            $cd['_candidate']   = $candidate;

            $this->command->info("  Candidate: {$cd['first_name']} {$cd['last_name']} → {$cd['stage']} (Score: {$cd['ai_score']})");
        }

        $this->command->info('Created 9 candidates with resumes and applications');

        // ── Interview Sessions ───────────────────────────────────

        $this->command->info('Creating interview sessions...');

        // Arjun Patel — has a completed HR screening and upcoming technical round
        $arjunApp = JobApplication::whereHas('candidate', fn($q) => $q->where('email', 'arjun.patel@outlook.com'))->first();
        $arjunCandidate = Candidate::where('email', 'arjun.patel@outlook.com')->first();

        $arjunHrInterview = InterviewSession::create([
            'organization_id'    => $orgId,
            'job_application_id' => $arjunApp->id,
            'candidate_id'       => $arjunCandidate->id,
            'interviewer_id'     => $omar->id,
            'assigned_by'        => $kevin->id,
            'status'             => 'completed',
            'outcome'            => 'pass',
            'interview_type'     => 'hr_screening',
            'scheduled_at'       => now()->subDays(5),
            'started_at'         => now()->subDays(5),
            'ended_at'           => now()->subDays(5)->addMinutes(35),
            'duration_seconds'   => 2100,
            'summary'            => [
                'key_points' => ['Strong communication skills', 'Motivated by our product mission', 'Salary expectations within range', 'Available in 30 days notice'],
                'recommendation' => 'Proceed to technical round',
            ],
            'notes' => 'Good cultural fit. Enthusiastic about Azure migration space. Currently at Infosys but looking for a product company.',
        ]);

        InterviewFeedback::create([
            'job_application_id'   => $arjunApp->id,
            'interviewer_id'       => $omar->id,
            'interview_session_id' => $arjunHrInterview->id,
            'stage'                => 'hr_screening',
            'rating'               => 8,
            'strengths'            => 'Clear communication, strong motivation, good cultural fit. Understands the Azure migration problem space well from his Infosys experience.',
            'weaknesses'           => 'Could be more confident in salary negotiation. Hasn\'t worked in a product company before.',
            'recommendation'       => 'yes',
            'notes'                => 'Recommend proceeding to technical round with Kevin.',
        ]);

        // Arjun's upcoming technical round
        InterviewSession::create([
            'organization_id'    => $orgId,
            'job_application_id' => $arjunApp->id,
            'candidate_id'       => $arjunCandidate->id,
            'interviewer_id'     => $kevin->id,
            'assigned_by'        => $omar->id,
            'status'             => 'scheduled',
            'interview_type'     => 'technical_round_1',
            'scheduled_at'       => now()->addDays(2)->setHour(14)->setMinute(0),
            'notes'              => 'Focus on .NET Core API design, Azure services, and system design for migration scenarios.',
        ]);

        // Priya Menon — completed both technical rounds, in offer stage
        $priyaApp = JobApplication::whereHas('candidate', fn($q) => $q->where('email', 'priya.menon@gmail.com'))->first();
        $priyaCandidate = Candidate::where('email', 'priya.menon@gmail.com')->first();

        $priyaTech1 = InterviewSession::create([
            'organization_id'    => $orgId,
            'job_application_id' => $priyaApp->id,
            'candidate_id'       => $priyaCandidate->id,
            'interviewer_id'     => $kevin->id,
            'assigned_by'        => $omar->id,
            'status'             => 'completed',
            'outcome'            => 'pass',
            'interview_type'     => 'technical_round_1',
            'scheduled_at'       => now()->subDays(10),
            'started_at'         => now()->subDays(10),
            'ended_at'           => now()->subDays(10)->addMinutes(55),
            'duration_seconds'   => 3300,
            'summary'            => [
                'key_points' => ['Excellent system design skills', 'Deep .NET and Azure understanding', 'Solved the migration architecture problem elegantly', 'Currently leading a similar project at Wipro'],
                'recommendation' => 'Strong pass — proceed to final round',
            ],
        ]);

        InterviewFeedback::create([
            'job_application_id'   => $priyaApp->id,
            'interviewer_id'       => $kevin->id,
            'interview_session_id' => $priyaTech1->id,
            'stage'                => 'technical_round_1',
            'rating'               => 9,
            'strengths'            => 'Outstanding system design skills. Proposed a multi-tenant migration engine with event-driven architecture that closely aligns with our planned approach. Deep understanding of Azure services and their trade-offs.',
            'weaknesses'           => 'Frontend skills are limited to Angular. Would need to collaborate closely with frontend team for API contract design.',
            'recommendation'       => 'strong_yes',
            'notes'                => 'One of the strongest .NET candidates I\'ve interviewed. Her Azure migration experience at Wipro is directly transferable. Strong hire recommendation.',
        ]);

        $nehaUser = User::where('email', 'neha.sharma@nalamtech.work')->first();
        $priyaTech2 = InterviewSession::create([
            'organization_id'    => $orgId,
            'job_application_id' => $priyaApp->id,
            'candidate_id'       => $priyaCandidate->id,
            'interviewer_id'     => $nehaUser->id,
            'assigned_by'        => $kevin->id,
            'status'             => 'completed',
            'outcome'            => 'pass',
            'interview_type'     => 'technical_round_2',
            'scheduled_at'       => now()->subDays(7),
            'started_at'         => now()->subDays(7),
            'ended_at'           => now()->subDays(7)->addMinutes(50),
            'duration_seconds'   => 3000,
            'summary'            => [
                'key_points' => ['Strong coding skills in C#', 'Clean code architecture', 'Good understanding of EF Core patterns', 'Collaborative problem-solving approach'],
                'recommendation' => 'Pass — ready for offer',
            ],
        ]);

        InterviewFeedback::create([
            'job_application_id'   => $priyaApp->id,
            'interviewer_id'       => $nehaUser->id,
            'interview_session_id' => $priyaTech2->id,
            'stage'                => 'technical_round_2',
            'rating'               => 8,
            'strengths'            => 'Clean coding style, strong EF Core patterns, good understanding of CQRS. Handled the live coding exercise well under pressure.',
            'weaknesses'           => 'Took some time to warm up with the pair programming format. Redis caching strategy was basic but functional.',
            'recommendation'       => 'yes',
            'notes'                => 'Solid technical candidate. Would be a great addition to the backend team. Recommend offer.',
        ]);

        // Deepak Krishnan — completed first technical round, second round scheduled
        $deepakApp = JobApplication::whereHas('candidate', fn($q) => $q->where('email', 'deepak.krishnan@microsoft.com'))->first();
        $deepakCandidate = Candidate::where('email', 'deepak.krishnan@microsoft.com')->first();

        $deepakTech1 = InterviewSession::create([
            'organization_id'    => $orgId,
            'job_application_id' => $deepakApp->id,
            'candidate_id'       => $deepakCandidate->id,
            'interviewer_id'     => $kevin->id,
            'assigned_by'        => $omar->id,
            'status'             => 'completed',
            'outcome'            => 'pass',
            'interview_type'     => 'technical_round_1',
            'scheduled_at'       => now()->subDays(3),
            'started_at'         => now()->subDays(3),
            'ended_at'           => now()->subDays(3)->addMinutes(60),
            'duration_seconds'   => 3600,
            'summary'            => [
                'key_points' => ['World-class Azure expertise', 'Designed multi-tenant platforms at Microsoft scale', 'Strong opinions on IaC and FinOps', 'Overqualified for the role — which is a good problem'],
                'recommendation' => 'Strong pass',
            ],
        ]);

        InterviewFeedback::create([
            'job_application_id'   => $deepakApp->id,
            'interviewer_id'       => $kevin->id,
            'interview_session_id' => $deepakTech1->id,
            'stage'                => 'technical_round_1',
            'rating'               => 10,
            'strengths'            => 'Exceptional Azure knowledge — has designed systems at Microsoft that handle millions of tenants. Terraform expertise is production-grade. Provided insights on cost optimization that could save us 30-40% on Azure spend.',
            'weaknesses'           => 'May be overqualified — need to ensure the role scope is challenging enough. Compensation expectations will be high (Microsoft-level).',
            'recommendation'       => 'strong_yes',
            'notes'                => 'The best cloud architecture candidate I\'ve ever interviewed. If we can match compensation, this hire would transform our infrastructure.',
        ]);

        // Deepak's upcoming second round
        InterviewSession::create([
            'organization_id'    => $orgId,
            'job_application_id' => $deepakApp->id,
            'candidate_id'       => $deepakCandidate->id,
            'interviewer_id'     => User::where('email', 'maya.chen@nalamtech.work')->first()->id,
            'assigned_by'        => $kevin->id,
            'status'             => 'scheduled',
            'interview_type'     => 'technical_round_2',
            'scheduled_at'       => now()->addDays(1)->setHour(10)->setMinute(30),
            'notes'              => 'Focus on infrastructure design, Terraform modules, AKS architecture, and cost optimization strategies.',
        ]);

        // Ananya Reddy — technical round scheduled (upcoming)
        $ananyaApp = JobApplication::whereHas('candidate', fn($q) => $q->where('email', 'ananya.reddy@gmail.com'))->first();
        $ananyaCandidate = Candidate::where('email', 'ananya.reddy@gmail.com')->first();

        InterviewSession::create([
            'organization_id'    => $orgId,
            'job_application_id' => $ananyaApp->id,
            'candidate_id'       => $ananyaCandidate->id,
            'interviewer_id'     => User::where('email', 'nina.tan@nalamtech.work')->first()->id,
            'assigned_by'        => $omar->id,
            'status'             => 'scheduled',
            'interview_type'     => 'technical_round_1',
            'scheduled_at'       => now()->addDays(3)->setHour(15)->setMinute(0),
            'notes'              => 'Focus on React/TypeScript architecture, component design patterns, data visualization, and accessibility.',
        ]);

        $this->command->info('Created 7 interview sessions with 4 feedback records');

        // ════════════════════════════════════════════════════════════
        // SIGNAL INTELLIGENCE — Balanced team with realistic patterns
        // ════════════════════════════════════════════════════════════

        $this->command->info('Seeding signal intelligence data...');

        $employees = Employee::where('organization_id', $orgId)->get()->keyBy(fn($e) => $e->first_name . ' ' . $e->last_name);

        // Realistic profiles: Kevin=leader, Omar=PM/collab heavy, Neha=solid, Nina=slight dip, Maya=overloaded
        $weeklySignals = [
            'Kevin Lee' => [
                // Strong leader: high collaboration, consistent, healthy work pattern
                'W10' => ['msgs' => 48, 'channels' => 20, 'private' => 28, 'calls' => 9, 'meetings' => 14, 'collab' => 16, 'after_hrs' => 8.5],
                'W11' => ['msgs' => 52, 'channels' => 22, 'private' => 30, 'calls' => 8, 'meetings' => 13, 'collab' => 17, 'after_hrs' => 7.2],
                'W12' => ['msgs' => 45, 'channels' => 19, 'private' => 26, 'calls' => 10, 'meetings' => 15, 'collab' => 15, 'after_hrs' => 9.1],
                'W13' => ['msgs' => 42, 'channels' => 18, 'private' => 24, 'calls' => 8, 'meetings' => 12, 'collab' => 14, 'after_hrs' => 6.8],
            ],
            'Omar Ali' => [
                // PM: highest collaboration, lots of meetings, moderate messages
                'W10' => ['msgs' => 58, 'channels' => 24, 'private' => 34, 'calls' => 6, 'meetings' => 18, 'collab' => 20, 'after_hrs' => 5.2],
                'W11' => ['msgs' => 62, 'channels' => 26, 'private' => 36, 'calls' => 5, 'meetings' => 19, 'collab' => 21, 'after_hrs' => 4.8],
                'W12' => ['msgs' => 55, 'channels' => 22, 'private' => 33, 'calls' => 7, 'meetings' => 17, 'collab' => 19, 'after_hrs' => 6.1],
                'W13' => ['msgs' => 56, 'channels' => 22, 'private' => 34, 'calls' => 5, 'meetings' => 15, 'collab' => 18, 'after_hrs' => 5.5],
            ],
            'Neha Sharma' => [
                // Solid backend dev: moderate comms, focused, consistent
                'W10' => ['msgs' => 32, 'channels' => 13, 'private' => 19, 'calls' => 3, 'meetings' => 8, 'collab' => 10, 'after_hrs' => 4.2],
                'W11' => ['msgs' => 35, 'channels' => 14, 'private' => 21, 'calls' => 4, 'meetings' => 9, 'collab' => 11, 'after_hrs' => 3.8],
                'W12' => ['msgs' => 30, 'channels' => 12, 'private' => 18, 'calls' => 3, 'meetings' => 7, 'collab' => 9, 'after_hrs' => 5.1],
                'W13' => ['msgs' => 31, 'channels' => 12, 'private' => 19, 'calls' => 3, 'meetings' => 8, 'collab' => 10, 'after_hrs' => 3.5],
            ],
            'Nina Tan' => [
                // Slight disengagement trend: messages dropping, fewer meetings recently
                'W10' => ['msgs' => 38, 'channels' => 15, 'private' => 23, 'calls' => 4, 'meetings' => 10, 'collab' => 13, 'after_hrs' => 3.0],
                'W11' => ['msgs' => 34, 'channels' => 13, 'private' => 21, 'calls' => 3, 'meetings' => 8, 'collab' => 11, 'after_hrs' => 2.5],
                'W12' => ['msgs' => 28, 'channels' => 10, 'private' => 18, 'calls' => 2, 'meetings' => 6, 'collab' => 8, 'after_hrs' => 1.8],
                'W13' => ['msgs' => 22, 'channels' => 8, 'private' => 14, 'calls' => 1, 'meetings' => 5, 'collab' => 7, 'after_hrs' => 1.2],
            ],
            'Maya Chen' => [
                // Overloaded: high after-hours, lots of context switching, increasing workload
                'W10' => ['msgs' => 40, 'channels' => 16, 'private' => 24, 'calls' => 5, 'meetings' => 11, 'collab' => 14, 'after_hrs' => 15.2],
                'W11' => ['msgs' => 44, 'channels' => 18, 'private' => 26, 'calls' => 6, 'meetings' => 12, 'collab' => 15, 'after_hrs' => 18.5],
                'W12' => ['msgs' => 48, 'channels' => 20, 'private' => 28, 'calls' => 7, 'meetings' => 14, 'collab' => 16, 'after_hrs' => 22.1],
                'W13' => ['msgs' => 50, 'channels' => 21, 'private' => 29, 'calls' => 6, 'meetings' => 13, 'collab' => 15, 'after_hrs' => 19.8],
            ],
        ];

        foreach ($weeklySignals as $name => $weeks) {
            $employee = $employees[$name] ?? null;
            if (!$employee) continue;

            foreach ($weeks as $week => $data) {
                $period = "2026-{$week}";
                $metrics = [
                    ['key' => 'messages_sent_count',         'value' => $data['msgs'],      'unit' => 'count'],
                    ['key' => 'channel_messages_count',      'value' => $data['channels'],  'unit' => 'count'],
                    ['key' => 'private_chat_messages_count', 'value' => $data['private'],   'unit' => 'count'],
                    ['key' => 'calls_count',                 'value' => $data['calls'],     'unit' => 'count'],
                    ['key' => 'meetings_attended_count',     'value' => $data['meetings'],  'unit' => 'count'],
                    ['key' => 'unique_collaborators_count',  'value' => $data['collab'],    'unit' => 'count'],
                    ['key' => 'after_hours_message_pct',     'value' => $data['after_hrs'], 'unit' => 'percent'],
                ];

                foreach ($metrics as $m) {
                    EmployeeSignal::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'source_type' => 'teams',
                            'metric_key'  => $m['key'],
                            'period'      => $period,
                        ],
                        [
                            'organization_id' => $orgId,
                            'metric_value'    => $m['value'],
                            'metric_unit'     => $m['unit'],
                        ]
                    );
                }
            }
        }

        $this->command->info('Seeded 4 weeks of signal data for 5 employees');

        // ── Signal Snapshots (AI-computed indices) ───────────────

        $this->command->info('Creating signal snapshots...');

        $snapshots = [
            'Kevin Lee' => [
                'W12' => ['consistency' => 85, 'recovery' => 72, 'workload' => 55, 'context_switch' => 35, 'collaboration' => 88,
                    'summary' => 'Kevin maintains high consistency and strong collaboration as team lead. Workload is balanced. After-hours work is moderate and stable — healthy leadership pattern.'],
                'W13' => ['consistency' => 83, 'recovery' => 75, 'workload' => 50, 'context_switch' => 32, 'collaboration' => 85,
                    'summary' => 'Continued strong performance with slight reduction in workload this week. Collaboration remains high. No concerning patterns detected.'],
            ],
            'Omar Ali' => [
                'W12' => ['consistency' => 80, 'recovery' => 68, 'workload' => 62, 'context_switch' => 45, 'collaboration' => 95,
                    'summary' => 'Omar shows the highest collaboration density on the team — expected for a PM role. Context switching is elevated due to cross-team coordination. Monitor workload as sprint review approaches.'],
                'W13' => ['consistency' => 82, 'recovery' => 70, 'workload' => 58, 'context_switch' => 42, 'collaboration' => 92,
                    'summary' => 'Workload slightly decreased post-sprint planning. Collaboration still highest on team. Healthy pattern for PM role.'],
            ],
            'Neha Sharma' => [
                'W12' => ['consistency' => 88, 'recovery' => 80, 'workload' => 45, 'context_switch' => 22, 'collaboration' => 62,
                    'summary' => 'Neha is the most consistent performer on the team. Low context switching indicates deep focus work — ideal for backend development. After-hours work is minimal. Excellent work-life balance.'],
                'W13' => ['consistency' => 90, 'recovery' => 82, 'workload' => 42, 'context_switch' => 20, 'collaboration' => 60,
                    'summary' => 'Maintaining excellent consistency. The most focused developer on the team with minimal distractions. Strong recovery signals suggest sustainable pace.'],
            ],
            'Nina Tan' => [
                'W12' => ['consistency' => 62, 'recovery' => 45, 'workload' => 35, 'context_switch' => 28, 'collaboration' => 48,
                    'summary' => 'Nina shows a declining engagement trend over the past 3 weeks. Messages, meetings, and collaboration have all decreased. Low after-hours work could indicate healthy boundaries OR disengagement. Recommend a 1:1 check-in.'],
                'W13' => ['consistency' => 55, 'recovery' => 40, 'workload' => 28, 'context_switch' => 25, 'collaboration' => 42,
                    'summary' => 'Engagement continues to decline. Collaboration density dropped to team-low. This pattern warrants attention — schedule a manager 1:1 to understand if there are blockers or personal factors.'],
            ],
            'Maya Chen' => [
                'W12' => ['consistency' => 72, 'recovery' => 35, 'workload' => 85, 'context_switch' => 68, 'collaboration' => 78,
                    'summary' => 'Maya shows clear signs of overload. After-hours work reached 22% — highest on team by far. High context switching suggests she\'s pulled into too many workstreams. Burnout risk is elevated. Recommend redistributing some DevOps tasks.'],
                'W13' => ['consistency' => 70, 'recovery' => 38, 'workload' => 80, 'context_switch' => 65, 'collaboration' => 75,
                    'summary' => 'Workload remains very high though slightly improved. After-hours work decreased slightly to 19.8% but still concerning. Recovery signal is weak — she\'s not getting enough downtime between sprints. Action needed.'],
            ],
        ];

        foreach ($snapshots as $name => $weeks) {
            $employee = $employees[$name] ?? null;
            if (!$employee) continue;

            foreach ($weeks as $week => $data) {
                SignalSnapshot::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'period'      => "2026-{$week}",
                    ],
                    [
                        'organization_id'        => $orgId,
                        'consistency_index'       => $data['consistency'],
                        'recovery_signal'         => $data['recovery'],
                        'workload_pressure'       => $data['workload'],
                        'context_switching_index'  => $data['context_switch'],
                        'collaboration_density'    => $data['collaboration'],
                        'ai_summary'              => $data['summary'],
                        'raw_signals'             => [],
                        'ai_analysis'             => [
                            'engagement_trend' => $data['consistency'] > 75 ? 'stable' : ($data['consistency'] > 60 ? 'declining' : 'concerning'),
                            'burnout_risk' => $data['workload'] > 75 ? 'high' : ($data['workload'] > 55 ? 'moderate' : 'low'),
                            'recommendations' => $data['workload'] > 75
                                ? ['Redistribute workload', 'Reduce after-hours expectations', 'Consider pairing for complex tasks']
                                : ($data['consistency'] < 60
                                    ? ['Schedule 1:1 check-in', 'Explore engagement drivers', 'Review project alignment with interests']
                                    : ['Continue current patterns', 'Recognize contributions']),
                        ],
                    ]
                );
            }
        }

        $this->command->info('Created signal snapshots for 5 employees (2 weeks each)');

        // ════════════════════════════════════════════════════════════
        // RESOURCE MATCHING
        // ════════════════════════════════════════════════════════════

        $this->command->info('Creating resource matches...');

        $projects = \App\Models\Project::where('organization_id', $orgId)->get()->keyBy('name');

        $matches = [
            'Azure Migration Platform' => [
                'Kevin Lee'    => ['score' => 95, 'strengths' => ['C#', '.NET', 'Azure', 'Architecture Design'], 'gaps' => [], 'explanation' => 'Kevin is the ideal lead for this project. His 8+ years of full-stack .NET development and deep Azure expertise make him the strongest match. His architecture design skills are essential for the migration engine.', 'assigned' => true],
                'Neha Sharma'  => ['score' => 88, 'strengths' => ['C#', '.NET Core', 'Azure Functions', 'SQL Server', 'REST APIs'], 'gaps' => ['Terraform'], 'explanation' => 'Neha is a strong match for backend development on the migration platform. Her API design and database optimization skills are directly relevant. Minor gap in Terraform but not critical for her role.', 'assigned' => true],
                'Maya Chen'    => ['score' => 72, 'strengths' => ['Docker', 'Terraform', 'Azure DevOps'], 'gaps' => ['C#', '.NET Core'], 'explanation' => 'Maya can contribute to the infrastructure and deployment aspects of the migration platform. Her Terraform and Docker skills are valuable but she lacks the .NET development skills for core engine work.', 'assigned' => false],
                'Omar Ali'     => ['score' => 55, 'strengths' => ['Agile', 'Azure DevOps', 'Product Strategy'], 'gaps' => ['C#', '.NET Core', 'Docker', 'Terraform'], 'explanation' => 'Omar can serve as product manager for this project. His technical depth is limited for implementation but his Azure DevOps and agile expertise are valuable for planning and tracking.', 'assigned' => true],
                'Nina Tan'     => ['score' => 35, 'strengths' => ['React'], 'gaps' => ['C#', '.NET Core', 'Azure', 'Docker', 'Terraform'], 'explanation' => 'Nina\'s skills don\'t align well with this backend-heavy project. She could contribute to any admin UI components but the core work requires .NET/Azure expertise she doesn\'t have.', 'assigned' => false],
            ],
            'Customer Analytics Dashboard' => [
                'Nina Tan'     => ['score' => 92, 'strengths' => ['React', 'TypeScript', 'Next.js', 'Tailwind CSS', 'Performance Optimization'], 'gaps' => ['Azure SignalR'], 'explanation' => 'Nina is the best match for the frontend of this project. Her React, TypeScript, and Next.js expertise are exactly what\'s needed. She can learn Azure SignalR integration quickly given her WebSocket experience.', 'assigned' => true],
                'Kevin Lee'    => ['score' => 78, 'strengths' => ['React', 'TypeScript', 'Azure', 'Architecture Design'], 'gaps' => [], 'explanation' => 'Kevin can lead the architecture and backend API layer. His full-stack experience bridges frontend and backend needs. Strong Azure SignalR implementation capability.', 'assigned' => true],
                'Neha Sharma'  => ['score' => 75, 'strengths' => ['C#', '.NET Core', 'SQL Server', 'REST APIs'], 'gaps' => ['React', 'TypeScript', 'Azure SignalR'], 'explanation' => 'Neha can build the backend API and data layer for the dashboard. Her SQL Server and API design skills are essential. She would work on the .NET 8 API that feeds data to the React frontend.', 'assigned' => true],
                'Omar Ali'     => ['score' => 60, 'strengths' => ['Product Strategy', 'Data Analysis', 'Stakeholder Management'], 'gaps' => ['React', 'TypeScript', 'C#', 'Azure SignalR'], 'explanation' => 'Omar is well-suited as product manager for this customer-facing project. His stakeholder management and data analysis skills help define the right analytics features.', 'assigned' => true],
                'Maya Chen'    => ['score' => 40, 'strengths' => ['Docker', 'CI/CD'], 'gaps' => ['React', 'TypeScript', 'C#', 'Azure SignalR', 'SQL Server'], 'explanation' => 'Maya can handle the deployment and CI/CD pipeline for this project but is not a good match for the core development work.', 'assigned' => false],
            ],
            'CI/CD Pipeline Modernization' => [
                'Maya Chen'    => ['score' => 96, 'strengths' => ['Azure DevOps', 'Terraform', 'Docker', 'Kubernetes', 'CI/CD', 'PowerShell'], 'gaps' => [], 'explanation' => 'Maya is the perfect lead for this project. Her DevOps expertise covers every required skill. Her existing work on Azure DevOps YAML pipelines and Terraform modules directly applies.', 'assigned' => true],
                'Kevin Lee'    => ['score' => 68, 'strengths' => ['Docker', 'Azure', 'Architecture Design'], 'gaps' => ['Terraform', 'Kubernetes'], 'explanation' => 'Kevin can provide architecture guidance and Docker expertise. His understanding of the application layer helps ensure CI/CD pipelines meet development needs. Not a primary contributor but valuable advisor.', 'assigned' => false],
                'Neha Sharma'  => ['score' => 45, 'strengths' => ['Azure Functions'], 'gaps' => ['Terraform', 'Kubernetes', 'PowerShell', 'Azure DevOps Pipelines'], 'explanation' => 'Neha has limited DevOps skills. She could help define integration test stages for the pipeline but shouldn\'t be a primary resource on this project.', 'assigned' => false],
                'Omar Ali'     => ['score' => 52, 'strengths' => ['Azure DevOps', 'Agile'], 'gaps' => ['Terraform', 'Docker', 'Kubernetes', 'PowerShell'], 'explanation' => 'Omar understands Azure DevOps from a project management perspective. Can help coordinate the migration timeline and stakeholder communication.', 'assigned' => false],
                'Nina Tan'     => ['score' => 20, 'strengths' => [], 'gaps' => ['Azure DevOps', 'Terraform', 'Docker', 'Kubernetes', 'PowerShell'], 'explanation' => 'No relevant skills for this infrastructure-focused project. Nina should focus on the Analytics Dashboard instead.', 'assigned' => false],
            ],
        ];

        foreach ($matches as $projectName => $employeeMatches) {
            $project = $projects[$projectName] ?? null;
            if (!$project) continue;

            foreach ($employeeMatches as $empName => $match) {
                $employee = $employees[$empName] ?? null;
                if (!$employee) continue;

                ProjectResourceMatch::updateOrCreate(
                    [
                        'project_id'  => $project->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'match_score'     => $match['score'],
                        'strength_areas'  => $match['strengths'],
                        'skill_gaps'      => $match['gaps'],
                        'explanation'     => $match['explanation'],
                        'is_assigned'     => $match['assigned'],
                        'assigned_at'     => $match['assigned'] ? now()->subDays(rand(5, 20)) : null,
                    ]
                );
            }
        }

        $this->command->info('Created resource matches for 3 projects × 5 employees');

        // ════════════════════════════════════════════════════════════
        // SUMMARY
        // ════════════════════════════════════════════════════════════

        $this->command->info('');
        $this->command->info('=== Nalam Tech Demo Data Complete ===');
        $this->command->info('');
        $this->command->info('Hiring:');
        $this->command->info('  4 job postings (3 open, 1 on hold)');
        $this->command->info('  9 candidates with AI-scored resumes');
        $this->command->info('  7 interview sessions (3 completed, 3 scheduled, 1 in offer stage)');
        $this->command->info('  4 interview feedback records');
        $this->command->info('');
        $this->command->info('Signal Intelligence:');
        $this->command->info('  4 weeks of Teams/communication signals for 5 employees');
        $this->command->info('  Kevin: Strong leader, consistent');
        $this->command->info('  Omar: High collaboration PM');
        $this->command->info('  Neha: Solid, focused developer');
        $this->command->info('  Nina: Declining engagement — needs attention');
        $this->command->info('  Maya: Overloaded, burnout risk — needs action');
        $this->command->info('');
        $this->command->info('Resource Matching:');
        $this->command->info('  15 project-employee match scores across 3 projects');
        $this->command->info('  8 employees assigned to projects');
    }
}
