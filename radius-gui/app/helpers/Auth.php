<?php
/**
 * Authentication Helper
 *
 * Handles user authentication and authorization
 */

class Auth
{
    /**
     * Start session if not already started
     */
    public static function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../config/database.php';
            $sessionConfig = $config['session'];

            session_name($sessionConfig['name']);
            session_set_cookie_params([
                'lifetime' => $sessionConfig['lifetime'],
                'path' => '/',
                'secure' => $sessionConfig['secure'],
                'httponly' => $sessionConfig['httponly'],
                'samesite' => $sessionConfig['samesite']
            ]);

            session_start();

            // Regenerate session ID periodically for security
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }

    /**
     * Attempt to log in user
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public static function login($username, $password)
    {
        $db = Database::getInstance();

        // Fetch operator by username
        $operator = $db->fetchOne(
            'SELECT * FROM operators WHERE username = ? LIMIT 1',
            [$username]
        );

        if (!$operator) {
            return false;
        }

        // Verify password (support legacy MD5 and SHA-256 hashes)
        // Note: password column is only varchar(32), so no bcrypt upgrade is possible
        $passwordValid = false;

        // Calculate hashes to compare
        $sha256_hash = hash('sha256', $password);
        $md5_hash = md5($password);
        $stored_hash = $operator['password'];

        // Try SHA-256 first (most common for legacy RADIUS)
        if ($sha256_hash === $stored_hash) {
            $passwordValid = true;
        }
        // Fallback to MD5 for very old passwords
        elseif ($md5_hash === $stored_hash) {
            $passwordValid = true;
        }

        if (!$passwordValid) {
            return false;
        }

        // Set session variables
        $_SESSION['user_id'] = $operator['id'];
        $_SESSION['username'] = $operator['username'];
        $_SESSION['firstname'] = $operator['firstname'] ?? '';
        $_SESSION['lastname'] = $operator['lastname'] ?? '';
        $_SESSION['email1'] = $operator['email1'] ?? '';
        $_SESSION['role'] = self::getUserRole($operator);
        $_SESSION['logged_in'] = true;

        // Check if password change is required
        if (isset($operator['must_change_password']) && $operator['must_change_password'] == 1) {
            $_SESSION['must_change_password'] = true;
        }

        return true;
    }

    /**
     * Determine user role
     */
    private static function getUserRole($operator)
    {
        $appConfig = require __DIR__ . '/../config/app.php';
        $roleMapping = $appConfig['role_mapping'];

        // Check role mapping by username
        if (isset($roleMapping[$operator['username']])) {
            return $roleMapping[$operator['username']];
        }

        // Check if operator has a role column
        if (isset($operator['role']) && !empty($operator['role'])) {
            return $operator['role'];
        }

        // Map based on permissions (createusers = superadmin capability)
        if (isset($operator['createusers']) && $operator['createusers'] == 1) {
            return 'netadmin';
        }

        // Default to helpdesk
        return 'helpdesk';
    }

    /**
     * Check if user is logged in
     */
    public static function check()
    {
        self::startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current user data
     */
    public static function user()
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'firstname' => $_SESSION['firstname'] ?? '',
            'lastname' => $_SESSION['lastname'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role'] ?? 'helpdesk',
            'fullname' => trim(($_SESSION['firstname'] ?? '') . ' ' . ($_SESSION['lastname'] ?? ''))
        ];
    }

    /**
     * Check if user has permission
     */
    public static function can($permission)
    {
        if (!self::check()) {
            return false;
        }

        $appConfig = require __DIR__ . '/../config/app.php';
        $role = $_SESSION['role'] ?? 'helpdesk';

        if (!isset($appConfig['roles'][$role])) {
            return false;
        }

        $permissions = $appConfig['roles'][$role]['permissions'];

        // Superadmin has all permissions
        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Check if user has specific role
     */
    public static function hasRole($role)
    {
        if (!self::check()) {
            return false;
        }

        return ($_SESSION['role'] ?? '') === $role;
    }

    /**
     * Logout user
     */
    public static function logout()
    {
        self::startSession();
        session_unset();
        session_destroy();
    }

    /**
     * Redirect to login if not authenticated
     */
    public static function requireLogin()
    {
        if (!self::check()) {
            header('Location: /public/index.php?page=login');
            exit;
        }
    }

    /**
     * Require specific permission
     */
    public static function requirePermission($permission)
    {
        self::requireLogin();

        if (!self::can($permission)) {
            http_response_code(403);
            die('Access Denied: You do not have permission to access this page.');
        }
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken()
    {
        self::startSession();

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token)
    {
        self::startSession();

        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
