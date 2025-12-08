<?php
/**
 * Operator Model
 *
 * Handles dashboard operator/admin user operations
 */

class Operator
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get operator by username
     */
    public function getByUsername($username)
    {
        return $this->db->fetchOne(
            "SELECT * FROM operators WHERE username = ?",
            [$username]
        );
    }

    /**
     * Get operator by ID
     */
    public function getById($id)
    {
        return $this->db->fetchOne(
            "SELECT * FROM operators WHERE id = ?",
            [$id]
        );
    }

    /**
     * Get all operators
     */
    public function getAll()
    {
        return $this->db->fetchAll(
            "SELECT id, username, firstname, lastname, email, createusers,
                    creationdate, updatedate, creationby, updateby
             FROM operators
             ORDER BY username"
        );
    }

    /**
     * Create new operator
     */
    public function create($data)
    {
        return $this->db->execute(
            "INSERT INTO operators
             (username, password, firstname, lastname, email, createusers, creationby, creationdate)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['username'],
                $data['password'],
                $data['firstname'] ?? null,
                $data['lastname'] ?? null,
                $data['email'] ?? null,
                $data['createusers'] ?? 0,
                $data['creationby'] ?? 'system'
            ]
        );
    }

    /**
     * Update operator
     */
    public function update($id, $data)
    {
        $sets = [];
        $params = [];

        if (isset($data['firstname'])) {
            $sets[] = "firstname = ?";
            $params[] = $data['firstname'];
        }
        if (isset($data['lastname'])) {
            $sets[] = "lastname = ?";
            $params[] = $data['lastname'];
        }
        if (isset($data['email'])) {
            $sets[] = "email = ?";
            $params[] = $data['email'];
        }
        if (isset($data['createusers'])) {
            $sets[] = "createusers = ?";
            $params[] = $data['createusers'];
        }
        if (isset($data['password'])) {
            $sets[] = "password = ?";
            $params[] = $data['password'];
        }

        $sets[] = "updatedate = NOW()";
        if (isset($data['updateby'])) {
            $sets[] = "updateby = ?";
            $params[] = $data['updateby'];
        }

        $params[] = $id;

        return $this->db->execute(
            "UPDATE operators SET " . implode(', ', $sets) . " WHERE id = ?",
            $params
        );
    }

    /**
     * Delete operator
     */
    public function delete($id)
    {
        return $this->db->execute(
            "DELETE FROM operators WHERE id = ?",
            [$id]
        );
    }

    /**
     * Check if operator exists
     */
    public function exists($username)
    {
        $count = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM operators WHERE username = ?",
            [$username]
        );
        return $count > 0;
    }

    /**
     * Verify operator password
     */
    public function verifyPassword($username, $password)
    {
        $operator = $this->getByUsername($username);
        if (!$operator) {
            return false;
        }

        // Check if password is hashed
        if (password_get_info($operator['password'])['algo'] !== null) {
            return password_verify($password, $operator['password']);
        }

        // Legacy plain text comparison
        return $operator['password'] === $password;
    }

    /**
     * Update last login time
     */
    public function updateLastLogin($id)
    {
        return $this->db->execute(
            "UPDATE operators SET updatedate = NOW() WHERE id = ?",
            [$id]
        );
    }
}
