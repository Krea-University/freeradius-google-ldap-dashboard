<?php
/**
 * Application Configuration
 */

return [
    'name' => 'RADIUS Reporting',
    'version' => '1.0.0',

    // Base URL (set in .env or auto-detect)
    'base_url' => getenv('APP_URL') ?: '',

    // Default pagination
    'pagination' => [
        'per_page' => 25,
        'options' => [10, 25, 50, 100]
    ],

    // Default date range for reports (days)
    'default_date_range' => 7,

    // Role definitions
    'roles' => [
        'superadmin' => [
            'name' => 'Super Administrator',
            'permissions' => ['*'] // All permissions
        ],
        'netadmin' => [
            'name' => 'Network Administrator',
            'permissions' => [
                'dashboard.view',
                'online_users.view',
                'auth_log.view',
                'user_history.view',
                'top_users.view',
                'nas_usage.view',
                'error_analytics.view',
                'reports.view'
            ]
        ],
        'helpdesk' => [
            'name' => 'Helpdesk',
            'permissions' => [
                'dashboard.view',
                'online_users.view',
                'auth_log.view',
                'user_history.view'
            ]
        ]
    ],

    // Default role mapping (username => role)
    'role_mapping' => [
        'administrator' => 'superadmin',
        'admin' => 'superadmin'
        // Other users default to 'helpdesk' or based on operators table fields
    ],

    // CSV export settings
    'export' => [
        'max_rows' => 10000,
        'delimiter' => ',',
        'enclosure' => '"'
    ]
];
