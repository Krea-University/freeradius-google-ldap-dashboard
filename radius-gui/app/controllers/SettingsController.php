<?php
/**
 * Settings Controller
 */

class SettingsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();

        // Only superadmin can access
        if (!Auth::hasRole('superadmin')) {
            http_response_code(403);
            die('Access Denied: Only superadmins can access settings.');
        }
    }

    public function indexAction()
    {
        $pageTitle = 'Settings';

        // Get database config
        $dbConfig = require APP_PATH . '/config/database.php';
        $appConfig = require APP_PATH . '/config/app.php';

        // Get database stats
        $dbStats = $this->getDbStats();

        require APP_PATH . '/views/settings/index.php';
    }

    private function getDbStats()
    {
        $stats = [
            'total_auth_records' => 0,
            'total_acct_records' => 0,
            'total_operators' => 0,
            'total_nas' => 0,
            'oldest_auth_date' => null,
            'newest_auth_date' => null,
            'database_size' => 0
        ];

        try {
            // Total auth records
            $stats['total_auth_records'] = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM radpostauth"
            ) ?? 0;

            // Total accounting records
            $stats['total_acct_records'] = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM radacct"
            ) ?? 0;

            // Total operators
            $stats['total_operators'] = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM operators"
            ) ?? 0;

            // Total NAS devices
            $stats['total_nas'] = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM nas"
            ) ?? 0;

            // Oldest auth record
            $stats['oldest_auth_date'] = $this->db->fetchColumn(
                "SELECT MIN(authdate) FROM radpostauth"
            );

            // Newest auth record
            $stats['newest_auth_date'] = $this->db->fetchColumn(
                "SELECT MAX(authdate) FROM radpostauth"
            );
        } catch (Exception $e) {
            error_log("Settings stats error: " . $e->getMessage());
        }

        return $stats;
    }
}
