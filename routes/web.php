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
use App\Http\Controllers\Settings\DepartmentController;
use App\Http\Controllers\Settings\PlatformBrandingController;
use App\Http\Controllers\Settings\OrgSwitcherController;
use App\Http\Controllers\Settings\OrganizationManagementController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\Settings\SsoConfigController;
use App\Http\Controllers\Settings\BillingController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ProfileController;

// Payment webhooks — outside auth (Stripe/Razorpay POST without session, verified by signature)
Route::post('/webhooks/stripe',    [WebhookController::class, 'stripe'])->name('webhooks.stripe');
Route::post('/webhooks/razorpay',  [WebhookController::class, 'razorpay'])->name('webhooks.razorpay');

// OAuth callbacks — outside auth middleware (no session user required during redirect)
Route::get('/auth/slack/callback', [IntegrationsController::class, 'oauthSlackCallback'])->name('integrations.oauth.slack.callback');
Route::get('/auth/teams/callback', [IntegrationsController::class, 'oauthTeamsCallback'])->name('integrations.oauth.teams.callback');

// SSO — outside auth middleware (user has no session yet)
Route::get('/auth/{provider}/redirect', [SsoController::class, 'redirect'])
    ->where('provider', 'google|microsoft|okta')
    ->name('sso.redirect');
Route::get('/auth/{provider}/callback', [SsoController::class, 'callback'])
    ->where('provider', 'google|microsoft|okta')
    ->name('sso.callback');

