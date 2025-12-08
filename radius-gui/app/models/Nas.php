<?php
/**
 * NAS Model
 *
 * Handles Network Access Server (NAS) device operations
 */

class Nas
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all NAS devices
     */
    public function getAll()
    {
        return $this->db->fetchAll(
            "SELECT * FROM nas ORDER BY shortname"
        );
    }

    /**
     * Get NAS by ID
     */
    public function getById($id)
    {
        return $this->db->fetchOne(
            "SELECT * FROM nas WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get NAS by IP address
     */
    public function getByIp($nasname)
    {
        return $this->db->fetchOne(
            "SELECT * FROM nas WHERE nasname = ?",
            [$nasname]
        );
    }

    /**
     * Create new NAS device
     */
    public function create($data)
    {
        return $this->db->execute(
            "INSERT INTO nas (nasname, shortname, type, secret, description)
             VALUES (?, ?, ?, ?, ?)",
            [
                $data['nasname'],
                $data['shortname'],
                $data['type'] ?? 'other',
                $data['secret'],
                $data['description'] ?? null
            ]
        );
    }

    /**
     * Update NAS device
     */
    public function update($id, $data)
    {
        return $this->db->execute(
            "UPDATE nas
             SET shortname = ?, type = ?, secret = ?, description = ?
             WHERE id = ?",
            [
                $data['shortname'],
                $data['type'] ?? 'other',
                $data['secret'],
                $data['description'] ?? null,
                $id
            ]
        );
    }

    /**
     * Delete NAS device
     */
    public function delete($id)
    {
        return $this->db->execute(
            "DELETE FROM nas WHERE id = ?",
            [$id]
        );
    }

    /**
     * Check if NAS exists
     */
    public function exists($nasname)
    {
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM nas WHERE nasname = ?",
            [$nasname]
        );
        return $count > 0;
    }

    /**
     * Get NAS activity statistics
     */
    public function getActivityStats($nasname, $days = 7)
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*) as total_sessions,
                COUNT(DISTINCT username) as unique_users,
                SUM(acctsessiontime) as total_time,
                SUM(acctinputoctets + acctoutputoctets) as total_data
             FROM radacct
             WHERE nasipaddress = ?
               AND acctstarttime >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$nasname, $days]
        );
    }

    /**
     * Get total NAS count
     */
    public function getTotalCount()
    {
        return $this->db->fetchColumn("SELECT COUNT(*) FROM nas") ?: 0;
    }
}
