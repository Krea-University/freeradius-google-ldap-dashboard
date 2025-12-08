<?php
/**
 * Session Model
 *
 * Handles RADIUS accounting session operations (radacct table)
 */

class Session
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get online sessions
     */
    public function getOnline($limit = null)
    {
        $sql = "SELECT
                    radacctid,
                    acctsessionid,
                    username,
                    nasipaddress,
                    nasportid,
                    framedipaddress,
                    callingstationid,
                    acctstarttime,
                    acctsessiontime,
                    acctinputoctets,
                    acctoutputoctets
                FROM radacct
                WHERE acctstoptime IS NULL
                ORDER BY acctstarttime DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$limit]);
        }

        return $this->db->fetchAll($sql);
    }

    /**
     * Get session by ID
     */
    public function getById($radacctid)
    {
        return $this->db->fetchOne(
            "SELECT * FROM radacct WHERE radacctid = ?",
            [$radacctid]
        );
    }

    /**
     * Get sessions by username
     */
    public function getByUsername($username, $limit = 50, $offset = 0)
    {
        return $this->db->fetchAll(
            "SELECT * FROM radacct
             WHERE username = ?
             ORDER BY acctstarttime DESC
             LIMIT ? OFFSET ?",
            [$username, $limit, $offset]
        );
    }

    /**
     * Get active sessions count
     */
    public function getOnlineCount()
    {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM radacct WHERE acctstoptime IS NULL"
        ) ?: 0;
    }

    /**
     * Get total sessions count
     */
    public function getTotalCount()
    {
        return $this->db->fetchColumn("SELECT COUNT(*) FROM radacct") ?: 0;
    }

    /**
     * Get sessions by date range
     */
    public function getByDateRange($startDate, $endDate, $limit = null)
    {
        $sql = "SELECT * FROM radacct
                WHERE DATE(acctstarttime) BETWEEN ? AND ?
                ORDER BY acctstarttime DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$startDate, $endDate, $limit]);
        }

        return $this->db->fetchAll($sql, [$startDate, $endDate]);
    }

    /**
     * Get user session statistics
     */
    public function getUserStats($username)
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*) as total_sessions,
                SUM(acctsessiontime) as total_time,
                SUM(acctinputoctets) as total_download,
                SUM(acctoutputoctets) as total_upload,
                MIN(acctstarttime) as first_session,
                MAX(acctstarttime) as last_session
             FROM radacct
             WHERE username = ?",
            [$username]
        );
    }

    /**
     * Disconnect user session
     */
    public function disconnect($radacctid)
    {
        return $this->db->execute(
            "UPDATE radacct
             SET acctstoptime = NOW(),
                 acctterminatecause = 'Admin-Reset'
             WHERE radacctid = ? AND acctstoptime IS NULL",
            [$radacctid]
        );
    }

    /**
     * Get top users by session time
     */
    public function getTopUsers($limit = 10, $days = 7)
    {
        return $this->db->fetchAll(
            "SELECT
                username,
                COUNT(*) as session_count,
                SUM(acctsessiontime) as total_time,
                SUM(acctinputoctets) as input_bytes,
                SUM(acctoutputoctets) as output_bytes
             FROM radacct
             WHERE acctstarttime >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY username
             ORDER BY total_time DESC
             LIMIT ?",
            [$days, $limit]
        );
    }
}
