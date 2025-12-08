<?php
/**
 * Login Controller
 */

class LoginController
{
    public function __construct()
    {
        // Ensure session is started
        Auth::startSession();
    }

    public function indexAction()
    {
        // If already logged in, redirect to dashboard
        if (Auth::check()) {
            Utils::redirect('index.php?page=dashboard');
        }

        $error = null;

        // Handle login form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = Utils::post('username');
            $password = Utils::post('password');

            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password.';
            } else {
                $result = Auth::login($username, $password);
                if ($result) {
                    // Successful login
                    Utils::redirect('index.php?page=dashboard');
                } else {
                    $error = 'Invalid username or password.';
                }
            }
        }

        // Show login view
        require APP_PATH . '/views/auth/login.php';
    }

    public function logoutAction()
    {
        Auth::logout();
        Utils::redirect('index.php?page=login');
    }
}
