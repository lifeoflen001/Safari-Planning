<?php

return [
    'Operations' => [
        [
            'id' => 'planner-overview',
            'label' => 'Overview',
            'icon' => 'grid',
            'route' => 'planner',
            'roles' => ['admin', 'editor', 'commenter', 'viewer'],
        ],
        [
            'id' => 'planner-calendar',
            'label' => 'Planning calendar',
            'icon' => 'calendar',
            'route' => 'planner.calendar',
            'roles' => ['admin', 'editor', 'commenter', 'viewer'],
        ],
        [
            'id' => 'planner-tasks',
            'label' => 'Tasks',
            'icon' => 'checklist',
            'route' => 'planner.tasks',
            'roles' => ['admin', 'editor', 'commenter', 'viewer'],
        ],
        [
            'id' => 'planner-bookings',
            'label' => 'Safari board',
            'icon' => 'suitcase',
            'route' => 'planner.bookings',
            'roles' => ['admin', 'editor', 'commenter', 'viewer'],
        ],
        [
            'id' => 'planner-transfers',
            'label' => 'Transfers',
            'icon' => 'plane',
            'route' => 'planner.transfers',
            'roles' => ['admin', 'editor', 'commenter', 'viewer'],
        ],
        [
            'id' => 'planner-guides',
            'label' => 'Guides',
            'icon' => 'users',
            'route' => 'planner.guides',
            'roles' => ['admin', 'editor', 'commenter', 'viewer'],
        ],
        [
            'id' => 'planner-vehicles',
            'label' => 'Vehicles',
            'icon' => 'car',
            'route' => 'planner.vehicles',
            'roles' => ['admin', 'editor', 'commenter', 'viewer'],
        ],
        [
            'id' => 'planner-reports',
            'label' => 'Reports & exports',
            'icon' => 'chart',
            'route' => 'planner.reports',
            'roles' => ['admin', 'editor'],
        ],
        [
            'id' => 'planner-documents',
            'label' => 'Documents',
            'icon' => 'file',
            'route' => 'planner.documents',
            'roles' => ['admin', 'editor'],
        ],
    ],
    'Administration' => [
        [
            'id' => 'planner-team',
            'label' => 'Team & access',
            'icon' => 'users',
            'route' => 'planner.team',
            'roles' => ['admin'],
        ],
        [
            'id' => 'planner-settings',
            'label' => 'Workspace settings',
            'icon' => 'settings',
            'route' => 'settings.workspace',
            'roles' => ['admin'],
        ],
    ],
    'Account' => [
        [
            'id' => 'account-profile',
            'label' => 'My Profile',
            'icon' => 'grid',
            'route' => 'settings.profile',
            'roles' => ['admin', 'editor', 'commenter', 'viewer'],
        ],
    ],
];
