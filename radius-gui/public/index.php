<?php
/**
 * RADIUS Reporting GUI - Main Entry Point
 *
 * Front controller for all requests
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// Load .env file from root directory
$envFile = dirname(dirname(dirname(__FILE__))) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv($key . '=' . $value);
            }
        }
    }
}

// Autoload helpers and classes
require_once APP_PATH . '/helpers/Database.php';
require_once APP_PATH . '/helpers/Auth.php';
require_once APP_PATH . '/helpers/Utils.php';

// Start session
Auth::startSession();

// Check if user must change password (except on login, logout, and change-password pages)
$page = Utils::get('page', 'dashboard');
$action = Utils::get('action', 'index');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'] === true) {
        // Allow access only to change-password page and logout
        if ($page !== 'users' || $action !== 'change-password') {
            if ($page !== 'login' && $action !== 'logout') {
                header('Location: index.php?page=users&action=change-password');
                exit;
            }
        }
    }
}

// Convert hyphens to camelCase for actions (e.g., daily-auth -> dailyAuth)
$actionParts = explode('-', $action);
$actionCamelCase = $actionParts[0];
for ($i = 1; $i < count($actionParts); $i++) {
    $actionCamelCase .= ucfirst($actionParts[$i]);
}

// Convert hyphens to CamelCase for page/controller names (e.g., online-users -> OnlineUsers)
$pageParts = explode('-', $page);
$pageClassName = '';
foreach ($pageParts as $part) {
    $pageClassName .= ucfirst($part);
}

// Route to appropriate controller
$controllerFile = APP_PATH . '/controllers/' . $pageClassName . 'Controller.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;

    $controllerClass = $pageClassName . 'Controller';

    if (class_exists($controllerClass)) {
        $controller = new $controllerClass();

        $methodName = $actionCamelCase . 'Action';

        if (method_exists($controller, $methodName)) {
            $controller->$methodName();
        } else {
            // Default to index action
            $controller->indexAction();
        }
    } else {
        die('Controller class not found');
    }
} else {
    // Default to dashboard if page not found
    if ($page !== 'login') {
        header('Location: index.php?page=dashboard');
        exit;
    }

    // Show login page
    require_once APP_PATH . '/controllers/LoginController.php';
    $controller = new LoginController();
    $controller->indexAction();
}
