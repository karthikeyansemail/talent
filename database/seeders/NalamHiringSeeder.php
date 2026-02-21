<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\InterviewFeedback;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Resume;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class NalamHiringSeeder extends Seeder
{
    private const ORG_ID    = 3;
    private const HR_USER   = 10; // hrm@nalamsystems.work
    private const ADMIN_USER = 9; // admin@nalamsystems.work

    // Department IDs for Nalam Systems
    private const DEPT_ENG  = 5;
    private const DEPT_DESIGN = 6;

    public function run(): void
    {
        $jobs       = $this->createJobs();
        $candidates = $this->createCandidates();
        $this->createApplications($jobs, $candidates);

        $this->command->info('Nalam hiring seed complete.');
        $this->command->info('  Jobs:       ' . count($jobs));
        $this->command->info('  Candidates: ' . count($candidates));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // JOB POSTINGS
    // ──────────────────────────────────────────────────────────────────────────

    private function createJobs(): array
    {
        $now = Carbon::now();
        $jobs = [];

        $definitions = [
            [
                'title'               => 'Senior Backend Engineer (Python / FastAPI)',
                'department_id'       => self::DEPT_ENG,
                'employment_type'     => 'full_time',
                'location'            => 'Bangalore, India (Hybrid)',
                'salary_min'          => 1800000,
                'salary_max'          => 2800000,
                'status'              => 'open',
                'min_experience'      => 4,
                'max_experience'      => 8,
                'required_skills'     => ['Python', 'FastAPI', 'PostgreSQL', 'Redis', 'Docker', 'REST API Design'],
                'nice_to_have_skills' => ['Kubernetes', 'Celery', 'AWS Lambda', 'OpenTelemetry'],
                'description'         => "We are looking for a Senior Backend Engineer to design and build high-throughput APIs for our AI-powered talent intelligence platform. You will own entire service domains, drive technical decisions, and mentor junior engineers.\n\nYou'll work closely with our ML team to expose AI inference endpoints and with the product team to translate complex requirements into reliable, well-tested code.",
                'requirements'        => "- 4+ years of professional Python backend development\n- Strong experience with FastAPI or Flask/Django REST Framework\n- Proficient in SQL (PostgreSQL preferred) and NoSQL (Redis)\n- Experience with Docker-based deployments and CI/CD pipelines\n- Understanding of async programming (asyncio, background tasks)\n- Strong code review and documentation habits",
                'key_responsibilities' => "- Design, build, and maintain RESTful and async API services\n- Lead backend architecture discussions and ADR documentation\n- Write comprehensive unit and integration tests (pytest)\n- Optimize database queries and implement caching strategies\n- Collaborate with frontend and ML teams on API contracts\n- Participate in on-call rotation and incident response",
                'expectations'        => "- Own end-to-end delivery of backend services\n- Maintain >90% test coverage on all new code\n- Produce clear technical documentation for every service\n- Drive down p95 API latency below 200 ms",
                'created_at'          => $now->subDays(18),
            ],
            [
                'title'               => 'Frontend Engineer (React / TypeScript)',
                'department_id'       => self::DEPT_ENG,
                'employment_type'     => 'full_time',
                'location'            => 'Remote (India)',
                'salary_min'          => 1400000,
                'salary_max'          => 2200000,
                'status'              => 'open',
                'min_experience'      => 3,
                'max_experience'      => 6,
                'required_skills'     => ['React', 'TypeScript', 'CSS', 'REST API Integration', 'Jest'],
                'nice_to_have_skills' => ['Next.js', 'Storybook', 'Playwright', 'GraphQL', 'Figma'],
                'description'         => "Join our product team as a Frontend Engineer to build the responsive, accessible UI for our talent intelligence dashboards. You'll create polished data visualisations, interactive tables, and multi-step forms that HR teams use daily.\n\nWe value component-driven development — you'll work from Figma designs, maintain a Storybook library, and own cross-browser quality.",
                'requirements'        => "- 3+ years building production React applications with TypeScript\n- Strong CSS fundamentals (custom properties, grid, flex)\n- Experience consuming and typing REST APIs\n- Familiarity with automated testing (Jest, React Testing Library)\n- Attention to accessibility (WCAG 2.1 AA)\n- Collaborative experience with designers (Figma hand-off)",
                'key_responsibilities' => "- Build and maintain reusable component library in Storybook\n- Implement new product features from Figma prototypes\n- Write unit and e2e tests for all critical user flows\n- Review PRs with a focus on performance and accessibility\n- Liaise with backend team on API shape and error handling\n- Monitor and improve Core Web Vitals",
                'expectations'        => "- Deliver polished, pixel-perfect UIs\n- Zero accessibility regressions on new screens\n- Keep bundle size growth <5 KB gzipped per feature\n- Maintain Storybook coverage for all shared components",
                'created_at'          => $now->subDays(14),
            ],
            [
                'title'               => 'ML / AI Engineer',
                'department_id'       => self::DEPT_ENG,
                'employment_type'     => 'full_time',
                'location'            => 'Bangalore, India (On-site)',
                'salary_min'          => 2000000,
                'salary_max'          => 3200000,
                'status'              => 'open',
                'min_experience'      => 3,
                'max_experience'      => 7,
                'required_skills'     => ['Python', 'LLM APIs', 'Prompt Engineering', 'NLP', 'scikit-learn', 'FastAPI'],
                'nice_to_have_skills' => ['LangChain', 'RAG', 'Vector Databases', 'Fine-tuning', 'MLflow'],
                'description'         => "We're hiring an ML/AI Engineer to strengthen the intelligence layer of our platform. You will design prompts, build evaluation harnesses, and ship production ML features — from resume analysis to resource-match ranking.\n\nThis is a high-impact role: your models directly determine which candidates get shortlisted and which engineers get matched to projects.",
                'requirements'        => "- 3+ years in an applied ML or AI engineering role\n- Hands-on experience with LLM APIs (OpenAI, Anthropic, or similar)\n- Strong Python skills; comfortable with FastAPI for serving\n- Understanding of NLP fundamentals (tokenisation, embeddings, similarity)\n- Experience evaluating and iterating on prompt quality\n- Familiarity with experiment tracking (MLflow, W&B)",
                'key_responsibilities' => "- Design and optimise prompts for resume scoring and skill extraction\n- Build evaluation datasets and automated regression tests for ML features\n- Serve model inference via FastAPI microservices\n- Monitor model drift and score distributions in production\n- Research and prototype new AI features (RAG, fine-tuning)\n- Document model cards and data lineage",
                'expectations'        => "- Maintain and improve AI feature accuracy month-over-month\n- Ship at least one model improvement per sprint\n- Ensure all model outputs have explainability artefacts\n- Keep inference latency under 2 s for 95th percentile",
                'created_at'          => $now->subDays(10),
            ],
            [
                'title'               => 'DevOps / Platform Engineer',
                'department_id'       => self::DEPT_ENG,
                'employment_type'     => 'full_time',
                'location'            => 'Bangalore, India (Hybrid)',
                'salary_min'          => 1600000,
                'salary_max'          => 2600000,
                'status'              => 'open',
                'min_experience'      => 3,
                'max_experience'      => 7,
                'required_skills'     => ['Docker', 'Kubernetes', 'CI/CD', 'Terraform', 'AWS', 'Linux'],
                'nice_to_have_skills' => ['Helm', 'ArgoCD', 'Prometheus', 'Grafana', 'Vault'],
                'description'         => "We are looking for a Platform Engineer to own our cloud infrastructure and developer tooling. You will manage AWS EKS clusters, CI/CD pipelines, and observability stacks — making sure engineers can ship fast and the platform is reliable.\n\nYou'll work autonomously on infrastructure projects and partner closely with backend engineers on deployment patterns.",
                'requirements'        => "- 3+ years in DevOps, SRE, or Platform Engineering\n- Solid Kubernetes experience (EKS or GKE preferred)\n- Infrastructure-as-code with Terraform\n- CI/CD using GitHub Actions, GitLab CI, or Jenkins\n- Strong AWS knowledge (EKS, RDS, S3, CloudWatch, IAM)\n- Linux system administration and bash scripting",
                'key_responsibilities' => "- Maintain and scale Kubernetes clusters across environments\n- Manage Terraform-based AWS infrastructure\n- Build and maintain CI/CD pipelines for all services\n- Implement observability (metrics, logs, traces)\n- Conduct security audits and dependency scans\n- On-call for infrastructure incidents",
                'expectations'        => "- Maintain 99.9% platform uptime\n- Reduce deployment lead time to under 10 minutes\n- Zero critical CVEs unpatched >7 days\n- Document all runbooks in the wiki",
                'created_at'          => $now->subDays(7),
            ],
            [
                'title'               => 'Product Manager — Talent Intelligence',
                'department_id'       => self::DEPT_DESIGN,
                'employment_type'     => 'full_time',
                'location'            => 'Remote (India)',
                'salary_min'          => 1800000,
                'salary_max'          => 2800000,
                'status'              => 'open',
                'min_experience'      => 4,
                'max_experience'      => 8,
                'required_skills'     => ['Product Management', 'User Research', 'Data Analysis', 'Roadmap Planning', 'Agile/Scrum'],
                'nice_to_have_skills' => ['SQL', 'HRTech domain knowledge', 'Figma', 'A/B Testing'],
                'description'         => "We're seeking a Product Manager to own the roadmap for our AI-powered talent intelligence features. You will define the product vision for resume analysis, candidate ranking, and resource allocation — working with engineers, designers, and customers daily.\n\nThis is a 0→1 PM role: many features you ship will be the first of their kind.",
                'requirements'        => "- 4+ years of product management, preferably in B2B SaaS\n- Strong analytical skills — comfortable with data, metrics, and SQL\n- Experience running user interviews and synthesising qualitative insights\n- Track record of shipping features on time with measurable outcomes\n- Excellent written communication (PRDs, specs, release notes)\n- Understanding of AI/ML concepts as they apply to product",
                'key_responsibilities' => "- Own the product roadmap for hiring and resource allocation pillars\n- Write PRDs, user stories, and acceptance criteria\n- Prioritise backlog with engineering using outcome-based metrics\n- Conduct weekly customer interviews and synthesise findings\n- Define and track KPIs for every shipped feature\n- Partner with design on UX research and usability testing",
                'expectations'        => "- Publish a rolling 3-month roadmap quarterly\n- Achieve >70% customer satisfaction on new features\n- Reduce time-to-hire metric by 20% within 6 months\n- Maintain a 100% PRD completion rate before sprint start",
                'created_at'          => $now->subDays(5),
            ],
            [
                'title'               => 'QA Engineer (Automation)',
                'department_id'       => self::DEPT_ENG,
                'employment_type'     => 'full_time',
                'location'            => 'Bangalore, India (Hybrid)',
                'salary_min'          => 1000000,
                'salary_max'          => 1600000,
                'status'              => 'on_hold',
                'min_experience'      => 2,
                'max_experience'      => 5,
                'required_skills'     => ['Playwright', 'Cypress', 'API Testing', 'Python', 'Test Planning'],
                'nice_to_have_skills' => ['k6', 'Postman', 'SQL', 'CI integration', 'Allure Reports'],
                'description'         => "We need a QA Automation Engineer to build and maintain our test suite across web UI and API layers. You will implement end-to-end Playwright tests, API contract tests, and load tests — and integrate them into our CI pipeline.\n\nThe role is critical for ensuring AI features behave correctly as models and prompts evolve.",
                'requirements'        => "- 2+ years of test automation experience\n- Hands-on with Playwright or Cypress for e2e testing\n- API testing experience (Postman, REST-assured, or pytest+requests)\n- Familiarity with test pyramid concepts and CI integration\n- Able to write clear bug reports and test plans\n- Good SQL skills for backend data validation",
                'key_responsibilities' => "- Maintain e2e test suite with Playwright\n- Write API contract tests for all service endpoints\n- Integrate tests into GitHub Actions CI pipeline\n- Conduct exploratory testing before each release\n- Triage and reproduce production bugs\n- Track test coverage and flakiness metrics",
                'expectations'        => "- Keep e2e suite flakiness below 2%\n- Achieve >80% API endpoint test coverage\n- Zero P0/P1 bugs escaping to production\n- Publish weekly test health report",
                'created_at'          => $now->subDays(22),
            ],
        ];

        foreach ($definitions as $def) {
            $def['organization_id'] = self::ORG_ID;
            $def['created_by']      = self::HR_USER;
            $jobs[]                 = JobPosting::create($def);
        }

        return $jobs;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CANDIDATES + RESUMES
    // ──────────────────────────────────────────────────────────────────────────

    private function createCandidates(): array
    {
        $now       = Carbon::now();
        $candidates = [];

        $profiles = [
            // ── Backend Engineers ──────────────────────────────────────────
            [
                'first_name'      => 'Karan',
                'last_name'       => 'Mehta',
                'email'           => 'karan.mehta@gmail.com',
                'phone'           => '+91-9876543210',
                'current_company' => 'Razorpay',
                'current_title'   => 'Senior Software Engineer',
                'experience_years'=> 5.5,
                'skills'          => ['Python', 'FastAPI', 'PostgreSQL', 'Redis', 'Docker', 'AWS', 'REST API Design', 'Celery'],
                'source'          => 'direct',
                'resume'          => [
                    'text' => $this->resumeText('Karan Mehta', 'Senior Software Engineer', 'Razorpay', 5.5,
                        "Led backend services for Razorpay's payment gateway — handled 2M+ transactions/day. Built FastAPI microservices replacing legacy Flask monolith, reducing p95 latency from 450 ms to 120 ms. Designed Redis-based idempotency layer. Managed PostgreSQL schema migrations with zero downtime. Mentored 3 junior engineers.",
                        ['Python', 'FastAPI', 'PostgreSQL', 'Redis', 'Docker', 'AWS Lambda', 'Celery', 'pytest', 'OpenTelemetry'],
                        [['company'=>'Razorpay','title'=>'Senior Software Engineer','years'=>'2021–Present'],['company'=>'Freshworks','title'=>'Software Engineer','years'=>'2019–2021']],
                        [['degree'=>'B.Tech Computer Science','school'=>'NIT Trichy','year'=>'2019']]
                    ),
                    'parsed' => [
                        'skills' => ['Python', 'FastAPI', 'PostgreSQL', 'Redis', 'Docker', 'AWS Lambda', 'Celery', 'pytest'],
                        'experience' => [
                            ['company' => 'Razorpay', 'title' => 'Senior Software Engineer', 'duration' => '3.5 years'],
                            ['company' => 'Freshworks', 'title' => 'Software Engineer', 'duration' => '2 years'],
                        ],
                        'education' => [['degree' => 'B.Tech Computer Science', 'institution' => 'NIT Trichy', 'year' => '2019']],
                        'certifications' => ['AWS Certified Developer – Associate'],
                    ],
                ],
                'created_at' => $now->subDays(17),
            ],
            [
                'first_name'      => 'Priya',
                'last_name'       => 'Sharma',
                'email'           => 'priya.sharma.dev@outlook.com',
                'phone'           => '+91-9845112233',
                'current_company' => 'Swiggy',
                'current_title'   => 'Software Engineer II',
                'experience_years'=> 4.0,
                'skills'          => ['Python', 'Django', 'FastAPI', 'MySQL', 'Kafka', 'Docker', 'Kubernetes'],
                'source'          => 'upload',
                'resume'          => [
                    'text' => $this->resumeText('Priya Sharma', 'Software Engineer II', 'Swiggy', 4.0,
                        "Developed order-management APIs for Swiggy Instamart serving 500K daily users. Migrated Django monolith endpoints to FastAPI, improving throughput by 40%. Implemented Kafka-based event pipeline for inventory sync. Strong PostgreSQL optimisation experience — reduced slow-query count by 70% through query analysis and index design.",
                        ['Python', 'FastAPI', 'Django', 'PostgreSQL', 'Kafka', 'Docker', 'Kubernetes', 'Redis'],
                        [['company'=>'Swiggy','title'=>'Software Engineer II','years'=>'2022–Present'],['company'=>'Capgemini','title'=>'Software Engineer','years'=>'2020–2022']],
                        [['degree'=>'B.E. Information Technology','school'=>'VTU Bangalore','year'=>'2020']]
                    ),
                    'parsed' => [
                        'skills' => ['Python', 'FastAPI', 'Django', 'PostgreSQL', 'Kafka', 'Docker', 'Kubernetes', 'Redis'],
                        'experience' => [
                            ['company' => 'Swiggy', 'title' => 'Software Engineer II', 'duration' => '2 years'],
                            ['company' => 'Capgemini', 'title' => 'Software Engineer', 'duration' => '2 years'],
                        ],
                        'education' => [['degree' => 'B.E. Information Technology', 'institution' => 'VTU Bangalore', 'year' => '2020']],
                        'certifications' => [],
                    ],
                ],
                'created_at' => $now->subDays(15),
            ],
            [
                'first_name'      => 'Arjun',
                'last_name'       => 'Nair',
                'email'           => 'arjun.nair.eng@gmail.com',
                'phone'           => '+91-9731224455',
                'current_company' => 'CRED',
                'current_title'   => 'Staff Engineer',
                'experience_years'=> 7.0,
                'skills'          => ['Python', 'Go', 'FastAPI', 'PostgreSQL', 'Redis', 'Kubernetes', 'AWS', 'gRPC'],
                'source'          => 'referral',
                'resume'          => [
                    'text' => $this->resumeText('Arjun Nair', 'Staff Engineer', 'CRED', 7.0,
                        "Led platform team at CRED responsible for shared services — auth, notifications, and rate limiting — serving 6M users. Designed gRPC-based internal service mesh. Drove migration from AWS EC2 to EKS, reducing infra costs by 35%. Authored backend coding standards adopted org-wide. Deep expertise in Python and Go performance profiling.",
                        ['Python', 'Go', 'FastAPI', 'PostgreSQL', 'Redis', 'Kubernetes', 'AWS EKS', 'gRPC', 'Terraform', 'Datadog'],
                        [['company'=>'CRED','title'=>'Staff Engineer','years'=>'2020–Present'],['company'=>'Flipkart','title'=>'Senior Engineer','years'=>'2018–2020'],['company'=>'TCS','title'=>'Engineer','years'=>'2017–2018']],
                        [['degree'=>'M.Tech Software Systems','school'=>'BITS Pilani','year'=>'2017']]
                    ),
                    'parsed' => [
                        'skills' => ['Python', 'Go', 'FastAPI', 'PostgreSQL', 'Redis', 'Kubernetes', 'AWS EKS', 'gRPC', 'Terraform'],
                        'experience' => [
                            ['company' => 'CRED', 'title' => 'Staff Engineer', 'duration' => '4 years'],
                            ['company' => 'Flipkart', 'title' => 'Senior Engineer', 'duration' => '2 years'],
                            ['company' => 'TCS', 'title' => 'Engineer', 'duration' => '1 year'],
                        ],
                        'education' => [['degree' => 'M.Tech Software Systems', 'institution' => 'BITS Pilani', 'year' => '2017']],
                        'certifications' => ['AWS Solutions Architect – Professional', 'CKA (Certified Kubernetes Administrator)'],
                    ],
                ],
                'created_at' => $now->subDays(13),
            ],
            // ── Frontend Engineers ────────────────────────────────────────
            [
                'first_name'      => 'Divya',
                'last_name'       => 'Krishnan',
                'email'           => 'divya.krishnan.ui@gmail.com',
                'phone'           => '+91-9512233445',
                'current_company' => 'Meesho',
                'current_title'   => 'Frontend Engineer',
                'experience_years'=> 3.5,
                'skills'          => ['React', 'TypeScript', 'CSS', 'Jest', 'Storybook', 'GraphQL', 'Figma'],
                'source'          => 'upload',
                'resume'          => [
                    'text' => $this->resumeText('Divya Krishnan', 'Frontend Engineer', 'Meesho', 3.5,
                        "Built seller dashboard components used by 500K+ sellers on Meesho. Migrated JavaScript codebase to TypeScript, catching 200+ latent type errors. Maintained Storybook library with 80+ components. Improved FCP from 4.2 s to 1.8 s using code-splitting and lazy loading. Strong Figma-to-code workflow with pixel-perfect delivery.",
                        ['React', 'TypeScript', 'CSS', 'Jest', 'Storybook', 'GraphQL', 'Figma', 'Webpack', 'Playwright'],
                        [['company'=>'Meesho','title'=>'Frontend Engineer','years'=>'2021–Present'],['company'=>'Accenture','title'=>'Associate','years'=>'2020–2021']],
                        [['degree'=>'B.Sc Computer Science','school'=>'Christ University Bangalore','year'=>'2020']]
                    ),
                    'parsed' => [
                        'skills' => ['React', 'TypeScript', 'CSS', 'Jest', 'Storybook', 'GraphQL', 'Figma', 'Webpack'],
                        'experience' => [
                            ['company' => 'Meesho', 'title' => 'Frontend Engineer', 'duration' => '3 years'],
                            ['company' => 'Accenture', 'title' => 'Associate', 'duration' => '0.5 years'],
                        ],
                        'education' => [['degree' => 'B.Sc Computer Science', 'institution' => 'Christ University Bangalore', 'year' => '2020']],
                        'certifications' => [],
                    ],
                ],
                'created_at' => $now->subDays(12),
            ],
            [
                'first_name'      => 'Nikhil',
                'last_name'       => 'Joshi',
                'email'           => 'nikhil.joshi.fe@yahoo.com',
                'phone'           => '+91-9867554433',
                'current_company' => 'Zepto',
                'current_title'   => 'Senior Frontend Engineer',
                'experience_years'=> 5.0,
                'skills'          => ['React', 'Next.js', 'TypeScript', 'Tailwind CSS', 'Playwright', 'Node.js', 'GraphQL'],
                'source'          => 'direct',
                'resume'          => [
                    'text' => $this->resumeText('Nikhil Joshi', 'Senior Frontend Engineer', 'Zepto', 5.0,
                        "Owned the Zepto consumer web app (React + Next.js) serving 1M+ daily sessions. Implemented server-side rendering strategy that reduced TTFB by 60%. Built Playwright test suite covering all critical purchase flows. Led design system migration from CSS modules to Tailwind. Mentored 2 junior frontend engineers.",
                        ['React', 'Next.js', 'TypeScript', 'Tailwind CSS', 'Playwright', 'Node.js', 'GraphQL', 'Vercel', 'Storybook'],
                        [['company'=>'Zepto','title'=>'Senior Frontend Engineer','years'=>'2022–Present'],['company'=>'BookMyShow','title'=>'Frontend Engineer','years'=>'2019–2022']],
                        [['degree'=>'B.Tech Electronics & Communication','school'=>'IIT Roorkee','year'=>'2019']]
                    ),
                    'parsed' => [
                        'skills' => ['React', 'Next.js', 'TypeScript', 'Tailwind CSS', 'Playwright', 'Node.js', 'GraphQL'],
                        'experience' => [
                            ['company' => 'Zepto', 'title' => 'Senior Frontend Engineer', 'duration' => '2 years'],
                            ['company' => 'BookMyShow', 'title' => 'Frontend Engineer', 'duration' => '3 years'],
                        ],
                        'education' => [['degree' => 'B.Tech Electronics & Communication', 'institution' => 'IIT Roorkee', 'year' => '2019']],
                        'certifications' => ['Google UX Design Certificate'],
                    ],
                ],
                'created_at' => $now->subDays(11),
            ],
            // ── ML / AI Engineers ─────────────────────────────────────────
            [
                'first_name'      => 'Ritu',
                'last_name'       => 'Agarwal',
                'email'           => 'ritu.agarwal.ml@gmail.com',
                'phone'           => '+91-9445667788',
                'current_company' => 'Sarvam AI',
                'current_title'   => 'ML Engineer',
                'experience_years'=> 3.5,
                'skills'          => ['Python', 'LLM APIs', 'Prompt Engineering', 'NLP', 'FastAPI', 'LangChain', 'Vector Databases'],
                'source'          => 'direct',
                'resume'          => [
                    'text' => $this->resumeText('Ritu Agarwal', 'ML Engineer', 'Sarvam AI', 3.5,
                        "Built multilingual LLM pipelines at Sarvam AI for Indic language NLP. Designed prompt evaluation framework that reduced hallucination rate by 28%. Implemented RAG system with ChromaDB for document Q&A feature. Served models via FastAPI with sub-500 ms p95 latency. Published 2 internal research notes on prompt optimisation techniques.",
                        ['Python', 'LLM APIs', 'Prompt Engineering', 'NLP', 'FastAPI', 'LangChain', 'ChromaDB', 'HuggingFace', 'MLflow'],
                        [['company'=>'Sarvam AI','title'=>'ML Engineer','years'=>'2022–Present'],['company'=>'Infosys BPM','title'=>'Data Analyst','years'=>'2020–2022']],
                        [['degree'=>'M.Sc Data Science','school'=>'IISc Bangalore','year'=>'2020']]
                    ),
                    'parsed' => [
                        'skills' => ['Python', 'LLM APIs', 'Prompt Engineering', 'NLP', 'FastAPI', 'LangChain', 'ChromaDB', 'HuggingFace'],
                        'experience' => [
                            ['company' => 'Sarvam AI', 'title' => 'ML Engineer', 'duration' => '2.5 years'],
                            ['company' => 'Infosys BPM', 'title' => 'Data Analyst', 'duration' => '2 years'],
                        ],
                        'education' => [['degree' => 'M.Sc Data Science', 'institution' => 'IISc Bangalore', 'year' => '2020']],
                        'certifications' => ['Deep Learning Specialisation – Coursera', 'Hugging Face NLP Certificate'],
                    ],
                ],
                'created_at' => $now->subDays(9),
            ],
            [
                'first_name'      => 'Vikram',
                'last_name'       => 'Bose',
                'email'           => 'vikram.bose.ai@protonmail.com',
                'phone'           => '+91-9334455667',
                'current_company' => 'Fractal Analytics',
                'current_title'   => 'Senior Data Scientist',
                'experience_years'=> 6.0,
                'skills'          => ['Python', 'NLP', 'scikit-learn', 'PyTorch', 'MLflow', 'Spark', 'AWS SageMaker'],
                'source'          => 'upload',
                'resume'          => [
                    'text' => $this->resumeText('Vikram Bose', 'Senior Data Scientist', 'Fractal Analytics', 6.0,
                        "Led ML model development for Fortune 500 FMCG clients at Fractal Analytics. Built NLP-based contract analysis pipeline saving 10,000+ manual review hours annually. Designed sales-forecast ensemble model (RMSE 8.2%) deployed on SageMaker. Strong MLOps background — migrated team to MLflow-based experiment tracking. Comfortable with LLM APIs and prompt chaining.",
                        ['Python', 'NLP', 'scikit-learn', 'PyTorch', 'MLflow', 'AWS SageMaker', 'Spark', 'SQL', 'FastAPI'],
                        [['company'=>'Fractal Analytics','title'=>'Senior Data Scientist','years'=>'2020–Present'],['company'=>'WNS Analytics','title'=>'Data Scientist','years'=>'2018–2020']],
                        [['degree'=>'M.Tech Data Science','school'=>'IIT Bombay','year'=>'2018']]
                    ),
                    'parsed' => [
                        'skills' => ['Python', 'NLP', 'scikit-learn', 'PyTorch', 'MLflow', 'AWS SageMaker', 'Spark', 'FastAPI'],
                        'experience' => [
                            ['company' => 'Fractal Analytics', 'title' => 'Senior Data Scientist', 'duration' => '4 years'],
                            ['company' => 'WNS Analytics', 'title' => 'Data Scientist', 'duration' => '2 years'],
                        ],
                        'education' => [['degree' => 'M.Tech Data Science', 'institution' => 'IIT Bombay', 'year' => '2018']],
                        'certifications' => ['AWS Machine Learning – Specialty', 'TensorFlow Developer Certificate'],
                    ],
                ],
                'created_at' => $now->subDays(8),
            ],
            // ── DevOps / Platform ─────────────────────────────────────────
            [
                'first_name'      => 'Sneha',
                'last_name'       => 'Reddy',
                'email'           => 'sneha.reddy.devops@gmail.com',
                'phone'           => '+91-9123344556',
                'current_company' => 'Dunzo',
                'current_title'   => 'DevOps Engineer',
                'experience_years'=> 4.0,
                'skills'          => ['Docker', 'Kubernetes', 'Terraform', 'AWS', 'GitHub Actions', 'Helm', 'Prometheus'],
                'source'          => 'upload',
                'resume'          => [
                    'text' => $this->resumeText('Sneha Reddy', 'DevOps Engineer', 'Dunzo', 4.0,
                        "Managed AWS EKS clusters for Dunzo's delivery platform (50+ microservices). Implemented Terraform IaC, reducing infra provisioning time from 2 days to 20 minutes. Built GitHub Actions CI/CD pipelines with automated canary deployments. Set up Prometheus + Grafana observability stack. Reduced monthly AWS bill by 22% through right-sizing and reserved instances.",
                        ['Docker', 'Kubernetes', 'Terraform', 'AWS EKS', 'GitHub Actions', 'Helm', 'Prometheus', 'Grafana', 'ArgoCD'],
                        [['company'=>'Dunzo','title'=>'DevOps Engineer','years'=>'2021–Present'],['company'=>'Wipro','title'=>'Cloud Engineer','years'=>'2019–2021']],
                        [['degree'=>'B.E. Computer Science','school'=>'Osmania University','year'=>'2019']]
                    ),
                    'parsed' => [
                        'skills' => ['Docker', 'Kubernetes', 'Terraform', 'AWS EKS', 'GitHub Actions', 'Helm', 'Prometheus', 'Grafana'],
                        'experience' => [
                            ['company' => 'Dunzo', 'title' => 'DevOps Engineer', 'duration' => '3 years'],
                            ['company' => 'Wipro', 'title' => 'Cloud Engineer', 'duration' => '2 years'],
                        ],
                        'education' => [['degree' => 'B.E. Computer Science', 'institution' => 'Osmania University', 'year' => '2019']],
                        'certifications' => ['CKA (Certified Kubernetes Administrator)', 'AWS SysOps Administrator – Associate'],
                    ],
                ],
                'created_at' => $now->subDays(6),
            ],
            [
                'first_name'      => 'Rohan',
                'last_name'       => 'Verma',
                'email'           => 'rohan.verma.ops@gmail.com',
                'phone'           => '+91-9654321098',
                'current_company' => 'OYO',
                'current_title'   => 'Senior Platform Engineer',
                'experience_years'=> 6.5,
                'skills'          => ['Kubernetes', 'Terraform', 'AWS', 'GCP', 'CI/CD', 'Python', 'Linux', 'Vault', 'Istio'],
                'source'          => 'referral',
                'resume'          => [
                    'text' => $this->resumeText('Rohan Verma', 'Senior Platform Engineer', 'OYO', 6.5,
                        "Led platform engineering at OYO across AWS and GCP multi-cloud setup. Designed Istio service mesh for 100+ services, achieving zero-trust security. Built self-service developer portal (Backstage) reducing onboarding from 3 days to 2 hours. Implemented HashiCorp Vault for secrets management. On-call lead for P0/P1 incidents; drove MTTR from 45 min to 12 min.",
                        ['Kubernetes', 'Terraform', 'AWS', 'GCP', 'Istio', 'Vault', 'Python', 'Linux', 'ArgoCD', 'Datadog', 'Backstage'],
                        [['company'=>'OYO','title'=>'Senior Platform Engineer','years'=>'2019–Present'],['company'=>'Paytm','title'=>'Infrastructure Engineer','years'=>'2017–2019']],
                        [['degree'=>'B.Tech IT','school'=>'DTU Delhi','year'=>'2017']]
                    ),
                    'parsed' => [
                        'skills' => ['Kubernetes', 'Terraform', 'AWS', 'GCP', 'Istio', 'Vault', 'Python', 'Linux', 'ArgoCD'],
                        'experience' => [
                            ['company' => 'OYO', 'title' => 'Senior Platform Engineer', 'duration' => '5 years'],
                            ['company' => 'Paytm', 'title' => 'Infrastructure Engineer', 'duration' => '2 years'],
                        ],
                        'education' => [['degree' => 'B.Tech IT', 'institution' => 'DTU Delhi', 'year' => '2017']],
                        'certifications' => ['CKS (Certified Kubernetes Security Specialist)', 'HashiCorp Vault Associate', 'AWS DevOps Engineer – Professional'],
                    ],
                ],
                'created_at' => $now->subDays(5),
            ],
            // ── Product Managers ──────────────────────────────────────────
            [
                'first_name'      => 'Shreya',
                'last_name'       => 'Kapoor',
                'email'           => 'shreya.kapoor.pm@gmail.com',
                'phone'           => '+91-9900112233',
                'current_company' => 'Groww',
                'current_title'   => 'Product Manager',
                'experience_years'=> 5.0,
                'skills'          => ['Product Management', 'User Research', 'Data Analysis', 'Roadmap Planning', 'Agile/Scrum', 'SQL', 'Figma'],
                'source'          => 'direct',
                'resume'          => [
                    'text' => $this->resumeText('Shreya Kapoor', 'Product Manager', 'Groww', 5.0,
                        "Product Manager at Groww owning the mutual funds discovery and onboarding flow (3M+ MAU). Ran 40+ user interviews that directly informed UX redesign, lifting KYC completion by 18%. Wrote PRDs for 12 shipped features. Partner closely with data science on recommendation algorithms. Strong SQL skills — self-serve analytics daily.",
                        ['Product Management', 'User Research', 'SQL', 'Figma', 'Amplitude', 'Jira', 'A/B Testing', 'Agile/Scrum'],
                        [['company'=>'Groww','title'=>'Product Manager','years'=>'2021–Present'],['company'=>'Urban Company','title'=>'Associate PM','years'=>'2019–2021']],
                        [['degree'=>'MBA','school'=>'IIM Ahmedabad','year'=>'2019'],['degree'=>'B.Tech CSE','school'=>'IIT Kharagpur','year'=>'2017']]
                    ),
                    'parsed' => [
                        'skills' => ['Product Management', 'User Research', 'SQL', 'Figma', 'Amplitude', 'Jira', 'A/B Testing', 'Agile/Scrum'],
                        'experience' => [
                            ['company' => 'Groww', 'title' => 'Product Manager', 'duration' => '3 years'],
                            ['company' => 'Urban Company', 'title' => 'Associate PM', 'duration' => '2 years'],
                        ],
                        'education' => [
                            ['degree' => 'MBA', 'institution' => 'IIM Ahmedabad', 'year' => '2019'],
                            ['degree' => 'B.Tech CSE', 'institution' => 'IIT Kharagpur', 'year' => '2017'],
                        ],
                        'certifications' => ['Certified Scrum Product Owner (CSPO)'],
                    ],
                ],
                'created_at' => $now->subDays(4),
            ],
            [
                'first_name'      => 'Mohit',
                'last_name'       => 'Taneja',
                'email'           => 'mohit.taneja.product@gmail.com',
                'phone'           => '+91-9776655443',
                'current_company' => 'Leadsquared',
                'current_title'   => 'Senior Product Manager',
                'experience_years'=> 7.0,
                'skills'          => ['Product Management', 'HRTech', 'Data Analysis', 'Roadmap Planning', 'Agile/Scrum', 'SQL', 'A/B Testing'],
                'source'          => 'upload',
                'resume'          => [
                    'text' => $this->resumeText('Mohit Taneja', 'Senior Product Manager', 'Leadsquared', 7.0,
                        "Senior PM at Leadsquared driving the HRTech vertical — ATS, onboarding automation, and HRIS integrations. Deep domain expertise in HR workflows and compliance. Managed roadmap for 4 products simultaneously. Launched AI-based candidate ranking feature that reduced time-to-shortlist by 35%. 3 years before Leadsquared at Keka HR as core PM.",
                        ['Product Management', 'HRTech', 'SQL', 'Jira', 'Amplitude', 'A/B Testing', 'User Research', 'Agile/Scrum', 'Roadmap Planning'],
                        [['company'=>'Leadsquared','title'=>'Senior Product Manager','years'=>'2020–Present'],['company'=>'Keka HR','title'=>'Product Manager','years'=>'2017–2020']],
                        [['degree'=>'MBA','school'=>'XLRI Jamshedpur','year'=>'2017']]
                    ),
                    'parsed' => [
                        'skills' => ['Product Management', 'HRTech', 'SQL', 'Jira', 'Amplitude', 'A/B Testing', 'User Research', 'Agile/Scrum'],
                        'experience' => [
                            ['company' => 'Leadsquared', 'title' => 'Senior Product Manager', 'duration' => '4 years'],
                            ['company' => 'Keka HR', 'title' => 'Product Manager', 'duration' => '3 years'],
                        ],
                        'education' => [['degree' => 'MBA', 'institution' => 'XLRI Jamshedpur', 'year' => '2017']],
                        'certifications' => ['CSPO', 'SAFe Product Owner/Product Manager'],
                    ],
                ],
                'created_at' => $now->subDays(3),
            ],
            // ── QA Engineers ──────────────────────────────────────────────
            [
                'first_name'      => 'Kavya',
                'last_name'       => 'Shetty',
                'email'           => 'kavya.shetty.qa@gmail.com',
                'phone'           => '+91-9812233441',
                'current_company' => 'PhonePe',
                'current_title'   => 'QA Engineer',
                'experience_years'=> 3.0,
                'skills'          => ['Playwright', 'Python', 'API Testing', 'Postman', 'SQL', 'CI/CD', 'Selenium'],
                'source'          => 'upload',
                'resume'          => [
                    'text' => $this->resumeText('Kavya Shetty', 'QA Engineer', 'PhonePe', 3.0,
                        "QA Engineer at PhonePe owning automation for the payments SDK and web dashboard. Built Playwright suite with 400+ e2e tests integrated into GitHub Actions, catching 15+ regressions before prod. Automated API contract testing using pytest+requests. Wrote SQL-based data validation scripts for post-deployment sanity checks.",
                        ['Playwright', 'Python', 'API Testing', 'Postman', 'SQL', 'GitHub Actions', 'Selenium', 'pytest'],
                        [['company'=>'PhonePe','title'=>'QA Engineer','years'=>'2021–Present'],['company'=>'Infosys','title'=>'Associate QA','years'=>'2020–2021']],
                        [['degree'=>'B.E. Computer Science','school'=>'Manipal Institute of Technology','year'=>'2020']]
                    ),
                    'parsed' => [
                        'skills' => ['Playwright', 'Python', 'API Testing', 'Postman', 'SQL', 'GitHub Actions', 'Selenium', 'pytest'],
                        'experience' => [
                            ['company' => 'PhonePe', 'title' => 'QA Engineer', 'duration' => '3 years'],
                            ['company' => 'Infosys', 'title' => 'Associate QA', 'duration' => '1 year'],
                        ],
                        'education' => [['degree' => 'B.E. Computer Science', 'institution' => 'Manipal Institute of Technology', 'year' => '2020']],
                        'certifications' => ['ISTQB Foundation Level'],
                    ],
                ],
                'created_at' => $now->subDays(20),
            ],
            // ── Additional cross-role candidates ─────────────────────────
            [
                'first_name'      => 'Aditya',
                'last_name'       => 'Singh',
                'email'           => 'aditya.singh.backend@gmail.com',
                'phone'           => '+91-9845009876',
                'current_company' => 'InMobi',
                'current_title'   => 'Backend Engineer',
                'experience_years'=> 3.0,
                'skills'          => ['Python', 'FastAPI', 'MySQL', 'Redis', 'Docker', 'Celery'],
                'source'          => 'upload',
                'resume'          => [
                    'text' => $this->resumeText('Aditya Singh', 'Backend Engineer', 'InMobi', 3.0,
                        "Built ad-serving API endpoints at InMobi processing 500K requests/min. Implemented Celery-based async pipeline for report generation. Optimised MySQL query performance — reduced page load time by 35% for reporting dashboards. Good fundamentals in REST API design and Docker-based deployments.",
                        ['Python', 'FastAPI', 'MySQL', 'Redis', 'Docker', 'Celery', 'pytest'],
                        [['company'=>'InMobi','title'=>'Backend Engineer','years'=>'2021–Present'],['company'=>'Mphasis','title'=>'Junior Developer','years'=>'2020–2021']],
                        [['degree'=>'B.Tech CSE','school'=>'SRM University','year'=>'2020']]
                    ),
                    'parsed' => [
                        'skills' => ['Python', 'FastAPI', 'MySQL', 'Redis', 'Docker', 'Celery', 'pytest'],
                        'experience' => [
                            ['company' => 'InMobi', 'title' => 'Backend Engineer', 'duration' => '3 years'],
                            ['company' => 'Mphasis', 'title' => 'Junior Developer', 'duration' => '1 year'],
                        ],
                        'education' => [['degree' => 'B.Tech CSE', 'institution' => 'SRM University', 'year' => '2020']],
                        'certifications' => [],
                    ],
                ],
                'created_at' => $now->subDays(14),
            ],
            [
                'first_name'      => 'Pooja',
                'last_name'       => 'Iyer',
                'email'           => 'pooja.iyer.fullstack@gmail.com',
                'phone'           => '+91-9556677889',
                'current_company' => 'ShareChat',
                'current_title'   => 'Full Stack Engineer',
                'experience_years'=> 4.5,
                'skills'          => ['React', 'TypeScript', 'Node.js', 'Python', 'PostgreSQL', 'Docker', 'AWS'],
                'source'          => 'direct',
                'resume'          => [
                    'text' => $this->resumeText('Pooja Iyer', 'Full Stack Engineer', 'ShareChat', 4.5,
                        "Full Stack Engineer at ShareChat building creator monetisation tools. Worked on both React frontend and Python/Node.js backend services. Experience with AWS deployments, PostgreSQL, and Docker. Strong understanding of REST API design and component-driven frontend development.",
                        ['React', 'TypeScript', 'Node.js', 'Python', 'PostgreSQL', 'Docker', 'AWS', 'Jest'],
                        [['company'=>'ShareChat','title'=>'Full Stack Engineer','years'=>'2021–Present'],['company'=>'Accenture','title'=>'Application Developer','years'=>'2019–2021']],
                        [['degree'=>'B.E. IT','school'=>'Anna University','year'=>'2019']]
                    ),
                    'parsed' => [
                        'skills' => ['React', 'TypeScript', 'Node.js', 'Python', 'PostgreSQL', 'Docker', 'AWS', 'Jest'],
                        'experience' => [
                            ['company' => 'ShareChat', 'title' => 'Full Stack Engineer', 'duration' => '3.5 years'],
                            ['company' => 'Accenture', 'title' => 'Application Developer', 'duration' => '2 years'],
                        ],
                        'education' => [['degree' => 'B.E. IT', 'institution' => 'Anna University', 'year' => '2019']],
                        'certifications' => [],
                    ],
                ],
                'created_at' => $now->subDays(16),
            ],
            [
                'first_name'      => 'Raj',
                'last_name'       => 'Kulkarni',
                'email'           => 'raj.kulkarni.de@gmail.com',
                'phone'           => '+91-9712233445',
                'current_company' => 'Navi Technologies',
                'current_title'   => 'Data Engineer',
                'experience_years'=> 4.0,
                'skills'          => ['Python', 'Spark', 'Airflow', 'AWS', 'PostgreSQL', 'dbt', 'Kafka'],
                'source'          => 'upload',
                'resume'          => [
                    'text' => $this->resumeText('Raj Kulkarni', 'Data Engineer', 'Navi Technologies', 4.0,
                        "Data Engineer at Navi building the financial data platform. Designed Airflow DAGs for nightly ETL pipelines processing 50GB+ daily. Built Spark-based feature store for credit-scoring ML models. Implemented CDC pipeline using Kafka for real-time loan data streaming. Experience with dbt for transformation layer and data quality testing.",
                        ['Python', 'Apache Spark', 'Airflow', 'AWS S3', 'PostgreSQL', 'dbt', 'Kafka', 'Redshift'],
                        [['company'=>'Navi Technologies','title'=>'Data Engineer','years'=>'2021–Present'],['company'=>'Mu Sigma','title'=>'Business Analyst','years'=>'2019–2021']],
                        [['degree'=>'B.Tech CSE','school'=>'NITK Surathkal','year'=>'2019']]
                    ),
                    'parsed' => [
                        'skills' => ['Python', 'Apache Spark', 'Airflow', 'AWS S3', 'PostgreSQL', 'dbt', 'Kafka', 'Redshift'],
                        'experience' => [
                            ['company' => 'Navi Technologies', 'title' => 'Data Engineer', 'duration' => '3 years'],
                            ['company' => 'Mu Sigma', 'title' => 'Business Analyst', 'duration' => '2 years'],
                        ],
                        'education' => [['degree' => 'B.Tech CSE', 'institution' => 'NITK Surathkal', 'year' => '2019']],
                        'certifications' => ['dbt Analytics Engineering Certification'],
                    ],
                ],
                'created_at' => $now->subDays(10),
            ],
            [
                'first_name'      => 'Harini',
                'last_name'       => 'Chandrasekaran',
                'email'           => 'harini.cs.ml@gmail.com',
                'phone'           => '+91-9543211234',
                'current_company' => 'Bangalore Labs',
                'current_title'   => 'Research Engineer – NLP',
                'experience_years'=> 2.5,
                'skills'          => ['Python', 'NLP', 'Transformers', 'HuggingFace', 'PyTorch', 'LLM APIs', 'Prompt Engineering'],
                'source'          => 'direct',
                'resume'          => [
                    'text' => $this->resumeText('Harini Chandrasekaran', 'Research Engineer – NLP', 'Bangalore Labs', 2.5,
                        "NLP Research Engineer building custom text classifiers and information extraction pipelines. Fine-tuned BERT variants for named entity recognition achieving 91% F1 on custom datasets. Experimented with GPT-4 and Claude APIs for document summarisation. Strong academic background — published 1 paper at EMNLP 2023 on domain-adaptive NLP.",
                        ['Python', 'NLP', 'Transformers', 'HuggingFace', 'PyTorch', 'LLM APIs', 'Prompt Engineering', 'spaCy', 'NLTK'],
                        [['company'=>'Bangalore Labs','title'=>'Research Engineer – NLP','years'=>'2022–Present'],['company'=>'IISc','title'=>'Research Assistant','years'=>'2021–2022']],
                        [['degree'=>'M.Sc Cognitive Science (NLP Track)','school'=>'IISc Bangalore','year'=>'2021']]
                    ),
                    'parsed' => [
                        'skills' => ['Python', 'NLP', 'Transformers', 'HuggingFace', 'PyTorch', 'LLM APIs', 'Prompt Engineering', 'spaCy'],
                        'experience' => [
                            ['company' => 'Bangalore Labs', 'title' => 'Research Engineer – NLP', 'duration' => '2 years'],
                            ['company' => 'IISc', 'title' => 'Research Assistant', 'duration' => '1 year'],
                        ],
                        'education' => [['degree' => 'M.Sc Cognitive Science (NLP Track)', 'institution' => 'IISc Bangalore', 'year' => '2021']],
                        'certifications' => ['Deeplearning.ai NLP Specialisation'],
                    ],
                ],
                'created_at' => $now->subDays(7),
            ],
            [
                'first_name'      => 'Sameer',
                'last_name'       => 'Ghosh',
                'email'           => 'sameer.ghosh.devops@outlook.com',
                'phone'           => '+91-9812399988',
                'current_company' => 'MakeMyTrip',
                'current_title'   => 'Cloud Infrastructure Engineer',
                'experience_years'=> 5.0,
                'skills'          => ['AWS', 'Terraform', 'Docker', 'Kubernetes', 'Python', 'Linux', 'CI/CD', 'Ansible'],
                'source'          => 'upload',
                'resume'          => [
                    'text' => $this->resumeText('Sameer Ghosh', 'Cloud Infrastructure Engineer', 'MakeMyTrip', 5.0,
                        "Cloud Infrastructure Engineer at MakeMyTrip managing multi-region AWS setup serving 5M+ monthly bookings. Automated 80% of infrastructure provisioning with Terraform. Built blue-green deployment pipelines reducing deployment risk. Implemented Ansible for configuration management across 200+ EC2 instances. Strong Linux ops background.",
                        ['AWS', 'Terraform', 'Docker', 'Kubernetes', 'Python', 'Linux', 'GitHub Actions', 'Ansible', 'Grafana'],
                        [['company'=>'MakeMyTrip','title'=>'Cloud Infrastructure Engineer','years'=>'2021–Present'],['company'=>'HCL Technologies','title'=>'Systems Engineer','years'=>'2019–2021']],
                        [['degree'=>'B.Tech IT','school'=>'Jadavpur University','year'=>'2019']]
                    ),
                    'parsed' => [
                        'skills' => ['AWS', 'Terraform', 'Docker', 'Kubernetes', 'Python', 'Linux', 'GitHub Actions', 'Ansible'],
                        'experience' => [
                            ['company' => 'MakeMyTrip', 'title' => 'Cloud Infrastructure Engineer', 'duration' => '3 years'],
                            ['company' => 'HCL Technologies', 'title' => 'Systems Engineer', 'duration' => '2 years'],
                        ],
                        'education' => [['degree' => 'B.Tech IT', 'institution' => 'Jadavpur University', 'year' => '2019']],
                        'certifications' => ['AWS Solutions Architect – Associate', 'HashiCorp Terraform Associate'],
                    ],
                ],
                'created_at' => $now->subDays(4),
            ],
            [
                'first_name'      => 'Lakshmi',
                'last_name'       => 'Narayanan',
                'email'           => 'lakshmi.n.frontend@gmail.com',
                'phone'           => '+91-9445321654',
                'current_company' => 'Freshworks',
                'current_title'   => 'UI Engineer',
                'experience_years'=> 2.5,
                'skills'          => ['React', 'JavaScript', 'CSS', 'HTML', 'REST API Integration', 'Jest'],
                'source'          => 'direct',
                'resume'          => [
                    'text' => $this->resumeText('Lakshmi Narayanan', 'UI Engineer', 'Freshworks', 2.5,
                        "UI Engineer at Freshworks working on the Freshdesk support platform. Built customer-facing React components used by 50K+ support agents. Contributed to design system migration from older CSS framework to custom design tokens. Experience with REST API integration and writing Jest unit tests.",
                        ['React', 'JavaScript', 'CSS', 'HTML', 'REST API Integration', 'Jest', 'Bootstrap'],
                        [['company'=>'Freshworks','title'=>'UI Engineer','years'=>'2022–Present'],['company'=>'Microland','title'=>'Junior Developer','years'=>'2021–2022']],
                        [['degree'=>'B.Sc IT','school'=>'PSG College of Technology','year'=>'2021']]
                    ),
                    'parsed' => [
                        'skills' => ['React', 'JavaScript', 'CSS', 'HTML', 'REST API Integration', 'Jest', 'Bootstrap'],
                        'experience' => [
                            ['company' => 'Freshworks', 'title' => 'UI Engineer', 'duration' => '2 years'],
                            ['company' => 'Microland', 'title' => 'Junior Developer', 'duration' => '1 year'],
                        ],
                        'education' => [['degree' => 'B.Sc IT', 'institution' => 'PSG College of Technology', 'year' => '2021']],
                        'certifications' => [],
                    ],
                ],
                'created_at' => $now->subDays(9),
            ],
        ];

        foreach ($profiles as $profile) {
            $resumeData = $profile['resume'];
            unset($profile['resume']);

            $profile['organization_id'] = self::ORG_ID;
            $candidate = Candidate::create($profile);

            Resume::create([
                'candidate_id'   => $candidate->id,
                'file_path'      => "resumes/nalam/{$candidate->id}/{$candidate->first_name}_{$candidate->last_name}_resume.pdf",
                'file_name'      => "{$candidate->first_name}_{$candidate->last_name}_resume.pdf",
                'file_type'      => 'pdf',
                'extracted_text' => $resumeData['text'],
                'parsed_data'    => $resumeData['parsed'],
                'uploaded_by'    => self::HR_USER,
                'created_at'     => $profile['created_at'],
            ]);

            $candidates[$candidate->email] = $candidate;
        }

        return $candidates;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // APPLICATIONS
    // ──────────────────────────────────────────────────────────────────────────

    private function createApplications(array $jobs, array $candidates): void
    {
        /** @var JobPosting[] $jobMap */
        $jobMap = [];
        foreach ($jobs as $job) {
            $jobMap[$job->title] = $job;
        }

        $backendJob   = $jobMap['Senior Backend Engineer (Python / FastAPI)'];
        $frontendJob  = $jobMap['Frontend Engineer (React / TypeScript)'];
        $mlJob        = $jobMap['ML / AI Engineer'];
        $devopsJob    = $jobMap['DevOps / Platform Engineer'];
        $pmJob        = $jobMap['Product Manager — Talent Intelligence'];
        $qaJob        = $jobMap['QA Engineer (Automation)'];

        // Each application entry: [candidate_email, job, stage, ai_score, apply_offset_days, notes]
        $now = Carbon::now();

        $applications = [
            // ── Backend Role ──────────────────────────────────────────────────
            [
                'candidate' => $candidates['karan.mehta@gmail.com'],
                'job'       => $backendJob,
                'stage'     => 'offer',
                'ai_score'  => 91.5,
                'ai_analysis' => [
                    'match_percentage'  => 91,
                    'strengths'         => ['Python', 'FastAPI', 'PostgreSQL', 'Redis', 'Docker', 'AWS'],
                    'gaps'              => ['Kubernetes'],
                    'recommendation'    => 'strong_yes',
                    'reasoning'         => 'Exceptional match. 5+ years with FastAPI and PostgreSQL, demonstrated ownership of high-throughput services at Razorpay. Redis expertise and AWS certification are strong positives. Only gap is Kubernetes — readily closeable.',
                ],
                'ai_signals' => ['skill_match' => 0.94, 'experience_match' => 0.92, 'location_fit' => 1.0, 'salary_alignment' => 0.88],
                'applied_at' => $now->copy()->subDays(16),
                'feedback' => [
                    ['stage' => 'hr_screening', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Excellent communication, strong ownership mindset, referrals from Razorpay are glowing.',
                     'weaknesses' => 'Slightly above salary band — needs negotiation.'],
                    ['stage' => 'technical_round_1', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Solved distributed systems design problem elegantly. Deep understanding of async patterns.',
                     'weaknesses' => 'One edge case missed in the coding section — self-corrected quickly.'],
                    ['stage' => 'technical_round_2', 'rating' => 4, 'recommendation' => 'yes',
                     'strengths' => 'System design for rate limiter was textbook. Clear and precise.',
                     'weaknesses' => 'Could improve on test coverage strategies for async code.'],
                ],
            ],
            [
                'candidate' => $candidates['priya.sharma.dev@outlook.com'],
                'job'       => $backendJob,
                'stage'     => 'technical_round_1',
                'ai_score'  => 79.0,
                'ai_analysis' => [
                    'match_percentage'  => 79,
                    'strengths'         => ['Python', 'FastAPI', 'Kafka', 'Docker', 'PostgreSQL'],
                    'gaps'              => ['Redis advanced patterns', 'AWS Lambda'],
                    'recommendation'    => 'yes',
                    'reasoning'         => 'Strong backend profile with Kafka and PostgreSQL optimisation experience. Kafka pipeline knowledge is a bonus. Minor gaps in serverless and Redis depth.',
                ],
                'ai_signals' => ['skill_match' => 0.81, 'experience_match' => 0.78, 'location_fit' => 1.0, 'salary_alignment' => 0.95],
                'applied_at' => $now->copy()->subDays(14),
                'feedback' => [
                    ['stage' => 'hr_screening', 'rating' => 4, 'recommendation' => 'yes',
                     'strengths' => 'Articulate, knows what she wants. Event-driven architecture knowledge is impressive.',
                     'weaknesses' => 'May need ramp-up on AWS services.'],
                ],
            ],
            [
                'candidate' => $candidates['arjun.nair.eng@gmail.com'],
                'job'       => $backendJob,
                'stage'     => 'hired',
                'ai_score'  => 96.0,
                'ai_analysis' => [
                    'match_percentage'  => 96,
                    'strengths'         => ['Python', 'Go', 'FastAPI', 'PostgreSQL', 'Kubernetes', 'AWS', 'gRPC', 'Terraform'],
                    'gaps'              => [],
                    'recommendation'    => 'strong_yes',
                    'reasoning'         => 'Outstanding match. Staff-level experience with gRPC service mesh design and EKS migrations maps directly to our needs. Leadership in backend standards and mentorship is exactly what the team needs.',
                ],
                'ai_signals' => ['skill_match' => 0.98, 'experience_match' => 0.96, 'location_fit' => 1.0, 'salary_alignment' => 0.80],
                'applied_at' => $now->copy()->subDays(12),
                'feedback' => [
                    ['stage' => 'hr_screening', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Impressive depth. Already leading at staff level. Very clear on career goals.',
                     'weaknesses' => 'Salary expectations are at the top of band.'],
                    ['stage' => 'technical_round_1', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Live coding: built a concurrency-safe rate limiter in Python without hints. Exceptional.',
                     'weaknesses' => 'None — benchmark performance.'],
                    ['stage' => 'technical_round_2', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'System design: event-sourced order system design was production-quality. All edge cases covered.',
                     'weaknesses' => 'None significant.'],
                ],
            ],
            [
                'candidate' => $candidates['aditya.singh.backend@gmail.com'],
                'job'       => $backendJob,
                'stage'     => 'rejected',
                'ai_score'  => 58.0,
                'ai_analysis' => [
                    'match_percentage'  => 58,
                    'strengths'         => ['Python', 'FastAPI', 'Docker', 'Celery'],
                    'gaps'              => ['PostgreSQL advanced', 'Redis', 'AWS', 'System design at scale'],
                    'recommendation'    => 'neutral',
                    'reasoning'         => 'Has the basics but lacks depth for a Senior role. Experience is more junior-level — ad-serving APIs are less complex than payment gateway microservices. Redis and AWS knowledge is minimal.',
                ],
                'ai_signals' => ['skill_match' => 0.60, 'experience_match' => 0.55, 'location_fit' => 1.0, 'salary_alignment' => 1.0],
                'applied_at' => $now->copy()->subDays(13),
                'rejection_reason' => 'Does not meet seniority bar for this role. Recommend revisiting in 2 years or considering for a mid-level position if one opens.',
            ],
            // ── Frontend Role ─────────────────────────────────────────────────
            [
                'candidate' => $candidates['divya.krishnan.ui@gmail.com'],
                'job'       => $frontendJob,
                'stage'     => 'technical_round_2',
                'ai_score'  => 87.0,
                'ai_analysis' => [
                    'match_percentage'  => 87,
                    'strengths'         => ['React', 'TypeScript', 'CSS', 'Storybook', 'Jest', 'Figma', 'Playwright'],
                    'gaps'              => ['Next.js', 'GraphQL production experience'],
                    'recommendation'    => 'strong_yes',
                    'reasoning'         => 'Very strong frontend profile. Storybook library maintenance and TypeScript migration are directly relevant. Performance improvements (4.2s → 1.8s FCP) show real-world impact. Minor gaps in Next.js and GraphQL are easily learnable.',
                ],
                'ai_signals' => ['skill_match' => 0.89, 'experience_match' => 0.85, 'location_fit' => 1.0, 'salary_alignment' => 0.92],
                'applied_at' => $now->copy()->subDays(11),
                'feedback' => [
                    ['stage' => 'hr_screening', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Portfolio is excellent — components are clean and accessible. Great communicator.',
                     'weaknesses' => 'Limited Next.js experience, but eager to learn.'],
                    ['stage' => 'technical_round_1', 'rating' => 4, 'recommendation' => 'yes',
                     'strengths' => 'Built a multi-step form with TypeScript generics live — clean implementation.',
                     'weaknesses' => 'Accessibility attrs were added only after prompt. Should be second nature.'],
                ],
            ],
            [
                'candidate' => $candidates['nikhil.joshi.fe@yahoo.com'],
                'job'       => $frontendJob,
                'stage'     => 'offer',
                'ai_score'  => 93.0,
                'ai_analysis' => [
                    'match_percentage'  => 93,
                    'strengths'         => ['React', 'Next.js', 'TypeScript', 'Playwright', 'Storybook', 'GraphQL', 'Performance optimisation'],
                    'gaps'              => [],
                    'recommendation'    => 'strong_yes',
                    'reasoning'         => 'Exceptional match. SSR expertise with Next.js and Playwright test suite ownership are exactly what we need. IIT background and 5 years at scale. No meaningful gaps.',
                ],
                'ai_signals' => ['skill_match' => 0.95, 'experience_match' => 0.92, 'location_fit' => 1.0, 'salary_alignment' => 0.85],
                'applied_at' => $now->copy()->subDays(10),
                'feedback' => [
                    ['stage' => 'hr_screening', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Deep understanding of React internals. Talks in terms of business outcomes, not just code.',
                     'weaknesses' => 'None noted.'],
                    ['stage' => 'technical_round_1', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Implemented virtual scroll from scratch in 45 minutes. Explained trade-offs clearly.',
                     'weaknesses' => 'None.'],
                    ['stage' => 'technical_round_2', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Frontend system design was comprehensive — covered CDN, bundling, accessibility and i18n.',
                     'weaknesses' => 'None.'],
                ],
            ],
            [
                'candidate' => $candidates['lakshmi.n.frontend@gmail.com'],
                'job'       => $frontendJob,
                'stage'     => 'ai_shortlisted',
                'ai_score'  => 62.0,
                'ai_analysis' => [
                    'match_percentage'  => 62,
                    'strengths'         => ['React', 'CSS', 'Jest', 'REST API Integration'],
                    'gaps'              => ['TypeScript', 'Next.js', 'Playwright', 'Storybook', 'Performance optimisation'],
                    'recommendation'    => 'neutral',
                    'reasoning'         => 'Solid fundamentals but lacks TypeScript and several required tools. 2.5 years experience may be short for this role. Could be a good fit in 12-18 months.',
                ],
                'ai_signals' => ['skill_match' => 0.63, 'experience_match' => 0.58, 'location_fit' => 1.0, 'salary_alignment' => 1.0],
                'applied_at' => $now->copy()->subDays(8),
            ],
            [
                'candidate' => $candidates['pooja.iyer.fullstack@gmail.com'],
                'job'       => $frontendJob,
                'stage'     => 'hr_screening',
                'ai_score'  => 72.0,
                'ai_analysis' => [
                    'match_percentage'  => 72,
                    'strengths'         => ['React', 'TypeScript', 'CSS', 'Jest', 'REST API Integration'],
                    'gaps'              => ['Next.js', 'Playwright', 'Storybook'],
                    'recommendation'    => 'yes',
                    'reasoning'         => 'Good frontend skills with TypeScript. Full-stack background is a bonus. Missing SSR and e2e testing experience.',
                ],
                'ai_signals' => ['skill_match' => 0.74, 'experience_match' => 0.72, 'location_fit' => 1.0, 'salary_alignment' => 0.93],
                'applied_at' => $now->copy()->subDays(9),
                'feedback' => [],
            ],
            // ── ML / AI Role ──────────────────────────────────────────────────
            [
                'candidate' => $candidates['ritu.agarwal.ml@gmail.com'],
                'job'       => $mlJob,
                'stage'     => 'technical_round_1',
                'ai_score'  => 88.5,
                'ai_analysis' => [
                    'match_percentage'  => 88,
                    'strengths'         => ['Python', 'LLM APIs', 'Prompt Engineering', 'NLP', 'FastAPI', 'LangChain', 'Vector Databases'],
                    'gaps'              => ['PyTorch fine-tuning', 'MLflow in production'],
                    'recommendation'    => 'strong_yes',
                    'reasoning'         => 'Outstanding profile for this role. RAG system and prompt evaluation framework at Sarvam AI are directly relevant. LangChain + ChromaDB experience is exactly the stack we use.',
                ],
                'ai_signals' => ['skill_match' => 0.91, 'experience_match' => 0.85, 'location_fit' => 1.0, 'salary_alignment' => 0.90],
                'applied_at' => $now->copy()->subDays(8),
                'feedback' => [
                    ['stage' => 'hr_screening', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Published NLP researcher with hands-on production LLM experience — rare combination.',
                     'weaknesses' => 'Fewer years overall, but quality of work compensates.'],
                ],
            ],
            [
                'candidate' => $candidates['vikram.bose.ai@protonmail.com'],
                'job'       => $mlJob,
                'stage'     => 'hr_screening',
                'ai_score'  => 81.0,
                'ai_analysis' => [
                    'match_percentage'  => 81,
                    'strengths'         => ['Python', 'NLP', 'scikit-learn', 'PyTorch', 'MLflow', 'FastAPI', 'Spark'],
                    'gaps'              => ['LLM APIs production experience', 'Prompt Engineering at scale', 'LangChain'],
                    'recommendation'    => 'yes',
                    'reasoning'         => 'Strong classical ML and MLOps background. Good for model serving and experiment tracking. Needs upskilling on LLM-specific tooling but the fundamentals are excellent.',
                ],
                'ai_signals' => ['skill_match' => 0.82, 'experience_match' => 0.85, 'location_fit' => 1.0, 'salary_alignment' => 0.82],
                'applied_at' => $now->copy()->subDays(7),
                'feedback' => [],
            ],
            [
                'candidate' => $candidates['harini.cs.ml@gmail.com'],
                'job'       => $mlJob,
                'stage'     => 'applied',
                'ai_score'  => 76.0,
                'ai_analysis' => [
                    'match_percentage'  => 76,
                    'strengths'         => ['Python', 'NLP', 'Transformers', 'HuggingFace', 'LLM APIs', 'Prompt Engineering', 'PyTorch'],
                    'gaps'              => ['FastAPI production serving', 'MLflow', 'Team leadership'],
                    'recommendation'    => 'yes',
                    'reasoning'         => 'Strong NLP research profile — EMNLP publication is impressive. Lacks production ML serving experience but the research depth on Transformers and LLMs is directly applicable.',
                ],
                'ai_signals' => ['skill_match' => 0.80, 'experience_match' => 0.68, 'location_fit' => 1.0, 'salary_alignment' => 1.0],
                'applied_at' => $now->copy()->subDays(6),
            ],
            // ── DevOps Role ───────────────────────────────────────────────────
            [
                'candidate' => $candidates['sneha.reddy.devops@gmail.com'],
                'job'       => $devopsJob,
                'stage'     => 'technical_round_2',
                'ai_score'  => 85.0,
                'ai_analysis' => [
                    'match_percentage'  => 85,
                    'strengths'         => ['Docker', 'Kubernetes', 'Terraform', 'AWS', 'GitHub Actions', 'Helm', 'Prometheus'],
                    'gaps'              => ['Vault', 'Istio', 'Multi-cloud'],
                    'recommendation'    => 'strong_yes',
                    'reasoning'         => 'Solid match. EKS experience and cost reduction track record are directly relevant. CKA certification validates Kubernetes depth. Gaps in Vault and Istio are learnable.',
                ],
                'ai_signals' => ['skill_match' => 0.87, 'experience_match' => 0.82, 'location_fit' => 1.0, 'salary_alignment' => 0.93],
                'applied_at' => $now->copy()->subDays(5),
                'feedback' => [
                    ['stage' => 'hr_screening', 'rating' => 4, 'recommendation' => 'yes',
                     'strengths' => 'CKA certified, well-rounded AWS experience, Prometheus expertise is a bonus.',
                     'weaknesses' => 'Dunzo is a smaller scale than we need. Will assess depth in technical round.'],
                    ['stage' => 'technical_round_1', 'rating' => 4, 'recommendation' => 'yes',
                     'strengths' => 'Wrote a clean Terraform module for EKS live. Good understanding of IAM trust policies.',
                     'weaknesses' => 'Didn\'t mention pod disruption budgets for zero-downtime deployments unprompted.'],
                ],
            ],
            [
                'candidate' => $candidates['rohan.verma.ops@gmail.com'],
                'job'       => $devopsJob,
                'stage'     => 'offer',
                'ai_score'  => 97.0,
                'ai_analysis' => [
                    'match_percentage'  => 97,
                    'strengths'         => ['Kubernetes', 'Terraform', 'AWS', 'GCP', 'Istio', 'Vault', 'CI/CD', 'Python', 'Linux'],
                    'gaps'              => [],
                    'recommendation'    => 'strong_yes',
                    'reasoning'         => 'Perfect match. Multi-cloud EKS+GKE, Istio service mesh, Vault secrets management, and Backstage developer portal are all exactly what we\'re building. MTTR improvement from 45→12 min is impressive. CKS certification shows security depth.',
                ],
                'ai_signals' => ['skill_match' => 0.98, 'experience_match' => 0.97, 'location_fit' => 1.0, 'salary_alignment' => 0.82],
                'applied_at' => $now->copy()->subDays(4),
                'feedback' => [
                    ['stage' => 'hr_screening', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Exactly what we need. Multi-cloud, service mesh, secrets management — ticks every box.',
                     'weaknesses' => 'Salary is at top of band. Budget approval needed.'],
                    ['stage' => 'technical_round_1', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Live Terraform: provisioned an EKS cluster with IRSA, Helm releases, and ArgoCD in 40 minutes. Exceptional.',
                     'weaknesses' => 'None.'],
                    ['stage' => 'technical_round_2', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Incident scenario handled systematically — runbook creation, blast radius analysis, rollback strategy all discussed.',
                     'weaknesses' => 'None.'],
                ],
            ],
            [
                'candidate' => $candidates['sameer.ghosh.devops@outlook.com'],
                'job'       => $devopsJob,
                'stage'     => 'ai_shortlisted',
                'ai_score'  => 74.0,
                'ai_analysis' => [
                    'match_percentage'  => 74,
                    'strengths'         => ['AWS', 'Terraform', 'Docker', 'Kubernetes', 'GitHub Actions', 'Ansible', 'Linux'],
                    'gaps'              => ['Helm', 'ArgoCD', 'Vault', 'Istio', 'Prometheus deep expertise'],
                    'recommendation'    => 'yes',
                    'reasoning'         => 'Good foundation but slightly below the depth we need for a Platform Engineer. Ansible and multi-region AWS are positives. Needs hands-on Helm and ArgoCD experience.',
                ],
                'ai_signals' => ['skill_match' => 0.75, 'experience_match' => 0.72, 'location_fit' => 1.0, 'salary_alignment' => 0.95],
                'applied_at' => $now->copy()->subDays(3),
            ],
            // ── PM Role ───────────────────────────────────────────────────────
            [
                'candidate' => $candidates['shreya.kapoor.pm@gmail.com'],
                'job'       => $pmJob,
                'stage'     => 'technical_round_1',
                'ai_score'  => 89.0,
                'ai_analysis' => [
                    'match_percentage'  => 89,
                    'strengths'         => ['Product Management', 'User Research', 'SQL', 'Data Analysis', 'Agile/Scrum', 'Figma', 'A/B Testing'],
                    'gaps'              => ['HRTech domain knowledge', 'AI/ML product ownership'],
                    'recommendation'    => 'strong_yes',
                    'reasoning'         => 'Excellent PM profile. 40+ user interviews influencing UX at Groww shows strong research rigour. SQL self-serve analytics is exactly what we need. Only gap is HRTech domain knowledge — but she\'s a quick learner.',
                ],
                'ai_signals' => ['skill_match' => 0.90, 'experience_match' => 0.88, 'location_fit' => 1.0, 'salary_alignment' => 0.88],
                'applied_at' => $now->copy()->subDays(3),
                'feedback' => [
                    ['stage' => 'hr_screening', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'IIM + IIT background, strong fintech PM experience, data-driven approach.',
                     'weaknesses' => 'No HRTech experience, but adaptable and fast learner.'],
                ],
            ],
            [
                'candidate' => $candidates['mohit.taneja.product@gmail.com'],
                'job'       => $pmJob,
                'stage'     => 'hr_screening',
                'ai_score'  => 94.0,
                'ai_analysis' => [
                    'match_percentage'  => 94,
                    'strengths'         => ['Product Management', 'HRTech', 'Data Analysis', 'Roadmap Planning', 'Agile/Scrum', 'A/B Testing', 'User Research'],
                    'gaps'              => [],
                    'recommendation'    => 'strong_yes',
                    'reasoning'         => 'Near-perfect match. 7 years in HRTech (Keka + Leadsquared) with direct ATS product ownership is uniquely relevant. AI candidate ranking feature launch with 35% time-to-shortlist improvement mirrors our exact product goals.',
                ],
                'ai_signals' => ['skill_match' => 0.96, 'experience_match' => 0.94, 'location_fit' => 1.0, 'salary_alignment' => 0.80],
                'applied_at' => $now->copy()->subDays(2),
                'feedback' => [],
            ],
            // ── QA Role ───────────────────────────────────────────────────────
            [
                'candidate' => $candidates['kavya.shetty.qa@gmail.com'],
                'job'       => $qaJob,
                'stage'     => 'offer',
                'ai_score'  => 90.0,
                'ai_analysis' => [
                    'match_percentage'  => 90,
                    'strengths'         => ['Playwright', 'Python', 'API Testing', 'SQL', 'GitHub Actions', 'pytest'],
                    'gaps'              => ['k6 load testing', 'Allure Reports'],
                    'recommendation'    => 'strong_yes',
                    'reasoning'         => 'Strong match. PhonePe QA experience with Playwright and API testing is directly applicable. 400+ e2e tests with CI integration is impressive for 3 years experience.',
                ],
                'ai_signals' => ['skill_match' => 0.92, 'experience_match' => 0.88, 'location_fit' => 1.0, 'salary_alignment' => 0.96],
                'applied_at' => $now->copy()->subDays(19),
                'feedback' => [
                    ['stage' => 'hr_screening', 'rating' => 5, 'recommendation' => 'strong_yes',
                     'strengths' => 'Confident with both UI automation and API testing. SQL validation scripts show initiative.',
                     'weaknesses' => 'Load testing experience limited. Can ramp up on k6.'],
                    ['stage' => 'technical_round_1', 'rating' => 4, 'recommendation' => 'yes',
                     'strengths' => 'Wrote Playwright test for a form with dynamic validation live — clean page object model.',
                     'weaknesses' => 'API mocking strategy was basic — used real endpoints instead of stubs.'],
                ],
            ],
            // ── Cross-role applications ───────────────────────────────────────
            [
                'candidate' => $candidates['raj.kulkarni.de@gmail.com'],
                'job'       => $backendJob,
                'stage'     => 'ai_shortlisted',
                'ai_score'  => 65.0,
                'ai_analysis' => [
                    'match_percentage'  => 65,
                    'strengths'         => ['Python', 'PostgreSQL', 'Kafka'],
                    'gaps'              => ['FastAPI', 'Docker', 'Redis', 'REST API Design'],
                    'recommendation'    => 'neutral',
                    'reasoning'         => 'Data Engineering background is adjacent but not directly aligned with backend API development. Python and SQL are shared strengths, but lacks FastAPI and service-oriented architecture experience.',
                ],
                'ai_signals' => ['skill_match' => 0.66, 'experience_match' => 0.64, 'location_fit' => 1.0, 'salary_alignment' => 0.95],
                'applied_at' => $now->copy()->subDays(9),
            ],
        ];

        foreach ($applications as $appData) {
            /** @var Candidate $candidate */
            $candidate = $appData['candidate'];
            /** @var JobPosting $job */
            $job = $appData['job'];

            $resume = Resume::where('candidate_id', $candidate->id)->first();

            $appCreate = [
                'job_posting_id'  => $job->id,
                'candidate_id'    => $candidate->id,
                'resume_id'       => $resume?->id,
                'stage'           => $appData['stage'],
                'ai_score'        => $appData['ai_score'],
                'ai_analysis'     => $appData['ai_analysis'] ?? null,
                'ai_signals'      => $appData['ai_signals'] ?? null,
                'ai_analyzed_at'  => $appData['applied_at']->copy()->addMinutes(rand(3, 15)),
                'applied_at'      => $appData['applied_at'],
                'rejection_reason'=> $appData['rejection_reason'] ?? null,
            ];

            $application = JobApplication::create($appCreate);

            // Interview feedback
            foreach ($appData['feedback'] ?? [] as $fb) {
                InterviewFeedback::create([
                    'job_application_id' => $application->id,
                    'interviewer_id'     => self::HR_USER,
                    'stage'              => $fb['stage'],
                    'rating'             => $fb['rating'],
                    'strengths'          => $fb['strengths'],
                    'weaknesses'         => $fb['weaknesses'],
                    'recommendation'     => $fb['recommendation'],
                    'notes'              => null,
                ]);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // RESUME TEXT GENERATOR
    // ──────────────────────────────────────────────────────────────────────────

    private function resumeText(
        string $name,
        string $title,
        string $company,
        float  $years,
        string $summary,
        array  $skills,
        array  $experience,
        array  $education
    ): string {
        $skillStr = implode(' · ', $skills);
        $expStr   = '';
        foreach ($experience as $e) {
            $expStr .= "\n{$e['title']} — {$e['company']}  ({$e['years']})\n";
        }
        $eduStr = '';
        foreach ($education as $e) {
            $eduStr .= "\n{$e['degree']}  |  {$e['school']}  ({$e['year']})\n";
        }

        return <<<RESUME
{$name}
{$title}  |  {$company}  |  {$years} years total experience
────────────────────────────────────────────────────────────────

PROFESSIONAL SUMMARY
{$summary}

────────────────────────────────────────────────────────────────
SKILLS
{$skillStr}

────────────────────────────────────────────────────────────────
EXPERIENCE
{$expStr}
────────────────────────────────────────────────────────────────
EDUCATION
{$eduStr}
RESUME;
    }
}
