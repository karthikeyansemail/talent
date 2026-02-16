# Talent Intelligence Platform - Full Specification

> **This is the single source of truth for the application's design.** Any AI assistant modifying this codebase MUST consult this document before making changes to business logic, database schema, API contracts, or user workflows. If a change conflicts with this spec, the conflict MUST be raised with the user. After approved changes, this document MUST be updated.

---

## 1. Product Overview

### 1.1 Purpose
A self-hosted and SaaS-ready AI platform for organizations to:
1. **Hire smarter** - AI-powered candidate screening, resume analysis, and hiring pipeline management
2. **Allocate resources intelligently** - AI-powered internal workforce matching using resume skills and Jira work signals

### 1.2 Architecture
```
Browser <-> Laravel (PHP 8.2) <-> MySQL/MariaDB
                |
                | REST API (HTTP)
                |
           FastAPI (Python 3.11+) <-> LLM Provider (OpenAI / Anthropic)
```

- **Laravel**: ALL business logic, authentication, authorization, UI rendering, data persistence, queue management
- **FastAPI**: ONLY AI/LLM processing - stateless, no database access, receives data and returns analysis
- **Communication**: Laravel dispatches queue jobs that call Python endpoints via HTTP
- **Frontend**: Server-rendered Blade templates with plain CSS and vanilla JavaScript (no build step)

### 1.3 Multi-Tenancy Model
- Every organization is isolated by `organization_id` foreign key on all relevant tables
- Users belong to exactly one organization
- All queries MUST be scoped to the authenticated user's organization
- `super_admin` role can access cross-organization data (future)

---

## 2. User Roles & Permissions

### 2.1 Role Hierarchy
| Role | Hiring | Resource Allocation | Settings | Dashboard |
|------|--------|-------------------|----------|-----------|
| `super_admin` | Full | Full | Full + cross-org | Full |
| `org_admin` | Full | Full | Full (own org) | Full |
| `hr_manager` | Full | No | No | Hiring stats |
| `hiring_manager` | Full | No | No | Hiring stats |
| `resource_manager` | No | Full | No | Resource stats |
| `employee` | No | View own profile | No | Personal |

### 2.2 Authentication
- Email + password login (Laravel built-in hashing)
- Registration creates a new organization + org_admin user
- Session-based auth with database session driver
- "Remember me" token support

---

## 3. Database Schema

### 3.1 Core Tables

#### organizations
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | Auto-increment |
| name | string | Organization name |
| slug | string unique | URL-safe identifier |
| domain | string nullable | Company domain |
| logo_path | string nullable | Path to logo file |
| settings | json nullable | Org-level settings |
| is_active | boolean | Default true |
| timestamps | | created_at, updated_at |

#### users
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| password | string | Hashed |
| role | enum | super_admin, org_admin, hr_manager, hiring_manager, resource_manager, employee |
| organization_id | FK organizations | nullable for super_admin |
| is_active | boolean | Default true |
| remember_token | string nullable | |
| timestamps | | |

#### departments
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| organization_id | FK organizations | |
| name | string | |
| description | text nullable | |
| timestamps | | |

### 3.2 Hiring / ATS Tables

#### job_postings
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| organization_id | FK organizations | |
| department_id | FK departments | nullable |
| title | string | |
| description | text | Full job description |
| requirements | text nullable | Formatted requirements |
| min_experience | integer | Default 0 |
| max_experience | integer | Default 10 |
| required_skills | json | Array of skill strings |
| nice_to_have_skills | json nullable | Array of skill strings |
| employment_type | enum | full_time, part_time, contract, intern |
| location | string nullable | |
| salary_min | decimal(10,2) nullable | |
| salary_max | decimal(10,2) nullable | |
| status | enum | draft, open, on_hold, closed |
| created_by | FK users | |
| closed_at | timestamp nullable | |
| timestamps | | |

