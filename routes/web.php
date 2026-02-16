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
use App\Http\Controllers\Hiring\JobParserController;
use App\Http\Controllers\Hiring\CandidateParserController;
use App\Http\Controllers\Settings\OrganizationController;
use App\Http\Controllers\Settings\UserManagementController;
use App\Http\Controllers\Settings\IntegrationsController;
use App\Http\Controllers\ResourceAllocation\EmployeeImportController;
use App\Http\Controllers\Intelligence\SignalDashboardController;
use App\Http\Controllers\Intelligence\SignalConfigController;
use App\Http\Controllers\ResourceAllocation\ProjectParserController;
use App\Http\Controllers\Settings\LlmConfigController;
use App\Http\Controllers\Settings\ScoringRulesController;

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
        // AI parsing routes (must be before resource routes to avoid conflict)
        Route::post('jobs/parse-document', [JobParserController::class, 'parse'])->name('jobs.parseDocument');
        Route::post('candidates/parse-resume', [CandidateParserController::class, 'parse'])->name('candidates.parseResume');

        // Bulk candidate upload (must be before resource route)
        Route::get('candidates/bulk-upload', [CandidateController::class, 'bulkCreate'])->name('candidates.bulkCreate');
        Route::post('candidates/bulk-upload', [CandidateController::class, 'bulkStore'])->name('candidates.bulkStore');

        // Candidate search API (for typeahead in job application modal)
        Route::get('candidates/search', [CandidateController::class, 'search'])->name('candidates.search');

        Route::resource('jobs', JobController::class);
        Route::post('jobs/{job}/status', [JobController::class, 'updateStatus'])->name('jobs.updateStatus');

        Route::resource('candidates', CandidateController::class);
        Route::post('candidates/{candidate}/apply-to-jobs', [CandidateController::class, 'applyToJobs'])->name('candidates.applyToJobs');
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
        // Employee import routes (must be before resource route)
        Route::get('employees/import', [EmployeeImportController::class, 'showImport'])->name('employees.import');
        Route::post('employees/import/upload', [EmployeeImportController::class, 'uploadSpreadsheet'])->name('employees.import.upload');
        Route::post('employees/import/confirm', [EmployeeImportController::class, 'confirmImport'])->name('employees.import.confirm');
        Route::get('employees/import/template', [EmployeeImportController::class, 'downloadTemplate'])->name('employees.import.template');
        Route::post('employees/import/sync-zoho-people', [EmployeeImportController::class, 'syncZohoPeople'])->name('employees.import.syncZohoPeople');

        Route::resource('employees', EmployeeController::class);
        Route::post('employees/{employee}/sync-jira', [EmployeeController::class, 'syncJiraTasks'])->name('employees.syncJira');

        Route::resource('jira-connections', JiraConnectionController::class)->except(['show', 'edit', 'update']);
        Route::post('jira-connections/{jira_connection}/test', [JiraConnectionController::class, 'test'])->name('jira-connections.test');
        Route::post('jira-connections/{jira_connection}/sync', [JiraConnectionController::class, 'sync'])->name('jira-connections.sync');

        // AI project parsing (must be before resource route)
        Route::post('projects/parse-document', [ProjectParserController::class, 'parse'])->name('projects.parseDocument');

        Route::resource('projects', ProjectController::class);
        Route::post('projects/{project}/find-resources', [ProjectController::class, 'findResources'])->name('projects.findResources');
        Route::post('projects/{project}/sprint-sheets', [ProjectController::class, 'uploadSprintSheets'])->name('projects.sprintSheets.upload');
        Route::delete('projects/{project}/sprint-sheets/{sprintSheet}', [ProjectController::class, 'deleteSprintSheet'])->name('projects.sprintSheets.destroy');
        Route::post('projects/{project}/resources/{match}/assign', [ResourceMatchController::class, 'assign'])->name('resources.assign');
        Route::delete('projects/{project}/resources/{match}/unassign', [ResourceMatchController::class, 'unassign'])->name('resources.unassign');
    });

    // Settings (org_admin, super_admin)
    Route::middleware(['role:org_admin,super_admin'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('organization', [OrganizationController::class, 'edit'])->name('organization.edit');
        Route::put('organization', [OrganizationController::class, 'update'])->name('organization.update');
        Route::resource('users', UserManagementController::class)->except(['show']);
        Route::post('users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggleActive');

        // Integrations hub
        Route::get('integrations', [IntegrationsController::class, 'index'])->name('integrations.index');
        Route::post('integrations/zoho-projects', [IntegrationsController::class, 'storeZohoProjects'])->name('integrations.zohoProjects.store');
        Route::post('integrations/zoho-projects/{connection}/test', [IntegrationsController::class, 'testZohoProjects'])->name('integrations.zohoProjects.test');
        Route::delete('integrations/zoho-projects/{connection}', [IntegrationsController::class, 'destroyZohoProjects'])->name('integrations.zohoProjects.destroy');
        // LLM Configuration
        Route::get('llm', [LlmConfigController::class, 'edit'])->name('llm.edit');
        Route::put('llm', [LlmConfigController::class, 'update'])->name('llm.update');
        Route::post('llm/test', [LlmConfigController::class, 'test'])->name('llm.test');

        // Scoring Rules
        Route::get('scoring-rules', [ScoringRulesController::class, 'index'])->name('scoring.index');
        Route::put('scoring-rules', [ScoringRulesController::class, 'update'])->name('scoring.update');
        Route::post('scoring-rules/{rule}/toggle', [ScoringRulesController::class, 'toggleSignal'])->name('scoring.toggle');
        Route::post('scoring-rules/optimize', [ScoringRulesController::class, 'triggerOptimization'])->name('scoring.optimize');
        Route::post('scoring-rules/{version}/rollback', [ScoringRulesController::class, 'rollback'])->name('scoring.rollback');

        // Zoho People
        Route::post('integrations/zoho-people', [IntegrationsController::class, 'storeZohoPeople'])->name('integrations.zohoPeople.store');
        Route::post('integrations/zoho-people/{connection}/test', [IntegrationsController::class, 'testZohoPeople'])->name('integrations.zohoPeople.test');
        Route::post('integrations/zoho-people/{connection}/sync', [IntegrationsController::class, 'syncZohoPeople'])->name('integrations.zohoPeople.sync');
        Route::delete('integrations/zoho-people/{connection}', [IntegrationsController::class, 'destroyZohoPeople'])->name('integrations.zohoPeople.destroy');
    });

    // Intelligence (premium feature, org_admin + resource_manager)
    Route::middleware(['role:resource_manager,org_admin,super_admin', 'premium'])
        ->prefix('intelligence')
        ->name('intelligence.')
        ->group(function () {
            Route::get('/', [SignalDashboardController::class, 'index'])->name('dashboard');
            Route::get('employees/{employee}', [SignalDashboardController::class, 'employeeSignals'])->name('employee');
            Route::post('compute', [SignalDashboardController::class, 'computeSignals'])->name('compute');
            Route::get('config', [SignalConfigController::class, 'index'])->name('config');
            Route::post('config/sources', [SignalConfigController::class, 'storeSource'])->name('config.storeSource');
            Route::delete('config/sources/{source}', [SignalConfigController::class, 'destroySource'])->name('config.destroySource');
            Route::post('config/integrations', [SignalConfigController::class, 'storeIntegration'])->name('config.storeIntegration');
            Route::delete('config/integrations/{connection}', [SignalConfigController::class, 'destroyIntegration'])->name('config.destroyIntegration');
            Route::post('config/sprint-sheets', [SignalConfigController::class, 'uploadSprintSheet'])->name('config.uploadSprintSheet');
        });
});
