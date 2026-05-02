<?php

/**
 * Module catalog — single source of truth for what modules exist
 * and what each industry template enables by default.
 *
 * Adding a new module:
 *   1. Add a key here under 'modules' with label + description
 *   2. Add it to one or more 'templates' below
 *   3. Add the middleware to its routes: ->middleware('module:your_key')
 *   4. Wrap sidebar items: @if($org?->canUseModule('your_key'))
 */

return [

    'modules' => [
        'hiring' => [
            'label'       => 'Hiring & ATS',
            'description' => 'Job postings, candidates, resumes, AI resume analysis.',
            'icon'        => 'briefcase',
        ],
        'interviews' => [
            'label'       => 'Live Interviews',
            'description' => 'Real-time interview transcription, AI questions, evaluation, summaries.',
            'icon'        => 'mic',
        ],
        'work_signals' => [
            'label'       => 'Work Pulse / Signal Intelligence',
            'description' => 'Engagement dashboards from Jira/Slack/Teams/Azure DevOps/Zoho work data.',
            'icon'        => 'activity',
        ],
        'resource_allocation' => [
            'label'       => 'Resource Allocation',
            'description' => 'Project-to-employee matching with AI scoring.',
            'icon'        => 'users',
        ],
        'crm' => [
            'label'       => 'Sales Pulse (CRM)',
            'description' => 'Sales activity dashboards from Salesforce, HubSpot, Zoho CRM. Account allocation.',
            'icon'        => 'trending-up',
        ],
        'customer_support' => [
            'label'       => 'Support Pulse',
            'description' => 'Customer support performance from Zendesk, Freshdesk. Ticket allocation.',
            'icon'        => 'headphones',
        ],
    ],

    'templates' => [
        'software' => [
            'label'    => 'Software / IT Services',
            'modules'  => ['hiring', 'interviews', 'work_signals', 'resource_allocation'],
        ],
        'sales' => [
            'label'    => 'Sales-Driven Organization',
            'modules'  => ['hiring', 'interviews', 'crm'],
        ],
        'support' => [
            'label'    => 'Customer Support / BPO',
            'modules'  => ['hiring', 'interviews', 'customer_support'],
        ],
        'hybrid' => [
            'label'    => 'Hybrid (IT + Sales)',
            'modules'  => ['hiring', 'interviews', 'work_signals', 'resource_allocation', 'crm'],
        ],
        'custom' => [
            'label'    => 'Custom — Pick Modules Manually',
            'modules'  => ['hiring'], // minimal default; admin picks the rest
        ],
    ],

    // Modules that legacy orgs (NULL enabled_modules) get — keeps backward compat.
    'legacy_default' => ['hiring', 'interviews', 'work_signals', 'resource_allocation'],
];