**Business Rules:**
- Status transitions: draft -> open -> on_hold/closed; on_hold -> open/closed
- When any application reaches 'hired' stage, job status auto-changes to 'closed'
- Skills stored as JSON arrays, entered via comma-separated tag input in UI

#### candidates
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| organization_id | FK organizations | |
| first_name | string | |
| last_name | string | |
| email | string | |
| phone | string nullable | |
| current_company | string nullable | |
| current_title | string nullable | |
| experience_years | integer nullable | |
| source | enum | upload, referral, direct |
| notes | text nullable | |
| timestamps | | |

**Constraints:** Unique index on [organization_id, email]

#### resumes
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| candidate_id | FK candidates | |
| file_path | string | Storage path |
| file_name | string | Original filename |
| file_type | enum | pdf, docx |
| extracted_text | longtext nullable | Raw text from PDF/DOCX |
| parsed_data | json nullable | Structured extraction |
| uploaded_by | FK users | |
| timestamps | | |

**Business Rules:**
- Text extraction happens on upload using smalot/pdfparser (PDF) and phpoffice/phpword (DOCX)
- A candidate can have multiple resumes
- Resumes are stored in `storage/app/private/resumes/`

#### job_applications
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| job_posting_id | FK job_postings | |
| candidate_id | FK candidates | |
| resume_id | FK resumes | nullable |
| stage | enum | applied, ai_shortlisted, hr_screening, technical_round_1, technical_round_2, offer, hired, rejected |
| stage_notes | text nullable | |
| applied_at | timestamp | |
| ai_score | decimal(5,2) nullable | Overall AI score (0-100) |
| ai_analysis | json nullable | Full AI analysis result |
| ai_analyzed_at | timestamp nullable | |
| rejection_reason | text nullable | |
| timestamps | | |

**Constraints:** Unique index on [job_posting_id, candidate_id]

**Stage Pipeline:**
```
applied -> ai_shortlisted -> hr_screening -> technical_round_1 -> technical_round_2 -> offer -> hired
                                                                                              \-> rejected (from any stage)
```

**Business Rules:**
- AI analysis is triggered manually via "Analyze" button (dispatches AnalyzeResumeJob)
- AI score and analysis are stored on the application record
- Moving to 'hired' auto-closes the job posting
- Rejection can happen from any stage, with optional reason

#### interview_feedback
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| job_application_id | FK job_applications | |
| interviewer_id | FK users | |
| stage | string | Stage at which feedback given |
| rating | tinyint | 1-5 scale |
| strengths | text nullable | |
| weaknesses | text nullable | |
| recommendation | enum | strong_yes, yes, neutral, no, strong_no |
| notes | text nullable | |
| timestamps | | |

### 3.3 Resource Allocation Tables

#### employees
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | FK users nullable | Links to user account if exists |
| organization_id | FK organizations | |
| first_name | string | |
| last_name | string | |
| email | string | |
| department_id | FK departments | |
| designation | string nullable | Job title |
| resume_id | FK resumes nullable | Links to uploaded resume |
| skills_from_resume | json nullable | Skills extracted from resume |
| skills_from_jira | json nullable | Skills extracted from Jira tasks |
| combined_skill_profile | json nullable | Merged skill profile |
| is_active | boolean | Default true |
| timestamps | | |

**Business Rules:**
- Skill profile is built from two sources: resume + Jira work history
- Combined profile is computed by the AI service when Jira signals are extracted
- Employees can exist independently of user accounts

#### jira_connections
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| organization_id | FK organizations | |
| jira_base_url | string | e.g. https://company.atlassian.net |
| jira_email | string | API auth email |
| jira_api_token | text encrypted | Stored encrypted |
| is_active | boolean | Default true |
| last_synced_at | timestamp nullable | |
| timestamps | | |

