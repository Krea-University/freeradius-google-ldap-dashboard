<?php
/**
 * Bootstrap - Load environment variables from Apache SetEnv directives
 *
 * This file is automatically prepended to every PHP request via php.ini configuration
 * It ensures environment variables from docker-compose are available to the application
 */

// Populate $_ENV from $_SERVER (Apache SetEnv directives)
$envVars = [
    'DB_HOST',
    'DB_PORT',
    'DB_NAME',
    'DB_USER',
    'DB_PASSWORD',
    'DB_ROOT_PASSWORD',
    'APP_TIMEZONE',
    'APP_URL',
    'APP_ENV',
    'APP_DEBUG',
    'LDAP_SERVER',
    'LDAP_IDENTITY',
    'LDAP_PASSWORD',
    'LDAP_BASE_DN',
    'LDAP_BIND_DN',
    'RADIUS_SECRET',
    'ADMIN_USERNAME',
    'ADMIN_PASSWORD',
];

foreach ($envVars as $var) {
    if (!isset($_ENV[$var]) && isset($_SERVER[$var])) {
        $_ENV[$var] = $_SERVER[$var];
    }
}
