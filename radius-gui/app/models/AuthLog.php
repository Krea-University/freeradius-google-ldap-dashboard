<?php
/**
 * AuthLog Model
 *
 * Handles authentication log operations (radpostauth table)
 */

class AuthLog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get recent authentication attempts
     */
    public function getRecent($limit = 100, $offset = 0)
    {
        return $this->db->fetchAll(
            "SELECT * FROM radpostauth
             ORDER BY authdate DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Get authentication attempts by username
     */
    public function getByUsername($username, $limit = 50)
    {
        return $this->db->fetchAll(
            "SELECT * FROM radpostauth
             WHERE username = ?
             ORDER BY authdate DESC
             LIMIT ?",
            [$username, $limit]
        );
    }

    /**
     * Get failed authentication attempts
     */
    public function getFailedAttempts($limit = 100, $hours = 24)
    {
        return $this->db->fetchAll(
            "SELECT * FROM radpostauth
             WHERE reply != 'Access-Accept'
               AND authdate >= DATE_SUB(NOW(), INTERVAL ? HOUR)
             ORDER BY authdate DESC
             LIMIT ?",
            [$hours, $limit]
        );
    }

    /**
     * Get authentication attempts by error type
     */
    public function getByErrorType($errorType, $limit = 100)
    {
        return $this->db->fetchAll(
            "SELECT * FROM radpostauth
             WHERE error_type = ?
             ORDER BY authdate DESC
             LIMIT ?",
            [$errorType, $limit]
        );
    }

    /**
     * Get authentication statistics
     */
    public function getStats($hours = 24)
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*) as total_attempts,
                SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN reply != 'Access-Accept' THEN 1 ELSE 0 END) as failed,
                COUNT(DISTINCT username) as unique_users
             FROM radpostauth
             WHERE authdate >= DATE_SUB(NOW(), INTERVAL ? HOUR)",
            [$hours]
        );
    }

    /**
     * Get error type breakdown
     */
    public function getErrorBreakdown($hours = 24)
    {
        return $this->db->fetchAll(
            "SELECT
                error_type,
                COUNT(*) as count
             FROM radpostauth
             WHERE reply != 'Access-Accept'
               AND error_type IS NOT NULL
               AND authdate >= DATE_SUB(NOW(), INTERVAL ? HOUR)
             GROUP BY error_type
             ORDER BY count DESC",
            [$hours]
        );
    }

    /**
     * Get authentication attempts by date range
     */
    public function getByDateRange($startDate, $endDate, $limit = null)
    {
        $sql = "SELECT * FROM radpostauth
                WHERE DATE(authdate) BETWEEN ? AND ?
                ORDER BY authdate DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$startDate, $endDate, $limit]);
        }

        return $this->db->fetchAll($sql, [$startDate, $endDate]);
    }

    /**
     * Get total authentication count
     */
    public function getTotalCount()
    {
        return $this->db->fetchColumn("SELECT COUNT(*) FROM radpostauth") ?: 0;
    }

    /**
     * Get users with most failed attempts
     */
    public function getTopFailedUsers($limit = 10, $hours = 24)
    {
        return $this->db->fetchAll(
            "SELECT
                username,
                error_type,
                COUNT(*) as failure_count,
                MAX(authdate) as last_attempt
             FROM radpostauth
             WHERE reply != 'Access-Accept'
               AND authdate >= DATE_SUB(NOW(), INTERVAL ? HOUR)
             GROUP BY username, error_type
             ORDER BY failure_count DESC
             LIMIT ?",
            [$hours, $limit]
        );
    }

    /**
     * Search authentication logs
     */
    public function search($query, $limit = 100)
    {
        return $this->db->fetchAll(
            "SELECT * FROM radpostauth
             WHERE username LIKE ?
                OR reply_message LIKE ?
             ORDER BY authdate DESC
             LIMIT ?",
            ['%' . $query . '%', '%' . $query . '%', $limit]
        );
    }
}
