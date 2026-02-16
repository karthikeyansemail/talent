# Talent Intelligence Platform - Claude Code Guidelines

## Quick Reference
- **Project**: AI Hiring & Talent Intelligence Platform
- **Full Specification**: See [SPECIFICATION.md](SPECIFICATION.md) - ALWAYS consult before making changes
- **Stack**: PHP Laravel 12 + Python FastAPI + MySQL/MariaDB
- **Project Path**: `C:\xampp\htdocs\talent\`
- **PHP Binary**: `C:\xampp\php\php.exe` (v8.2.30)
- **Composer**: `C:\xampp\php\php.exe C:\xampp\php\composer`

## Core Rules

### 1. Specification Compliance
- **ALWAYS** read `SPECIFICATION.md` before modifying business logic, database schema, API contracts, or user workflows
- **NEVER** deviate from the specification without explicitly consulting the user first
- If a requested change conflicts with the specification, flag the conflict and ask how to proceed
- After approved deviations, **UPDATE** `SPECIFICATION.md` to reflect the new agreed-upon behavior

### 2. Architecture Boundaries
- **Laravel** handles ALL business logic, UI, routing, auth, and data persistence
- **Python FastAPI** handles ONLY AI/LLM analysis - it is a stateless service with no database access
- Communication between PHP and Python is via REST API only (AiServiceClient -> FastAPI endpoints)
- **NEVER** add database access to the Python service
- **NEVER** add LLM/AI logic directly in Laravel

### 3. Multi-Tenancy
- ALL data is scoped to `organization_id` - every query on org-scoped tables MUST filter by organization
- Users belong to exactly one organization
- Never expose data from one organization to another

### 4. Role-Based Access
Roles (highest to lowest privilege): `super_admin` > `org_admin` > `hr_manager` / `hiring_manager` / `resource_manager` > `employee`
- Hiring features: `hr_manager`, `hiring_manager`, `org_admin`, `super_admin`
- Resource allocation: `resource_manager`, `org_admin`, `super_admin`
- Settings/user management: `org_admin`, `super_admin`
- Dashboard: all authenticated users

### 5. Two Pillars - Keep Separate
- **Pillar 1: Hiring/ATS** - Jobs, Candidates, Resumes, Applications, AI Resume Analysis, Interview Feedback
- **Pillar 2: Resource Allocation** - Employees, Jira Integration, Projects, AI Resource Matching
- These are separate feature areas with separate controllers, views, and navigation sections
- They share: Organizations, Users, Departments, AI Service infrastructure

## Development Commands

```bash
# Run migrations
cd /c/xampp/htdocs/talent && /c/xampp/php/php.exe artisan migrate

# Fresh seed
cd /c/xampp/htdocs/talent && /c/xampp/php/php.exe artisan migrate:fresh --seed

# Clear caches
cd /c/xampp/htdocs/talent && /c/xampp/php/php.exe artisan optimize:clear

# Route list
cd /c/xampp/htdocs/talent && /c/xampp/php/php.exe artisan route:list

# Queue worker
cd /c/xampp/htdocs/talent && /c/xampp/php/php.exe artisan queue:work

# Python AI service
cd /c/xampp/htdocs/talent/ai-service && python -m uvicorn app.main:app --reload --port 8000

# Install composer packages
cd /c/xampp/htdocs/talent && /c/xampp/php/php.exe /c/xampp/php/composer require <package>
```

## Key File Locations

| Purpose | Path |
|---------|------|
| Routes | `routes/web.php` |
| Models | `app/Models/` |
| Controllers | `app/Http/Controllers/` |
| Views | `resources/views/` |
| Middleware | `app/Http/Middleware/` |
| Services | `app/Services/` |
| Queue Jobs | `app/Jobs/` |
| Migrations | `database/migrations/` |
| Seeders | `database/seeders/` |
| CSS | `public/css/app.css` |
| JS | `public/js/app.js` |
| AI Service | `ai-service/app/` |
| AI Prompts | `ai-service/app/prompts/` |
| Config | `config/ai.php` |

## Coding Conventions
- Controllers: organized by domain (`Hiring/`, `ResourceAllocation/`, `Settings/`, `Auth/`)
- Views: match controller domain (`jobs/`, `candidates/`, `employees/`, `projects/`)
- Frontend: plain CSS + vanilla JS (no npm build step, no Tailwind, no React)
- Forms use `@csrf` and `@method` directives
- Flash messages: `session('success')` and `session('error')`
- All form validation in controllers using `$request->validate()`
- AI processing is async via Laravel Queue jobs

## Testing Credentials (Seeded Data)
| Role | Email | Password |
|------|-------|----------|
| Org Admin | admin@acme.com | password |
| HR Manager | hr@acme.com | password |
| Resource Manager | rm@acme.com | password |

## Deployment
- Docker-ready: `docker-compose.yml` with 5 services (app, nginx, db, ai-service, queue-worker)
- Target: Linux containers on AWS/GCP
- Dev: Windows/XAMPP
