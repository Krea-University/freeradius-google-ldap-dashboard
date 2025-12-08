<?php
/**
 * User Model
 *
 * Handles RADIUS user operations (radcheck table)
 */

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all users
     */
    public function getAll($limit = null, $offset = 0)
    {
        $sql = "SELECT username, attribute, op, value FROM radcheck ORDER BY username";
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }
        return $this->db->fetchAll($sql);
    }

    /**
     * Get user by username
     */
    public function getByUsername($username)
    {
        return $this->db->fetchOne(
            "SELECT * FROM radcheck WHERE username = ?",
            [$username]
        );
    }

    /**
     * Create new user
     */
    public function create($username, $password, $attribute = 'Cleartext-Password', $op = ':=')
    {
        return $this->db->execute(
            "INSERT INTO radcheck (username, attribute, op, value) VALUES (?, ?, ?, ?)",
            [$username, $attribute, $op, $password]
        );
    }

    /**
     * Update user password
     */
    public function updatePassword($username, $password)
    {
        return $this->db->execute(
            "UPDATE radcheck SET value = ? WHERE username = ? AND attribute = 'Cleartext-Password'",
            [$password, $username]
        );
    }

    /**
     * Delete user
     */
    public function delete($username)
    {
        return $this->db->execute(
            "DELETE FROM radcheck WHERE username = ?",
            [$username]
        );
    }

    /**
     * Check if user exists
     */
    public function exists($username)
    {
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM radcheck WHERE username = ?",
            [$username]
        );
        return $count > 0;
    }

    /**
     * Get total user count
     */
    public function getTotalCount()
    {
        return $this->db->fetchColumn("SELECT COUNT(DISTINCT username) FROM radcheck") ?: 0;
    }

    /**
     * Search users by username
     */
    public function search($query, $limit = 50)
    {
        return $this->db->fetchAll(
            "SELECT username, attribute, op, value FROM radcheck
             WHERE username LIKE ?
             ORDER BY username
             LIMIT ?",
            ['%' . $query . '%', $limit]
        );
    }
}
