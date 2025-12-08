<?php
/**
 * User Session History Controller
 */

class UserHistoryController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        Auth::requirePermission('user_history.view');
    }

    public function indexAction()
    {
        $pageTitle = 'User Session History';

        // Get form inputs
        $username = Utils::get('username', '');
        $fromDate = Utils::get('from_date', date('Y-m-d', strtotime('-7 days')));
        $toDate = Utils::get('to_date', date('Y-m-d'));

        $sessions = [];
        $summary = null;

        if (!empty($username)) {
            // Get user sessions
            $sessions = $this->db->fetchAll(
                "SELECT
                    radacctid,
                    acctsessionid,
                    acctstarttime,
                    acctstoptime,
                    acctsessiontime,
                    callingstationid,
                    framedipaddress,
                    nasipaddress,
                    acctinputoctets,
                    acctoutputoctets,
                    (acctinputoctets + acctoutputoctets) as total_bytes
                FROM radacct
                WHERE username = ?
                  AND DATE(acctstarttime) BETWEEN ? AND ?
                ORDER BY acctstarttime DESC",
                [$username, $fromDate, $toDate]
            );

            // Calculate summary
            $totalSessions = count($sessions);
            $totalOnlineTime = array_sum(array_column($sessions, 'acctsessiontime'));
            $totalDownload = array_sum(array_column($sessions, 'acctinputoctets'));
            $totalUpload = array_sum(array_column($sessions, 'acctoutputoctets'));
            $totalData = $totalDownload + $totalUpload;

            $summary = [
                'total_sessions' => $totalSessions,
                'total_online_time' => $totalOnlineTime,
                'total_download' => $totalDownload,
                'total_upload' => $totalUpload,
                'total_data' => $totalData
            ];
        }

        // Handle CSV export
        if (Utils::get('export') === 'csv' && !empty($sessions)) {
            $this->exportCsv($username, $sessions);
        }

        require APP_PATH . '/views/user-history/index.php';
    }

    private function exportCsv($username, $sessions)
    {
        $filename = 'user_history_' . $username . '_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Start Time',
            'Stop Time',
            'Duration',
            'Device MAC',
            'IP Address',
            'NAS IP',
            'Download (MB)',
            'Upload (MB)',
            'Total (MB)'
        ];

        $data = [];
        foreach ($sessions as $session) {
            $data[] = [
                $session['acctstarttime'],
                $session['acctstoptime'] ?? 'Active',
                Utils::formatDuration($session['acctsessiontime']),
                $session['callingstationid'] ?? '-',
                $session['framedipaddress'] ?? '-',
                $session['nasipaddress'],
                number_format($session['acctinputoctets'] / 1048576, 2),
                number_format($session['acctoutputoctets'] / 1048576, 2),
                number_format($session['total_bytes'] / 1048576, 2)
            ];
        }

        Utils::exportCsv($filename, $headers, $data);
    }
}
