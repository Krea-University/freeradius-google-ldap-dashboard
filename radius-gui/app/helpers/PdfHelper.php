<?php
/**
 * PDF Helper using TCPDF
 *
 * Install TCPDF: composer require tecnickcom/tcpdf
 * Or download from: https://github.com/tecnickcom/TCPDF
 */

class PdfHelper
{
    private static function initPdf($title, $orientation = 'P')
    {
        // Check if TCPDF is available
        if (!class_exists('TCPDF')) {
            // Try to load TCPDF from vendor or lib directory
            $tcpdfPaths = [
                BASE_PATH . '/vendor/tecnickcom/tcpdf/tcpdf.php',
                BASE_PATH . '/lib/tcpdf/tcpdf.php',
                '/usr/share/php/tcpdf/tcpdf.php'
            ];

            $loaded = false;
            foreach ($tcpdfPaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    $loaded = true;
                    break;
                }
            }

            if (!$loaded) {
                die('TCPDF library not found. Please install it via: composer require tecnickcom/tcpdf');
            }
        }

        // Create custom PDF class with timestamp footer
        $pdf = new class($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false) extends TCPDF {
            public function Footer() {
                // Position at 15 mm from bottom
                $this->SetY(-15);
                // Set font
                $this->SetFont('helvetica', 'I', 8);
                // Page number and timestamp
                $timestamp = date('Y-m-d H:i:s T');
                $pageText = 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
                $footerText = $pageText . ' | Generated: ' . $timestamp;
                // Print centered footer
                $this->Cell(0, 10, $footerText, 0, false, 'C', 0, '', 0, false, 'T', 'M');
            }
        };

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('RADIUS Reporting System');
        $pdf->SetTitle($title);
        $pdf->SetSubject($title);

        // Set default header data
        $pdf->SetHeaderData('', 0, 'RADIUS Reporting System', $title);

