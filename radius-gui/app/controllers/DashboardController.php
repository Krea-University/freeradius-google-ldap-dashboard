<?php
/**
 * Dashboard Controller
 * Main dashboard with system statistics and overview
 */

class DashboardController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        Auth::requirePermission('dashboard.view');
    }

    public function indexAction()
    {
        $pageTitle = 'Dashboard';

        // Get total users
        $totalUsers = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT username) as count FROM radacct"
        );

        // Get online users (active sessions)
        $onlineUsers = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM active_sessions"
        );

        // Get total authentication attempts today
        $authToday = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM radpostauth WHERE DATE(authdate) = CURDATE()"
        );

        // Get failed authentication attempts today
        $authFailedToday = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM radpostauth WHERE DATE(authdate) = CURDATE() AND reply = 'Access-Reject'"
        );

        // Get top users (by total session time today)
        $topUsers = $this->db->fetchAll(
            "SELECT username, 
                    COUNT(*) as session_count,
                    SUM(acctsessiontime) as total_time,
                    SUM(acctinputoctets) as input_bytes,
                    SUM(acctoutputoctets) as output_bytes
             FROM radacct 
             WHERE DATE(acctstarttime) = CURDATE()
             GROUP BY username 
             ORDER BY total_time DESC 
             LIMIT 10"
        );

        // Get recent authentication failures
        $recentFailures = $this->db->fetchAll(
            "SELECT username, reply, authdate FROM radpostauth 
             WHERE reply = 'Access-Reject' AND DATE(authdate) = CURDATE()
             ORDER BY authdate DESC 
             LIMIT 10"
        );

        // Get hourly traffic data for chart
        $hourlyTraffic = $this->db->fetchAll(
            "SELECT HOUR(acctstarttime) as hour,
                    COUNT(*) as session_count,
                    SUM(acctinputoctets) as input_bytes,
                    SUM(acctoutputoctets) as output_bytes
             FROM radacct 
             WHERE DATE(acctstarttime) = CURDATE()
             GROUP BY HOUR(acctstarttime)
             ORDER BY hour"
        );

        // Get system health info
        $systemHealth = [
            'radius_server' => 'Operational',
            'database' => 'Connected',
            'last_sync' => date('Y-m-d H:i:s')
        ];

        require APP_PATH . '/views/dashboard/index.php';
    }
}
