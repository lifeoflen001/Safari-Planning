<?php

return [
    'invitation_ttl_hours' => (int) env('INVITATION_TTL_HOURS', 48),
    'temporary_password_ttl_hours' => (int) env('TEMPORARY_PASSWORD_TTL_HOURS', 24),
    'temporary_password_max_attempts' => (int) env('TEMPORARY_PASSWORD_MAX_ATTEMPTS', 5),

    'modules' => [
        'planner-overview' => ['label' => 'Overview', 'permission' => 'planner.view'],
        'planner-calendar' => ['label' => 'Planning calendar', 'permission' => 'planner.view'],
        'planner-tasks' => ['label' => 'Tasks', 'permission' => 'tasks.view'],
        'planner-bookings' => ['label' => 'Safari board', 'permission' => 'planner.view'],
        'planner-transfers' => ['label' => 'Transfers', 'permission' => 'transfers.view'],
        'planner-guides' => ['label' => 'Guides', 'permission' => 'guides.view'],
        'planner-vehicles' => ['label' => 'Vehicles', 'permission' => 'vehicles.view'],
        'planner-reports' => ['label' => 'Reports & exports', 'permission' => 'reports.view'],
        'planner-documents' => ['label' => 'Documents', 'permission' => 'documents.view'],
        'planner-team' => ['label' => 'Team & access', 'permission' => 'users.view'],
        'planner-settings' => ['label' => 'Workspace settings', 'permission' => 'workspace.view'],
    ],

    'permissions' => [
        'planner.view' => ['label' => 'View planning board', 'group' => 'Planning'],
        'planner.edit' => ['label' => 'Edit planning records', 'group' => 'Planning'],
        'planner.comment' => ['label' => 'Comment on planning records', 'group' => 'Planning'],
        'planner.import' => ['label' => 'Import planning data', 'group' => 'Planning'],
        'tasks.view' => ['label' => 'View operational tasks', 'group' => 'Operations'],
        'tasks.manage' => ['label' => 'Create and manage tasks', 'group' => 'Operations'],
        'transfers.view' => ['label' => 'View transfers', 'group' => 'Operations'],
        'transfers.edit' => ['label' => 'Create and edit transfers', 'group' => 'Operations'],
        'guides.view' => ['label' => 'View guides', 'group' => 'Resources'],
        'guides.edit' => ['label' => 'Manage guides', 'group' => 'Resources'],
        'vehicles.view' => ['label' => 'View vehicles', 'group' => 'Resources'],
        'vehicles.edit' => ['label' => 'Manage vehicles', 'group' => 'Resources'],
        'reports.view' => ['label' => 'View reports', 'group' => 'Insights'],
        'documents.view' => ['label' => 'View documents', 'group' => 'Insights'],
        'documents.edit' => ['label' => 'Upload documents', 'group' => 'Insights'],
        'users.view' => ['label' => 'View team members', 'group' => 'Administration'],
        'users.manage' => ['label' => 'Invite and manage users', 'group' => 'Administration'],
        'roles.manage' => ['label' => 'Manage roles and permissions', 'group' => 'Administration'],
        'workspace.view' => ['label' => 'View workspace settings', 'group' => 'Administration'],
        'workspace.manage' => ['label' => 'Manage workspace settings', 'group' => 'Administration'],
        'audit.view' => ['label' => 'Review access history', 'group' => 'Security'],
        'sessions.revoke' => ['label' => 'Revoke user sessions', 'group' => 'Security'],
    ],

    'role_permissions' => [
        'admin' => '*',
        'editor' => [
            'planner.view', 'planner.edit', 'planner.comment', 'planner.import',
            'tasks.view', 'tasks.manage',
            'transfers.view', 'transfers.edit', 'guides.view', 'guides.edit',
            'vehicles.view', 'vehicles.edit', 'reports.view', 'documents.view', 'documents.edit',
        ],
        'commenter' => ['planner.view', 'planner.comment', 'tasks.view', 'transfers.view', 'guides.view', 'vehicles.view', 'documents.view'],
        'viewer' => ['planner.view', 'tasks.view', 'transfers.view', 'guides.view', 'vehicles.view', 'documents.view'],
    ],
];
