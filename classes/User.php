<?php
class User
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Register user
    public function register($data)
    {
        $this->db->query('INSERT INTO users (username, email, password) VALUES (:username, :email, :password)');
        $this->db->bind(':username', $data['username']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', $data['password']);

        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }

    // Login user
    public function login($username, $password)
    {
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);

        $row = $this->db->single();

        if ($row) {
            $hashed_password = $row->password;
            if (password_verify($password, $hashed_password)) {
                // Update last login
                $this->updateLastLogin($row->id);
                return $row;
            }
        }

        return false;
    }

    // Update last login
    private function updateLastLogin($user_id)
    {
        $this->db->query('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id');
        $this->db->bind(':id', $user_id);
        $this->db->execute();
    }

    // Find user by email
    public function findUserByEmail($email)
    {
        $this->db->query('SELECT * FROM users WHERE email = :email');
        $this->db->bind(':email', $email);
        $row = $this->db->single();

        if ($this->db->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    // Find user by username
    public function findUserByUsername($username)
    {
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        $row = $this->db->single();

        if ($this->db->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }

    // Get user by ID
    public function getUserById($id)
    {
        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Verify user's current password
    public function verifyPassword($user_id, $password)
    {
        $this->db->query('SELECT password FROM users WHERE id = :id');
        $this->db->bind(':id', $user_id);
        $user = $this->db->single();

        if ($user) {
            return password_verify($password, $user->password);
        }
        return false;
    }

    // Update user password
    public function updatePassword($user_id, $new_password)
    {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $this->db->query('UPDATE users SET password = :password WHERE id = :id');
        $this->db->bind(':password', $hashed_password);
        $this->db->bind(':id', $user_id);

        return $this->db->execute();
    }
}