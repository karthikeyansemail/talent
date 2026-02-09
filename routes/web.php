<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Hiring\JobController;
use App\Http\Controllers\Hiring\CandidateController;
use App\Http\Controllers\Hiring\ResumeController;
use App\Http\Controllers\Hiring\ApplicationController;
use App\Http\Controllers\Hiring\InterviewFeedbackController;
use App\Http\Controllers\Hiring\HiringReportsController;
use App\Http\Controllers\ResourceAllocation\EmployeeController;
use App\Http\Controllers\ResourceAllocation\JiraConnectionController;
use App\Http\Controllers\ResourceAllocation\ProjectController;
use App\Http\Controllers\ResourceAllocation\ResourceMatchController;
use App\Http\Controllers\Settings\OrganizationController;
use App\Http\Controllers\Settings\UserManagementController;

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', fn() => redirect('/dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Hiring (hr_manager, hiring_manager, org_admin, super_admin)
    Route::middleware(['role:hr_manager,hiring_manager,org_admin,super_admin'])->group(function () {
        Route::resource('jobs', JobController::class);
        Route::post('jobs/{job}/status', [JobController::class, 'updateStatus'])->name('jobs.updateStatus');

        Route::resource('candidates', CandidateController::class);
        Route::post('candidates/{candidate}/resumes', [ResumeController::class, 'upload'])->name('resumes.upload');
        Route::get('candidates/{candidate}/resumes/{resume}/download', [ResumeController::class, 'download'])->name('resumes.download');

        Route::get('jobs/{job}/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::post('jobs/{job}/applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::put('applications/{application}/stage', [ApplicationController::class, 'updateStage'])->name('applications.updateStage');
        Route::post('applications/{application}/analyze', [ApplicationController::class, 'triggerAiAnalysis'])->name('applications.analyze');

        Route::post('applications/{application}/feedback', [InterviewFeedbackController::class, 'store'])->name('feedback.store');
        Route::delete('feedback/{feedback}', [InterviewFeedbackController::class, 'destroy'])->name('feedback.destroy');

        Route::get('hiring/reports', [HiringReportsController::class, 'index'])->name('hiring.reports');
    });

    // Resource Allocation (resource_manager, org_admin, super_admin)
    Route::middleware(['role:resource_manager,org_admin,super_admin'])->group(function () {
        Route::resource('employees', EmployeeController::class);
        Route::post('employees/{employee}/sync-jira', [EmployeeController::class, 'syncJiraTasks'])->name('employees.syncJira');

        Route::resource('jira-connections', JiraConnectionController::class)->except(['show', 'edit', 'update']);
        Route::post('jira-connections/{jira_connection}/test', [JiraConnectionController::class, 'test'])->name('jira-connections.test');
        Route::post('jira-connections/{jira_connection}/sync', [JiraConnectionController::class, 'sync'])->name('jira-connections.sync');

        Route::resource('projects', ProjectController::class);
        Route::post('projects/{project}/find-resources', [ProjectController::class, 'findResources'])->name('projects.findResources');
        Route::post('projects/{project}/resources/{match}/assign', [ResourceMatchController::class, 'assign'])->name('resources.assign');
        Route::delete('projects/{project}/resources/{match}/unassign', [ResourceMatchController::class, 'unassign'])->name('resources.unassign');
    });

    // Settings (org_admin, super_admin)
    Route::middleware(['role:org_admin,super_admin'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('organization', [OrganizationController::class, 'edit'])->name('organization.edit');
        Route::put('organization', [OrganizationController::class, 'update'])->name('organization.update');
        Route::resource('users', UserManagementController::class)->except(['show']);
        Route::post('users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggleActive');
    });
});
