<?php
/**
 * Database Configuration
 *
 * Configure database connections for the RADIUS Reporting GUI
 */

// Helper function to get environment variable with fallback
if (!function_exists('getConfigEnv')) {
    function getConfigEnv($key, $default = '') {
        // Try multiple sources for environment variables
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }
}

return [
    // Main RADIUS database connection
    'radius' => [
        'host' => getConfigEnv('DB_HOST', 'localhost'),
        'port' => getConfigEnv('DB_PORT', 3306),
        'database' => getConfigEnv('DB_NAME', 'radius'),
        'username' => getConfigEnv('DB_USER', 'radius'),
        'password' => getConfigEnv('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],

    // Application timezone
    'timezone' => getConfigEnv('APP_TIMEZONE', 'Asia/Kolkata'),

    // Session configuration
    'session' => [
        'name' => 'RADIUS_GUI_SESSION',
        'lifetime' => 7200, // 2 hours
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]
];