#### employee_jira_tasks
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| employee_id | FK employees | |
| jira_connection_id | FK jira_connections | |
| jira_task_key | string | e.g. PROJ-123 |
| summary | string | |
| description | text nullable | |
| task_type | string | Story, Bug, Task, etc. |
| status | string | |
| priority | string nullable | |
| labels | json nullable | |
| components | json nullable | |
| story_points | decimal(5,2) nullable | |
| resolution | string nullable | |
| resolved_at | timestamp nullable | |
| created_in_jira_at | timestamp nullable | |
| timestamps | | |

#### projects
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| organization_id | FK organizations | |
| name | string | |
| description | text nullable | |
| required_skills | json | Array of skill strings |
| required_technologies | json nullable | Array of technology strings |
| complexity_level | enum | low, medium, high, critical |
| domain_context | text nullable | Business domain description |
| start_date | date nullable | |
| end_date | date nullable | |
| status | enum | planning, active, completed, on_hold |
| created_by | FK users | |
| timestamps | | |

#### project_resource_matches
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| project_id | FK projects | |
| employee_id | FK employees | |
| match_score | decimal(5,2) | AI-computed score (0-100) |
| strength_areas | json nullable | Skills where employee excels |
| skill_gaps | json nullable | Skills employee lacks |
| explanation | text nullable | AI explanation |
| is_assigned | boolean | Default false |
| assigned_at | timestamp nullable | |
| timestamps | | |

### 3.4 System Tables

#### ai_processing_logs
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| organization_id | FK organizations nullable | |
| endpoint | string | AI service endpoint called |
| request_payload | json nullable | |
| response_payload | json nullable | |
| status | enum | pending, processing, completed, failed |
| error_message | text nullable | |
| processing_time_ms | integer nullable | |
| created_at | timestamp | No updated_at |

#### activity_logs
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | FK users nullable | |
| organization_id | FK organizations nullable | |
| action | string | e.g. "created_job", "analyzed_resume" |
| subject_type | string nullable | Model class name |
| subject_id | bigint nullable | Model ID |
| details | json nullable | Additional context |
| timestamps | | |

---

## 4. Python AI Service API Contracts

### 4.1 Configuration
- `LLM_PROVIDER`: "openai" or "anthropic"
- `OPENAI_API_KEY` / `ANTHROPIC_API_KEY`: Provider API keys
- `LLM_MODEL`: Model identifier (default: gpt-4o for OpenAI, claude-sonnet for Anthropic)
- Unified interface: both providers return the same response formats

### 4.2 GET /health
**Response:**
```json
{"status": "healthy", "version": "1.0.0"}
```

### 4.3 POST /analyze-resume
Analyzes a candidate's resume against a specific job posting.

**Request:**
```json
{
  "resume_text": "Full extracted resume text...",
  "job_title": "Senior Backend Developer",
  "job_description": "We are looking for...",
  "required_skills": ["Python", "Node.js", "AWS"],
  "min_experience": 5,
  "max_experience": 10
}
```

**Response:**
```json
{
  "overall_score": 85.5,
  "skill_match_score": 90.0,
  "experience_score": 80.0,
  "relevance_score": 85.0,
  "authenticity_score": 87.0,
  "skill_analysis": [
    {"skill": "Python", "level": "advanced", "evidence": "Built microservices...", "score": 92}
  ],
  "experience_summary": "7 years of backend development...",
  "strengths": ["Strong Python background", "Cloud experience"],
  "concerns": ["No GraphQL experience"],
  "recommendation": "strong_match",
  "explanation": "Candidate is a strong fit because..."
}
```

**Scoring Weights:** skill_match 35%, experience 25%, relevance 25%, authenticity 15%

**Recommendation Values:** strong_match | good_match | partial_match | weak_match

### 4.4 POST /extract-jira-signals
Extracts skill signals and work patterns from an employee's Jira task history.

**Request:**
```json
{
  "employee_name": "Alice Wang",
  "tasks": [
    {
      "key": "PROJ-123",
      "summary": "Implement user authentication",
      "description": "Add JWT-based auth...",
      "type": "Story",
      "status": "Done",
      "priority": "High",
      "labels": ["backend", "security"],
      "story_points": 5,
      "resolved_at": "2024-01-15"
    }
  ]
}
```

