import os
import sys
import shutil

# Navigate up from ai-service/app to talent project root
script_dir = os.path.dirname(os.path.abspath(__file__))
talent_root = os.path.abspath(os.path.join(script_dir, '..', '..'))
models_dir = os.path.join(talent_root, 'app', 'Models')

print(f"Script location: {script_dir}")
print(f"Talent root: {talent_root}")
print(f"Models dir: {models_dir}")

os.makedirs(models_dir, exist_ok=True)

models = {}

models["Organization.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'logo_path',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function jiraConnections(): HasMany
    {
        return $this->hasMany(JiraConnection::class);
    }

    public function aiProcessingLogs(): HasMany
    {
        return $this->hasMany(AiProcessingLog::class);
    }
}
'''

models["Department.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
'''

models["JobPosting.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class JobPosting extends Model
{
    use HasFactory;

    protected $table = 'job_postings';

    protected $fillable = [
        'organization_id',
        'department_id',
        'title',
        'description',
        'requirements',
        'min_experience',
        'max_experience',
        'required_skills',
        'nice_to_have_skills',
        'employment_type',
        'location',
        'salary_min',
        'salary_max',
        'status',
        'created_by',
        'closed_at',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'nice_to_have_skills' => 'array',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
'''

models["Candidate.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'current_company',
        'current_title',
        'experience_years',
        'source',
        'notes',
    ];

    protected $casts = [
        'experience_years' => 'decimal:1',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
'''

models["Resume.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'file_path',
        'file_name',
        'file_type',
        'extracted_text',
        'parsed_data',
        'uploaded_by',
    ];

    protected $casts = [
        'parsed_data' => 'array',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
'''

models["JobApplication.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class JobApplication extends Model
{
    use HasFactory;

    protected $table = 'job_applications';

    protected $fillable = [
        'job_posting_id',
        'candidate_id',
        'resume_id',
        'stage',
        'stage_notes',
        'applied_at',
        'ai_score',
        'ai_analysis',
        'ai_analyzed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'ai_analysis' => 'array',
        'ai_score' => 'decimal:2',
        'applied_at' => 'datetime',
        'ai_analyzed_at' => 'datetime',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(InterviewFeedback::class);
    }
}
'''

models["InterviewFeedback.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class InterviewFeedback extends Model
{
    use HasFactory;

    protected $table = 'interview_feedback';

    protected $fillable = [
        'job_application_id',
        'interviewer_id',
        'stage',
        'rating',
        'strengths',
        'weaknesses',
        'recommendation',
        'notes',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function interviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }
}
'''

models["Employee.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'department_id',
        'designation',
        'resume_id',
        'skills_from_resume',
        'skills_from_jira',
        'combined_skill_profile',
        'is_active',
    ];

    protected $casts = [
        'skills_from_resume' => 'array',
        'skills_from_jira' => 'array',
        'combined_skill_profile' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function jiraTasks(): HasMany
    {
        return $this->hasMany(EmployeeJiraTask::class);
    }

    public function resourceMatches(): HasMany
    {
        return $this->hasMany(ProjectResourceMatch::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
'''

models["JiraConnection.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class JiraConnection extends Model
{
    use HasFactory;

    protected $table = 'jira_connections';

    protected $fillable = [
        'organization_id',
        'jira_base_url',
        'jira_email',
        'jira_api_token',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'jira_api_token' => 'encrypted',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'jira_api_token',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(EmployeeJiraTask::class);
    }
}
'''

models["EmployeeJiraTask.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class EmployeeJiraTask extends Model
{
    use HasFactory;

    protected $table = 'employee_jira_tasks';

    protected $fillable = [
        'employee_id',
        'jira_connection_id',
        'jira_task_key',
        'summary',
        'description',
        'task_type',
        'status',
        'priority',
        'labels',
        'components',
        'story_points',
        'resolution',
        'resolved_at',
        'created_in_jira_at',
    ];

    protected $casts = [
        'labels' => 'array',
        'components' => 'array',
        'story_points' => 'decimal:1',
        'resolved_at' => 'datetime',
        'created_in_jira_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function jiraConnection(): BelongsTo
    {
        return $this->belongsTo(JiraConnection::class);
    }
}
'''

models["Project.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'required_skills',
        'required_technologies',
        'complexity_level',
        'domain_context',
        'start_date',
        'end_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'required_technologies' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resourceMatches(): HasMany
    {
        return $this->hasMany(ProjectResourceMatch::class);
    }
}
'''

models["ProjectResourceMatch.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class ProjectResourceMatch extends Model
{
    use HasFactory;

    protected $table = 'project_resource_matches';

    protected $fillable = [
        'project_id',
        'employee_id',
        'match_score',
        'strength_areas',
        'skill_gaps',
        'explanation',
        'is_assigned',
        'assigned_at',
    ];

    protected $casts = [
        'strength_areas' => 'array',
        'skill_gaps' => 'array',
        'match_score' => 'decimal:2',
        'is_assigned' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
'''

models["AiProcessingLog.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class AiProcessingLog extends Model
{
    use HasFactory;

    protected $table = 'ai_processing_logs';

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'endpoint',
        'request_payload',
        'response_payload',
        'status',
        'error_message',
        'processing_time_ms',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
'''

models["ActivityLog.php"] = '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'organization_id',
        'action',
        'subject_type',
        'subject_id',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
'''

# Write all model files
for filename, content in models.items():
    filepath = os.path.join(models_dir, filename)
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Created: {filepath}")

# Read existing User.php
user_path = os.path.join(models_dir, "User.php")
if os.path.exists(user_path):
    with open(user_path, 'r', encoding='utf-8') as f:
        existing = f.read()
    print(f"\n--- EXISTING User.php ({len(existing)} bytes) ---")
    print(existing)
    print("--- END ---")
else:
    print("\nUser.php does not exist yet")

print("\nDone creating 14 model files!")
print(f"\nFiles in {models_dir}:")
for f in sorted(os.listdir(models_dir)):
    print(f"  {f}")