// Auth
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Password reset
use App\Http\Controllers\Auth\ForgotPasswordController;
Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', fn() => redirect('/dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (all authenticated users)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Hiring (hr_manager, hiring_manager, management, org_admin, super_admin)
    Route::middleware(['role:hr_manager,hiring_manager,management,org_admin,super_admin'])->group(function () {
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
        Route::get('jobs/{job}/download-jd', [JobController::class, 'downloadJd'])->name('jobs.downloadJd');

        Route::resource('candidates', CandidateController::class);
        Route::post('candidates/{candidate}/apply-to-jobs', [CandidateController::class, 'applyToJobs'])->name('candidates.applyToJobs');
        Route::post('candidates/{candidate}/resumes', [ResumeController::class, 'upload'])->name('resumes.upload');
        Route::get('candidates/{candidate}/resumes/{resume}/download', [ResumeController::class, 'download'])->name('resumes.download');

        Route::get('jobs/{job}/applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::post('jobs/{job}/applications', [ApplicationController::class, 'store'])->name('applications.store');
        Route::post('jobs/{job}/bulk-apply', [ApplicationController::class, 'bulkApply'])->name('applications.bulkApply');
        Route::get('applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::put('applications/{application}/stage', [ApplicationController::class, 'updateStage'])->name('applications.updateStage');
        Route::post('applications/{application}/analyze', [ApplicationController::class, 'triggerAiAnalysis'])->name('applications.analyze');
        Route::get('applications/{application}/analysis-status', [ApplicationController::class, 'analysisStatus'])->name('applications.analysisStatus');
        Route::post('jobs/{job}/analyze-all', [ApplicationController::class, 'analyzeAll'])->name('applications.analyzeAll');

        Route::post('applications/{application}/feedback', [InterviewFeedbackController::class, 'store'])->name('feedback.store');
        Route::delete('feedback/{feedback}', [InterviewFeedbackController::class, 'destroy'])->name('feedback.destroy');

        Route::get('hiring/reports', [HiringReportsController::class, 'index'])->name('hiring.reports');
    });

    // Resource Allocation (resource_manager, management, org_admin, super_admin)
    Route::middleware(['role:resource_manager,management,org_admin,super_admin'])->group(function () {
        // Employee import routes (must be before resource route)
        Route::get('employees/import', [EmployeeImportController::class, 'showImport'])->name('employees.import');
        Route::post('employees/import/upload', [EmployeeImportController::class, 'uploadSpreadsheet'])->name('employees.import.upload');
        Route::post('employees/import/confirm', [EmployeeImportController::class, 'confirmImport'])->name('employees.import.confirm');
        Route::get('employees/import/template', [EmployeeImportController::class, 'downloadTemplate'])->name('employees.import.template');
        Route::post('employees/import/sync-zoho-people', [EmployeeImportController::class, 'syncZohoPeople'])->name('employees.import.syncZohoPeople');

        Route::resource('employees', EmployeeController::class);
        // Generic work-data sync (connector-agnostic — dispatches whatever is active for the org)
        Route::post('employees/{employee}/sync-work-data', [EmployeeController::class, 'syncWorkData'])->name('employees.syncWorkData');
        Route::get('employees/{employee}/work-data-sync-status', [EmployeeController::class, 'workDataSyncStatus'])->name('employees.workDataSyncStatus');
        // Backwards-compat aliases (old Jira-specific URLs still work)
        Route::post('employees/{employee}/sync-jira', [EmployeeController::class, 'syncWorkData'])->name('employees.syncJira');
        Route::get('employees/{employee}/jira-sync-status', [EmployeeController::class, 'workDataSyncStatus'])->name('employees.jiraSyncStatus');
        Route::get('employees/{employee}/signal-intelligence', [EmployeeController::class, 'signalIntelligenceHtml'])->name('employees.signalIntelligenceHtml');
        Route::post('employees/{employee}/analyze-work-pulse', [EmployeeController::class, 'analyzeWorkPulse'])->name('employees.analyzeWorkPulse');
        Route::get('employees/{employee}/work-pulse-status', [EmployeeController::class, 'workPulseStatus'])->name('employees.workPulseStatus');

        Route::resource('jira-connections', JiraConnectionController::class)->except(['show', 'edit', 'update']);
        Route::post('jira-connections/{jira_connection}/test', [JiraConnectionController::class, 'test'])->name('jira-connections.test');
        Route::post('jira-connections/{jira_connection}/sync', [JiraConnectionController::class, 'sync'])->name('jira-connections.sync');

        // AI project parsing (must be before resource route)
        Route::post('projects/parse-document', [ProjectParserController::class, 'parse'])->name('projects.parseDocument');

        Route::resource('projects', ProjectController::class);
        Route::post('projects/{project}/find-resources', [ProjectController::class, 'findResources'])->name('projects.findResources');
        Route::get('projects/{project}/match-status', [ProjectController::class, 'matchStatus'])->name('projects.matchStatus');
        Route::get('projects/{project}/candidate-count', [ProjectController::class, 'candidateCount'])->name('projects.candidateCount');
        Route::post('projects/{project}/sprint-sheets', [ProjectController::class, 'uploadSprintSheets'])->name('projects.sprintSheets.upload');
        Route::delete('projects/{project}/sprint-sheets/{sprintSheet}', [ProjectController::class, 'deleteSprintSheet'])->name('projects.sprintSheets.destroy');
        Route::post('projects/{project}/documents', [ProjectController::class, 'uploadDocument'])->name('projects.documents.upload');
        Route::delete('projects/{project}/documents/{document}', [ProjectController::class, 'deleteDocument'])->name('projects.documents.destroy');
        Route::post('projects/{project}/sync-from-documents', [ProjectController::class, 'syncFromDocuments'])->name('projects.syncFromDocuments');
        Route::post('projects/{project}/resources/{match}/assign', [ResourceMatchController::class, 'assign'])->name('resources.assign');
        Route::delete('projects/{project}/resources/{match}/unassign', [ResourceMatchController::class, 'unassign'])->name('resources.unassign');
    });

    // Settings (org_admin, super_admin)
    Route::middleware(['role:org_admin,super_admin'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('organization', [OrganizationController::class, 'edit'])->name('organization.edit');
        Route::put('organization', [OrganizationController::class, 'update'])->name('organization.update');
        Route::resource('users', UserManagementController::class)->except(['show']);
        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');
        Route::post('users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('users.toggleActive');

        // Integrations hub
        Route::get('integrations', [IntegrationsController::class, 'index'])->name('integrations.index');
        Route::post('integrations/zoho-projects', [IntegrationsController::class, 'storeZohoProjects'])->name('integrations.zohoProjects.store');
        Route::post('integrations/zoho-projects/{connection}/test', [IntegrationsController::class, 'testZohoProjects'])->name('integrations.zohoProjects.test');
        Route::delete('integrations/zoho-projects/{connection}', [IntegrationsController::class, 'destroyZohoProjects'])->name('integrations.zohoProjects.destroy');

        // Zoho People
        Route::post('integrations/zoho-people', [IntegrationsController::class, 'storeZohoPeople'])->name('integrations.zohoPeople.store');
        Route::post('integrations/zoho-people/{connection}/test', [IntegrationsController::class, 'testZohoPeople'])->name('integrations.zohoPeople.test');
        Route::post('integrations/zoho-people/{connection}/sync', [IntegrationsController::class, 'syncZohoPeople'])->name('integrations.zohoPeople.sync');
        Route::delete('integrations/zoho-people/{connection}', [IntegrationsController::class, 'destroyZohoPeople'])->name('integrations.zohoPeople.destroy');

        // OrangeHRM
        Route::post('integrations/orangehrm', [IntegrationsController::class, 'storeOrangeHRM'])->name('integrations.orangehrm.store');
        Route::post('integrations/orangehrm/{connection}/test', [IntegrationsController::class, 'testOrangeHRM'])->name('integrations.orangehrm.test');
        Route::post('integrations/orangehrm/{connection}/sync', [IntegrationsController::class, 'syncOrangeHRM'])->name('integrations.orangehrm.sync');
        Route::delete('integrations/orangehrm/{connection}', [IntegrationsController::class, 'destroyOrangeHRM'])->name('integrations.orangehrm.destroy');

        // GitHub (Source Code Signals)
        Route::post('integrations/github', [IntegrationsController::class, 'storeGitHub'])->name('integrations.github.store');
        Route::post('integrations/github/{connection}/test', [IntegrationsController::class, 'testGitHub'])->name('integrations.github.test');
        Route::post('integrations/github/{connection}/sync', [IntegrationsController::class, 'syncGitHub'])->name('integrations.github.sync');
        Route::delete('integrations/github/{connection}', [IntegrationsController::class, 'destroyGitHub'])->name('integrations.github.destroy');

        // Microsoft DevOps Boards
        Route::post('integrations/devops', [IntegrationsController::class, 'storeDevOps'])->name('integrations.devops.store');
        Route::post('integrations/devops/{connection}/test', [IntegrationsController::class, 'testDevOps'])->name('integrations.devops.test');
        Route::post('integrations/devops/{connection}/sync', [IntegrationsController::class, 'syncDevOps'])->name('integrations.devops.sync');
        Route::delete('integrations/devops/{connection}', [IntegrationsController::class, 'destroyDevOps'])->name('integrations.devops.destroy');

        // GitHub Projects Boards
        Route::post('integrations/github-projects', [IntegrationsController::class, 'storeGitHubProjects'])->name('integrations.githubProjects.store');
        Route::post('integrations/github-projects/{connection}/test', [IntegrationsController::class, 'testGitHubProjects'])->name('integrations.githubProjects.test');
        Route::post('integrations/github-projects/{connection}/sync', [IntegrationsController::class, 'syncGitHubProjects'])->name('integrations.githubProjects.sync');
        Route::delete('integrations/github-projects/{connection}', [IntegrationsController::class, 'destroyGitHubProjects'])->name('integrations.githubProjects.destroy');

        // Slack OAuth initiation + sync/destroy (no store form — uses OAuth)
        Route::get('integrations/auth/slack', [IntegrationsController::class, 'oauthSlack'])->name('integrations.oauth.slack');
        Route::post('integrations/slack/{connection}/sync', [IntegrationsController::class, 'syncSlack'])->name('integrations.slack.sync');
        Route::delete('integrations/slack/{connection}', [IntegrationsController::class, 'destroySlack'])->name('integrations.slack.destroy');

        // Microsoft Teams OAuth initiation + sync/destroy (no store form — uses OAuth)
        Route::get('integrations/auth/teams', [IntegrationsController::class, 'oauthTeams'])->name('integrations.oauth.teams');
        Route::post('integrations/teams/{connection}/sync', [IntegrationsController::class, 'syncTeams'])->name('integrations.teams.sync');
        Route::delete('integrations/teams/{connection}', [IntegrationsController::class, 'destroyTeams'])->name('integrations.teams.destroy');
    });

    // Billing (org_admin, super_admin)
    Route::middleware(['role:org_admin,super_admin'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('billing',                         [BillingController::class, 'index'])->name('billing.index');
        Route::post('billing/checkout/stripe',        [BillingController::class, 'stripeCheckout'])->name('billing.stripe.checkout');
        Route::get('billing/success',                 [BillingController::class, 'success'])->name('billing.success');
        Route::get('billing/cancel',                  [BillingController::class, 'cancel'])->name('billing.cancel');
        Route::post('billing/razorpay/order',         [BillingController::class, 'razorpayCreateOrder'])->name('billing.razorpay.order');
        Route::post('billing/razorpay/verify',        [BillingController::class, 'razorpayVerify'])->name('billing.razorpay.verify');
        Route::post('billing/cancel-subscription',    [BillingController::class, 'cancelSubscription'])->name('billing.cancel-subscription');
        Route::post('billing/dev-activate',           [BillingController::class, 'devActivate'])->name('billing.dev-activate');
        Route::get('billing/deploy-guide',            [BillingController::class, 'deployGuide'])->name('billing.deploy-guide');
    });

    // Hiring Scoring Rules (hr_manager, org_admin, super_admin)
    Route::middleware(['role:hr_manager,org_admin,super_admin'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('scoring-rules', [ScoringRulesController::class, 'index'])->name('scoring.index');
        Route::put('scoring-rules', [ScoringRulesController::class, 'update'])->name('scoring.update');
        Route::post('scoring-rules/{rule}/toggle', [ScoringRulesController::class, 'toggleSignal'])->name('scoring.toggle');
        Route::post('scoring-rules/optimize', [ScoringRulesController::class, 'triggerOptimization'])->name('scoring.optimize');
        Route::post('scoring-rules/{version}/rollback', [ScoringRulesController::class, 'rollback'])->name('scoring.rollback');
    });

    // Super Admin org switcher
    Route::middleware(['role:super_admin'])->group(function () {
        Route::post('/switch-org', [OrgSwitcherController::class, 'switch'])->name('org.switch');
        Route::post('/reset-org', [OrgSwitcherController::class, 'reset'])->name('org.reset');
    });

    // Platform Branding, Organization Management & LLM Config (super_admin only)
    Route::middleware(['role:super_admin'])->prefix('settings')->name('settings.')->group(function () {
        // LLM Configuration
        Route::get('llm', [LlmConfigController::class, 'edit'])->name('llm.edit');
        Route::put('llm', [LlmConfigController::class, 'update'])->name('llm.update');
        Route::post('llm/test', [LlmConfigController::class, 'test'])->name('llm.test');

        Route::get('platform-branding', [PlatformBrandingController::class, 'edit'])->name('platformBranding');
        Route::put('platform-branding', [PlatformBrandingController::class, 'update'])->name('platformBranding.update');
        Route::put('platform-branding/org/{organization}', [PlatformBrandingController::class, 'updateOrgBranding'])->name('platformBranding.updateOrg');
        Route::put('platform-branding/org/{organization}/theme', [PlatformBrandingController::class, 'updateOrgTheme'])->name('platformBranding.updateOrgTheme');

        // Organization Management
        Route::get('organizations', [OrganizationManagementController::class, 'index'])->name('organizations.index');
        Route::get('organizations/create', [OrganizationManagementController::class, 'create'])->name('organizations.create');
        Route::post('organizations', [OrganizationManagementController::class, 'store'])->name('organizations.store');
        Route::get('organizations/{organization}/edit', [OrganizationManagementController::class, 'edit'])->name('organizations.edit');
        Route::put('organizations/{organization}', [OrganizationManagementController::class, 'update'])->name('organizations.update');

        // SSO Configuration
        Route::get('sso', [SsoConfigController::class, 'index'])->name('sso.index');
        Route::put('sso/{provider}', [SsoConfigController::class, 'update'])->name('sso.update');
    });

    // Intelligence (premium feature, resource_manager, management, org_admin, super_admin)
    Route::middleware(['role:resource_manager,management,org_admin,super_admin', 'premium'])
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
