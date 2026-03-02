<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\Employee;
use App\Models\InterviewFeedback;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Project;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanTestData extends Command
{
    protected $signature = 'app:clean-test-data {--force : Skip confirmation prompt}';
    protected $description = 'Delete test accounts and data created by Playwright and Dusk browser tests';

    public function handle(): int
    {
        // Gather counts before deleting
        $testUsers = User::where('email', 'like', 'dusk.%')
            ->orWhere('email', 'like', 'pw.%')
            ->get();

        $testCandidates = Candidate::where('email', 'like', 'dusk.%')
            ->orWhere('email', 'like', 'pw.%')
            ->get();

        $testEmployees = Employee::where('email', 'like', 'dusk.%')
            ->orWhere('email', 'like', 'pw.%')
            ->get();

        $testJobs = JobPosting::where('title', 'like', 'Dusk Test -%')
            ->orWhere('title', 'like', 'Playwright Test -%')
            ->get();

        $testProjects = Project::where('name', 'like', 'Dusk Test%')
            ->orWhere('name', 'like', 'Playwright Test%')
            ->get();

        // Interview feedback linked to test candidates' applications
        $testCandidateIds = $testCandidates->pluck('id');
        $testJobIds = $testJobs->pluck('id');

        $testApplications = JobApplication::whereIn('candidate_id', $testCandidateIds)
            ->orWhereIn('job_posting_id', $testJobIds)
            ->get();

        $testApplicationIds = $testApplications->pluck('id');

        $testFeedback = InterviewFeedback::where(function ($q) use ($testApplicationIds) {
            $q->whereIn('job_application_id', $testApplicationIds)
                ->orWhere('notes', 'like', '%automated test feedback%');
        })->get();

        $testResumes = Resume::whereIn('candidate_id', $testCandidateIds)->get();

        // Display summary
        $this->info('Test data found:');
        $this->table(
            ['Table', 'Records'],
            [
                ['users', $testUsers->count()],
                ['candidates', $testCandidates->count()],
                ['employees', $testEmployees->count()],
                ['job_postings', $testJobs->count()],
                ['projects', $testProjects->count()],
                ['job_applications', $testApplications->count()],
                ['interview_feedback', $testFeedback->count()],
                ['resumes', $testResumes->count()],
            ]
        );

        $total = $testUsers->count() + $testCandidates->count() + $testEmployees->count()
            + $testJobs->count() + $testProjects->count() + $testApplications->count()
            + $testFeedback->count() + $testResumes->count();

        if ($total === 0) {
            $this->info('No test data found. Nothing to clean up.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm("Delete {$total} test records? This cannot be undone.")) {
            $this->info('Aborted.');
            return 0;
        }

        DB::transaction(function () use (
            $testFeedback, $testApplications, $testResumes,
            $testCandidates, $testJobs, $testProjects,
            $testEmployees, $testUsers
        ) {
            // Delete in FK-safe order: dependents first

            // 1. Interview feedback (depends on job_applications)
            if ($testFeedback->isNotEmpty()) {
                InterviewFeedback::whereIn('id', $testFeedback->pluck('id'))->delete();
                $this->line("  Deleted {$testFeedback->count()} interview_feedback records");
            }

            // 2. Job applications (depends on job_postings, candidates, resumes)
            if ($testApplications->isNotEmpty()) {
                JobApplication::whereIn('id', $testApplications->pluck('id'))->delete();
                $this->line("  Deleted {$testApplications->count()} job_applications records");
            }

            // 3. Resumes (depends on candidates)
            if ($testResumes->isNotEmpty()) {
                Resume::whereIn('id', $testResumes->pluck('id'))->delete();
                $this->line("  Deleted {$testResumes->count()} resumes records");
            }

            // 4. Candidates (independent after applications/resumes removed)
            if ($testCandidates->isNotEmpty()) {
                Candidate::whereIn('id', $testCandidates->pluck('id'))->delete();
                $this->line("  Deleted {$testCandidates->count()} candidates records");
            }

            // 5. Job postings (independent after applications removed)
            if ($testJobs->isNotEmpty()) {
                JobPosting::whereIn('id', $testJobs->pluck('id'))->delete();
                $this->line("  Deleted {$testJobs->count()} job_postings records");
            }

            // 6. Projects
            if ($testProjects->isNotEmpty()) {
                Project::whereIn('id', $testProjects->pluck('id'))->delete();
                $this->line("  Deleted {$testProjects->count()} projects records");
            }

            // 7. Employees
            if ($testEmployees->isNotEmpty()) {
                Employee::whereIn('id', $testEmployees->pluck('id'))->delete();
                $this->line("  Deleted {$testEmployees->count()} employees records");
            }

            // 8. Users (last, since other tables may reference them)
            if ($testUsers->isNotEmpty()) {
                User::whereIn('id', $testUsers->pluck('id'))->delete();
                $this->line("  Deleted {$testUsers->count()} users records");
            }
        });

        $this->info("Successfully cleaned up {$total} test records.");
        return 0;
    }
}
