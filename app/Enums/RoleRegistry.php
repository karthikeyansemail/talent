<?php

namespace App\Enums;

class RoleRegistry
{
    /**
     * Master role list. To add a new role:
     * 1. Add an entry here
     * 2. Run migration if needed (role_user uses string column, no enum change needed)
     * 3. Done — UI, validation, middleware all read from this array
     */
    public const ROLES = [
        'super_admin' => [
            'label'       => 'Super Admin',
            'description' => 'Platform-wide administrator with access to all organizations',
            'assignable'  => false,
            'pillars'     => ['hiring', 'resource', 'settings', 'platform'],
        ],
        'org_admin' => [
            'label'       => 'Org Admin',
            'description' => 'Organization administrator — full access within their org',
            'assignable'  => true,
            'pillars'     => ['hiring', 'resource', 'settings'],
        ],
        'hr_manager' => [
            'label'       => 'HR Manager',
            'description' => 'Manage job postings, candidates, applications, and hiring reports',
            'assignable'  => true,
            'pillars'     => ['hiring'],
        ],
        'hiring_manager' => [
            'label'       => 'Hiring Manager',
            'description' => 'Review candidates and provide interview feedback',
            'assignable'  => true,
            'pillars'     => ['hiring'],
        ],
        'resource_manager' => [
            'label'       => 'Resource Manager',
            'description' => 'Manage employees, projects, and resource allocation',
            'assignable'  => true,
            'pillars'     => ['resource'],
        ],
        'employee' => [
            'label'       => 'Employee',
            'description' => 'Basic access — dashboard and own profile only',
            'assignable'  => true,
            'pillars'     => [],
        ],
        'interviewer' => [
            'label'       => 'Interviewer',
            'description' => 'Conduct interviews and submit feedback for assigned candidates',
            'assignable'  => true,
            'pillars'     => ['hiring'],
        ],
    ];

    /** Roles that org_admin can assign to users */
    public static function assignable(): array
    {
        return array_filter(self::ROLES, fn($r) => $r['assignable']);
    }

    /** Get label for a role key */
    public static function label(string $key): string
    {
        return self::ROLES[$key]['label'] ?? ucwords(str_replace('_', ' ', $key));
    }

    /** All valid role keys for validation */
    public static function validKeys(): array
    {
        return array_keys(self::ROLES);
    }

    /** Assignable role keys (for validation in user management) */
    public static function assignableKeys(): array
    {
        return array_keys(self::assignable());
    }
}
