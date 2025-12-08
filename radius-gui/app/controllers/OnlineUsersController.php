<?php
/**
 * Online Users Controller
 */

class OnlineUsersController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        Auth::requirePermission('online_users.view');
    }

    public function indexAction()
    {
        $pageTitle = 'Online Users';

        // Get filters
        $username = Utils::get('username', '');
        $nasIp = Utils::get('nas_ip', '');

        // Build query - direct query instead of using view
        $sql = "SELECT
                    ra.radacctid,
                    ra.acctsessionid,
                    ra.username,
                    ra.nasipaddress,
                    ra.nasportid,
                    ra.framedipaddress,
                    ra.callingstationid AS device_mac,
                    ra.calledstationid AS wifi_network,
                    ra.acctstarttime,
                    ra.acctupdatetime,
                    NULL AS acctstoptime,
                    TIMESTAMPDIFF(SECOND, ra.acctstarttime, COALESCE(ra.acctupdatetime, NOW())) AS session_duration,
                    ra.acctinputoctets,
                    ra.acctoutputoctets,
                    (ra.acctinputoctets + ra.acctoutputoctets) AS total_bytes,
                    ra.acctterminatecause,
                    n.shortname AS nas_name,
                    n.description AS nas_description
                FROM radacct ra
                LEFT JOIN nas n ON ra.nasipaddress = n.nasname
                WHERE ra.acctstoptime IS NULL";
        $params = [];

        if (!empty($username)) {
            $sql .= " AND ra.username LIKE ?";
            $params[] = '%' . $username . '%';
        }

        if (!empty($nasIp)) {
            $sql .= " AND ra.nasipaddress = ?";
            $params[] = $nasIp;
        }

        $sql .= " ORDER BY ra.acctstarttime DESC";

        // Get online sessions
        $sessions = $this->db->fetchAll($sql, $params);

        // Get distinct NAS IPs for filter dropdown
        $nasList = $this->db->fetchAll(
            "SELECT DISTINCT nasipaddress FROM radacct WHERE acctstoptime IS NULL ORDER BY nasipaddress"
        );

        // Handle CSV export
        if (Utils::get('export') === 'csv') {
            $this->exportCsv($sessions);
        }

        require APP_PATH . '/views/online-users/index.php';
    }

    private function exportCsv($sessions)
    {
        $filename = 'online_users_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Username',
            'Device MAC',
            'IP Address',
            'NAS IP',
            'WiFi Network',
            'Session Start',
            'Duration',
            'Download (MB)',
            'Upload (MB)',
            'Total (MB)'
        ];

        $data = [];
        foreach ($sessions as $session) {
            $data[] = [
                $session['username'],
                $session['device_mac'] ?? '-',
                $session['framedipaddress'] ?? '-',
                $session['nasipaddress'],
                $session['wifi_network'] ?? '-',
                $session['acctstarttime'],
                Utils::formatDuration($session['session_duration']),
                number_format($session['acctinputoctets'] / 1048576, 2),
                number_format($session['acctoutputoctets'] / 1048576, 2),
                number_format($session['total_bytes'] / 1048576, 2)
            ];
        }

        Utils::exportCsv($filename, $headers, $data);
    }
}