        // Set header and footer fonts
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);

        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        return $pdf;
    }

    public static function generateDailyAuthReport($date, $stats, $hourlyData)
    {
        $pdf = self::initPdf('Daily Authentication Summary - ' . $date);

        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Daily Authentication Summary', 0, 1, 'C');
        $pdf->Ln(5);

        // Date
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Date: ' . $date, 0, 1);
        $pdf->Ln(5);

        // Summary Statistics
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Summary Statistics', 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 11);
        $html = '<table border="1" cellpadding="5">
            <tr style="background-color:#f0f0f0;">
                <th width="50%"><b>Metric</b></th>
                <th width="50%"><b>Value</b></th>
            </tr>
            <tr>
                <td>Total Attempts</td>
                <td>' . number_format($stats['total_attempts']) . '</td>
            </tr>
            <tr>
                <td>Successful Logins</td>
                <td style="color:green;">' . number_format($stats['successful_logins']) . '</td>
            </tr>
            <tr>
                <td>Failed Logins</td>
                <td style="color:red;">' . number_format($stats['failed_logins']) . '</td>
            </tr>
            <tr>
                <td>Unique Users</td>
                <td>' . number_format($stats['unique_users']) . '</td>
            </tr>
            <tr style="background-color:#e8f4f8;">
                <td><b>Success Rate</b></td>
                <td><b>' . $stats['success_rate'] . '%</b></td>
            </tr>
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(10);

        // Hourly Breakdown
        if (!empty($hourlyData)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Hourly Breakdown', 0, 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            $html = '<table border="1" cellpadding="4">
                <tr style="background-color:#f0f0f0;">
                    <th><b>Hour</b></th>
                    <th><b>Total</b></th>
                    <th><b>Successful</b></th>
                    <th><b>Failed</b></th>
                </tr>';

            foreach ($hourlyData as $row) {
                $html .= '<tr>
                    <td>' . sprintf('%02d:00', $row['hour']) . '</td>
                    <td>' . number_format($row['attempts']) . '</td>
                    <td style="color:green;">' . number_format($row['successful']) . '</td>
                    <td style="color:red;">' . number_format($row['failed']) . '</td>
                </tr>';
            }

            $html .= '</table>';
            $pdf->writeHTML($html, true, false, true, false, '');
        }

        // Output PDF
        $filename = 'daily_auth_report_' . $date . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    public static function generateMonthlyUsageReport($month, $dailyData, $totals)
    {
        $pdf = self::initPdf('Monthly Usage Summary - ' . $month, 'L');

        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Monthly Usage Summary', 0, 1, 'C');
        $pdf->Ln(5);

        // Month
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Month: ' . date('F Y', strtotime($month . '-01')), 0, 1);
        $pdf->Ln(5);

        // Summary Totals
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Monthly Totals', 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 11);
        $html = '<table border="1" cellpadding="5">
            <tr style="background-color:#f0f0f0;">
                <th><b>Metric</b></th>
                <th><b>Value</b></th>
            </tr>
            <tr>
                <td>Total Sessions</td>
                <td>' . number_format($totals['total_sessions']) . '</td>
            </tr>
            <tr>
                <td>Unique Users</td>
                <td>' . number_format($totals['unique_users']) . '</td>
            </tr>
            <tr>
                <td>Total Online Time</td>
                <td>' . Utils::formatDuration($totals['total_online_time']) . '</td>
            </tr>
            <tr>
                <td>Total Data Transfer</td>
                <td>' . Utils::formatBytes($totals['total_data']) . '</td>
            </tr>
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(10);

        // Daily Breakdown
        if (!empty($dailyData)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Daily Breakdown', 0, 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 8);
            $html = '<table border="1" cellpadding="3">
                <tr style="background-color:#f0f0f0;">
                    <th><b>Date</b></th>
                    <th><b>Sessions</b></th>
                    <th><b>Unique Users</b></th>
                    <th><b>Online Time</b></th>
                    <th><b>Data Transfer</b></th>
                </tr>';

            foreach ($dailyData as $row) {
                $html .= '<tr>
                    <td>' . $row['date'] . '</td>
                    <td>' . number_format($row['total_sessions']) . '</td>
                    <td>' . number_format($row['unique_users']) . '</td>
                    <td>' . Utils::formatDuration($row['total_online_time']) . '</td>
                    <td>' . Utils::formatBytes($row['total_data']) . '</td>
                </tr>';
            }

            $html .= '</table>';
            $pdf->writeHTML($html, true, false, true, false, '');
        }

        // Output PDF
        $filename = 'monthly_usage_report_' . $month . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    public static function generateFailedLoginsReport($fromDate, $toDate, $threshold, $failedLogins)
    {
        $pdf = self::initPdf('Failed Login Report');

        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Failed Login Report', 0, 1, 'C');
        $pdf->Ln(5);

        // Date Range
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Period: ' . $fromDate . ' to ' . $toDate, 0, 1);
        $pdf->Cell(0, 8, 'Minimum Failures: ' . $threshold, 0, 1);
        $pdf->Ln(5);

        // Failed Logins Table
        if (!empty($failedLogins)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Users with Multiple Failed Attempts', 0, 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            $html = '<table border="1" cellpadding="4">
                <tr style="background-color:#f0f0f0;">
                    <th><b>Username</b></th>
                    <th><b>Error Type</b></th>
                    <th><b>Failures</b></th>
                    <th><b>First Failure</b></th>
                    <th><b>Last Failure</b></th>
                </tr>';

            foreach ($failedLogins as $row) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($row['username']) . '</td>
                    <td>' . htmlspecialchars(ucwords(str_replace('_', ' ', $row['error_type'] ?? 'Unknown'))) . '</td>
                    <td style="color:red;"><b>' . number_format($row['failure_count']) . '</b></td>
                    <td>' . date('Y-m-d H:i', strtotime($row['first_failure'])) . '</td>
                    <td>' . date('Y-m-d H:i', strtotime($row['last_failure'])) . '</td>
                </tr>';
            }

            $html .= '</table>';
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            $pdf->Cell(0, 8, 'No users with multiple failed attempts found.', 0, 1);
        }

        // Output PDF
        $filename = 'failed_logins_report_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    public static function generateSystemHealthReport($dbStats, $authStats, $errorBreakdown, $performanceMetrics, $topNasDevices, $recentAlerts)
    {
        $pdf = self::initPdf('System Health Report');

        $pdf->AddPage();

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'System Health Report', 0, 1, 'C');
        $pdf->Ln(5);

        // Report Date
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1);
        $pdf->Ln(5);

        // Database Statistics
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Database Statistics', 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 10);
        $html = '<table border="1" cellpadding="4">
            <tr style="background-color:#f0f0f0;">
                <th><b>Metric</b></th>
                <th><b>Value</b></th>
            </tr>
            <tr><td>Total Auth Records</td><td>' . number_format($dbStats['total_auth_records']) . '</td></tr>
            <tr><td>Total Accounting Records</td><td>' . number_format($dbStats['total_acct_records']) . '</td></tr>
            <tr><td>Total Users</td><td>' . number_format($dbStats['total_users']) . '</td></tr>
            <tr><td>Total Operators</td><td>' . number_format($dbStats['total_operators']) . '</td></tr>
            <tr><td>NAS Devices</td><td>' . number_format($dbStats['total_nas']) . '</td></tr>
            <tr><td>Online Sessions</td><td>' . number_format($dbStats['online_sessions']) . '</td></tr>
            <tr><td>Database Size</td><td>' . Utils::formatBytes($dbStats['database_size']) . '</td></tr>
        </table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);

        // Authentication Statistics (Last 24 Hours)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Authentication Statistics (Last 24 Hours)', 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 10);
        $successRate = number_format($authStats['success_rate'], 1) . '%';
        $html = '<table border="1" cellpadding="4">
            <tr style="background-color:#f0f0f0;">
                <th><b>Metric</b></th>
                <th><b>Value</b></th>
            </tr>
            <tr><td>Total Attempts</td><td>' . number_format($authStats['total_attempts']) . '</td></tr>
            <tr><td>Successful</td><td style="color:green;"><b>' . number_format($authStats['successful']) . '</b></td></tr>
            <tr><td>Failed</td><td style="color:red;"><b>' . number_format($authStats['failed']) . '</b></td></tr>
            <tr><td>Success Rate</td><td><b>' . $successRate . '</b></td></tr>
        </table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);

        // Error Breakdown
        if (!empty($errorBreakdown)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Error Breakdown (Last 24 Hours)', 0, 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 10);
            $totalErrors = array_sum(array_column($errorBreakdown, 'count'));
            $html = '<table border="1" cellpadding="4">
                <tr style="background-color:#f0f0f0;">
                    <th><b>Error Type</b></th>
                    <th><b>Count</b></th>
                    <th><b>Percentage</b></th>
                </tr>';

            foreach ($errorBreakdown as $error) {
                $percentage = ($error['count'] / $totalErrors) * 100;
                $html .= '<tr>
                    <td>' . htmlspecialchars(ucwords(str_replace('_', ' ', $error['error_type']))) . '</td>
                    <td>' . number_format($error['count']) . '</td>
                    <td>' . number_format($percentage, 1) . '%</td>
                </tr>';
            }

            $html .= '</table>';
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Ln(5);
        }

        // Performance Metrics (Last 7 Days)
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 8, 'Performance Metrics (Last 7 Days)', 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 10);
        $html = '<table border="1" cellpadding="4">
            <tr style="background-color:#f0f0f0;">
                <th><b>Metric</b></th>
                <th><b>Value</b></th>
            </tr>
            <tr><td>Avg Session Duration</td><td>' . Utils::formatDuration($performanceMetrics['avg_session_duration']) . '</td></tr>
            <tr><td>Avg Data Per Session</td><td>' . Utils::formatBytes($performanceMetrics['avg_data_per_session']) . '</td></tr>
            <tr><td>Peak Concurrent Users</td><td>' . number_format($performanceMetrics['peak_concurrent_users']) . '</td></tr>
        </table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);

        // Top NAS Devices
        if (!empty($topNasDevices)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Top NAS Devices (Last 7 Days)', 0, 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            $html = '<table border="1" cellpadding="4">
                <tr style="background-color:#f0f0f0;">
                    <th><b>NAS IP</b></th>
                    <th><b>Short Name</b></th>
                    <th><b>Users</b></th>
                    <th><b>Sessions</b></th>
                    <th><b>Total Data</b></th>
                </tr>';

            foreach ($topNasDevices as $nas) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($nas['nasname']) . '</td>
                    <td>' . htmlspecialchars($nas['shortname']) . '</td>
                    <td>' . number_format($nas['unique_users']) . '</td>
                    <td>' . number_format($nas['total_sessions']) . '</td>
                    <td>' . Utils::formatBytes($nas['total_data']) . '</td>
                </tr>';
            }

            $html .= '</table>';
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Ln(5);
        }

        // Recent System Alerts
        if (!empty($recentAlerts)) {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Recent System Alerts (Last Hour - 10+ Failures)', 0, 1);
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 9);
            $html = '<table border="1" cellpadding="4">
                <tr style="background-color:#ffcccc;">
                    <th><b>Username</b></th>
                    <th><b>Error Type</b></th>
                    <th><b>Failures</b></th>
                    <th><b>Last Failure</b></th>
                </tr>';

            foreach ($recentAlerts as $alert) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($alert['username']) . '</td>
                    <td>' . htmlspecialchars(ucwords(str_replace('_', ' ', $alert['error_type']))) . '</td>
                    <td style="color:red;"><b>' . number_format($alert['failure_count']) . '</b></td>
                    <td>' . date('Y-m-d H:i', strtotime($alert['last_failure'])) . '</td>
                </tr>';
            }

            $html .= '</table>';
            $pdf->writeHTML($html, true, false, true, false, '');
        } else {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'Recent System Alerts', 0, 1);
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 8, 'No critical alerts in the last hour. System is running smoothly.', 0, 1);
        }

        // Output PDF
        $filename = 'system_health_report_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }
}