**Response:**
```json
{
  "extracted_skills": [
    {"skill": "React", "confidence": 0.9, "depth": "advanced", "evidence_count": 12, "last_used": "2024-01"}
  ],
  "work_patterns": {
    "complexity_preference": "high",
    "avg_story_points": 5.2,
    "domains": ["backend", "infrastructure"],
    "consistency_score": 88.0
  },
  "summary": "Alice is a backend-focused engineer..."
}
```

**Depth Levels:** surface | working | deep | expert

### 4.5 POST /match-project-resources
Matches employees to a project based on skills, experience, and work patterns.

**Request:**
```json
{
  "project": {
    "name": "Customer Portal Redesign",
    "description": "Complete redesign of...",
    "required_skills": ["React", "TypeScript", "Python"],
    "required_technologies": ["React", "FastAPI", "AWS"],
    "complexity_level": "high",
    "domain_context": "B2B SaaS customer portal"
  },
  "employees": [
    {
      "id": 1,
      "name": "Alice Wang",
      "skills_from_resume": {"Python": "advanced", "Django": "advanced"},
      "skills_from_jira": {"React": {"confidence": 0.8}},
      "combined_skill_profile": {"top_skills": ["Python", "Django", "AWS"]}
    }
  ]
}
```

**Response:**
```json
{
  "matches": [
    {
      "employee_id": 1,
      "match_score": 88.5,
      "strength_areas": ["Python", "AWS"],
      "skill_gaps": ["Kubernetes"],
      "explanation": "Alice is a strong match due to..."
    }
  ]
}
```

**Score Ranges:** 80-100 excellent, 60-79 good, 40-59 partial, 0-39 weak

---

## 5. User Workflows

### 5.1 Hiring Workflow
1. **Create Job Posting** - HR/hiring manager creates a job with title, description, required skills, experience range
2. **Add Candidates** - Upload candidates with resumes (PDF/DOCX)
3. **Create Applications** - Link candidates to job postings
4. **AI Analysis** - Click "Analyze" on an application to dispatch AI resume analysis
5. **Review AI Scores** - View overall score, skill breakdown, strengths, concerns, recommendation
6. **Move Through Pipeline** - Progress applications through stages: applied -> ai_shortlisted -> hr_screening -> technical rounds -> offer -> hired/rejected
7. **Add Interview Feedback** - Interviewers add ratings, strengths, weaknesses, recommendations at each stage
8. **Hire or Reject** - Final decision closes the pipeline

### 5.2 Resource Allocation Workflow
1. **Add Employees** - Create employee profiles with department, designation, skills
2. **Connect Jira** - Add Jira connection (base URL + API credentials)
3. **Sync Jira Tasks** - Fetch employee's task history from Jira
4. **Extract Skill Signals** - AI analyzes Jira tasks to extract skills and work patterns
5. **Create Project** - Define project with required skills, technologies, complexity, domain
6. **Find Resources** - Click "Find Resources" to trigger AI-powered employee matching
7. **Review Matches** - View ranked employees with scores, strengths, skill gaps, explanations
8. **Assign Resources** - Assign best-matched employees to the project

### 5.3 Settings & Admin
- **Organization Settings** - Edit org name, domain
- **User Management** - CRUD users, assign roles, toggle active status
- **Registration** - Self-registration creates new org + admin user

---

## 6. UI Design Rules

### 6.1 Layout
- **Sidebar navigation** on the left - role-based menu items
- **Top bar** with user name, role badge, logout
- **Content area** with page header and card-based sections
- **Flash messages** auto-dismiss after 5 seconds

