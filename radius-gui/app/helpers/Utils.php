<?php
/**
 * Utility Helper Functions
 */

class Utils
{
    /**
     * Format bytes to human-readable size
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        $bytes = max(0, (int)$bytes);

        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1024 ** $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Format seconds to HH:MM:SS
     *
     * @param int $seconds
     * @return string
     */
    public static function formatDuration($seconds)
    {
        $seconds = max(0, (int)$seconds);

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }

    /**
     * Escape HTML output
     */
    public static function e($value)
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize input
     */
    public static function sanitize($value)
    {
        return htmlspecialchars(strip_tags($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get request parameter
     */
    public static function get($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get POST parameter
     */
    public static function post($key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get request parameter (GET or POST)
     */
    public static function request($key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Redirect to URL
     */
    public static function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Flash message to session
     */
    public static function flash($key, $message)
    {
        Auth::startSession();
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Get and clear flash message
     */
    public static function getFlash($key)
    {
        Auth::startSession();

        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }

        return null;
    }

    /**
     * Paginate array or query results
     */
    public static function paginate($total, $perPage, $currentPage = 1)
    {
        $currentPage = max(1, (int)$currentPage);
        $totalPages = max(1, ceil($total / $perPage));
        $currentPage = min($currentPage, $totalPages);

        $offset = ($currentPage - 1) * $perPage;

        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'offset' => $offset,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'prev_page' => max(1, $currentPage - 1),
            'next_page' => min($totalPages, $currentPage + 1)
        ];
    }

    /**
     * Generate pagination links
     */
    public static function paginationLinks($pagination, $baseUrl)
    {
        $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

        // Previous
        $prevClass = $pagination['has_prev'] ? '' : ' disabled';
        $prevUrl = $pagination['has_prev'] ? $baseUrl . '&page=' . $pagination['prev_page'] : '#';
        $html .= '<li class="page-item' . $prevClass . '"><a class="page-link" href="' . $prevUrl . '">Previous</a></li>';

        // Page numbers (show max 5 pages around current)
        $start = max(1, $pagination['current_page'] - 2);
        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);

        if ($start > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>';
            if ($start > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for ($i = $start; $i <= $end; $i++) {
            $activeClass = $i === $pagination['current_page'] ? ' active' : '';
            $html .= '<li class="page-item' . $activeClass . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
        }

        if ($end < $pagination['total_pages']) {
            if ($end < $pagination['total_pages'] - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $pagination['total_pages'] . '">' . $pagination['total_pages'] . '</a></li>';
        }

        // Next
        $nextClass = $pagination['has_next'] ? '' : ' disabled';
        $nextUrl = $pagination['has_next'] ? $baseUrl . '&page=' . $pagination['next_page'] : '#';
        $html .= '<li class="page-item' . $nextClass . '"><a class="page-link" href="' . $nextUrl . '">Next</a></li>';

        $html .= '</ul></nav>';

        return $html;
    }

    /**
     * Export array to CSV
     */
    public static function exportCsv($filename, $headers, $data)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write headers
        fputcsv($output, $headers);

        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Get client IP address
     */
    public static function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }

    /**
     * Format date for display
     */
    public static function formatDate($date, $format = 'Y-m-d H:i:s')
    {
        if (empty($date)) {
            return '-';
        }

        try {
            $dt = new DateTime($date);
            return $dt->format($format);
        } catch (Exception $e) {
            return $date;
        }
    }

    /**
     * Calculate percentage
     */
    public static function percentage($part, $total, $precision = 1)
    {
        if ($total == 0) {
            return '0.0';
        }

        return number_format(($part / $total) * 100, $precision);
    }
}
