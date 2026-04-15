<?php
/**
 * UserRepository — Data Access Layer cho bảng users
 */
class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE id = ? LIMIT 1',
            [$id], 'i'
        );
    }

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE username = ? LIMIT 1',
            [$username], 's'
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM users WHERE email = ? LIMIT 1',
            [$email], 's'
        );
    }

    public function usernameExists(string $username): bool
    {
        return $this->db->count(
            'SELECT id FROM users WHERE username = ?',
            [$username], 's'
        ) > 0;
    }

    public function emailExists(string $email): bool
    {
        return $this->db->count(
            'SELECT id FROM users WHERE email = ?',
            [$email], 's'
        ) > 0;
    }

    public function create(string $username, string $email, string $hashedPassword, string $fullName, string $role = 'user'): int
    {
        return $this->db->insert(
            'INSERT INTO users (username, email, password, full_name, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [$username, $email, $hashedPassword, $fullName, $role],
            'sssss'
        );
    }

    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $affected = $this->db->execute(
            'UPDATE users SET password = ? WHERE id = ?',
            [$hashedPassword, $userId], 'si'
        );
        return $affected > 0;
    }

    public function getRecentUsers(int $limit = 10, string $excludeRole = 'admin'): array
    {
        return $this->db->fetchAll(
            'SELECT id, username, full_name, email, role, created_at FROM users WHERE role != ? ORDER BY created_at DESC LIMIT ?',
            [$excludeRole, $limit], 'si'
        );
    }

    public function countByRole(string $excludeRole = 'admin'): int
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM users WHERE role != ?",
            [$excludeRole], 's'
        );
        return (int)($row['total'] ?? 0);
    }
}