### 6.2 Technology
- Server-rendered Blade templates (no SPA, no client-side framework)
- Plain CSS with CSS custom properties (variables) for theming
- Vanilla JavaScript for interactivity (modals, tag inputs, AJAX)
- No npm build step, no Tailwind, no React/Vue
- All assets served from `public/css/app.css` and `public/js/app.js`

### 6.3 Component Patterns
- **Tables** with search/filter, sortable headers, pagination
- **Forms** with validation errors displayed inline
- **Tag inputs** for skills (comma/enter to add, backspace to remove)
- **Modals** for secondary actions (add application, add feedback)
- **Stage badges** color-coded by pipeline stage
- **Score displays** with colored progress bars
- **Cards** for stats, detail sections, and grouped content

### 6.4 Color Scheme
- Primary: `#4f46e5` (indigo)
- Success: `#059669` (green)
- Warning: `#d97706` (amber)
- Danger: `#dc2626` (red)
- Background: `#f8fafc` (light gray)
- Sidebar: `#1e293b` (dark slate)

---

## 7. Integration Points

### 7.1 Laravel -> Python AI Service
- **Transport**: HTTP via `AiServiceClient` service class
- **Config**: `AI_SERVICE_URL` env var (default: http://localhost:8000)
- **Timeout**: `AI_SERVICE_TIMEOUT` env var (default: 120 seconds)
- **Async**: All AI calls dispatched via Laravel Queue jobs (database driver)
- **Logging**: Every AI call logged to `ai_processing_logs` table with request/response/timing

### 7.2 Laravel -> Jira
- **Transport**: HTTP Basic Auth (email + API token) via `SyncJiraTasksJob`
- **Endpoint**: `{jira_base_url}/rest/api/3/search`
- **Stored**: Tasks cached in `employee_jira_tasks` table
- **Trigger**: Manual "Sync Jira" button on employee profile

---

## 8. Deployment

### 8.1 Development (Current)
- Windows + XAMPP (Apache + MariaDB)
- PHP 8.2.30 via `C:\xampp\php\`
- Python 3.13 for AI service
- Access via http://localhost/talent/public/

### 8.2 Production (Target)
- Docker containers on Linux (AWS/GCP)
- `docker-compose.yml` with 5 services:
  - `app` - PHP-FPM 8.2 (Laravel)
  - `nginx` - Reverse proxy
  - `db` - MySQL 8.0
  - `ai-service` - Python 3.11 (FastAPI + uvicorn)
  - `queue-worker` - PHP (Laravel queue:work)
- Environment variables via `.env` files
- Persistent volume for MySQL data

### 8.3 Environment Variables
**Laravel (.env):**
- `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `AI_SERVICE_URL` - Python service URL
- `AI_SERVICE_TIMEOUT` - Request timeout in seconds
- `QUEUE_CONNECTION=database`
- `SESSION_DRIVER=database`

**Python (ai-service/.env):**
- `LLM_PROVIDER` - "openai" or "anthropic"
- `OPENAI_API_KEY`, `OPENAI_MODEL`
- `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL`

---

## 9. Seeded Test Data

| Entity | Details |
|--------|---------|
| Organization | Acme Technologies (acme-tech) |
| Admin | admin@acme.com / password (org_admin) |
| HR Manager | hr@acme.com / password |
| Resource Manager | rm@acme.com / password |
| Departments | Engineering, Product, Design, Data Science |
| Job Postings | Senior Backend Developer, React Frontend Developer, ML Engineer |
| Candidates | 5 candidates (John Smith, Emily Chen, Alex Johnson, Priya Patel, David Kim) |
| Applications | 3 applications for Senior Backend Developer job |
| Employees | 5 employees (Alice Wang, Bob Martinez, Carol Davis, Dan Wilson, Eva Brown) |
| Projects | Customer Portal Redesign, ML Recommendation Engine |

---

## 10. Change Log

| Date | Change | Approved By |
|------|--------|-------------|
| 2026-02-09 | Initial specification created from build | User |

---

*Last updated: 2026-02-09*
