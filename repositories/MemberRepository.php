<?php
/**
 * MemberRepository — Data Access Layer cho class_requests + class_members
 */
class MemberRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getPendingRequests(int $classId = 0, int $ownerId = 0, bool $isAdmin = false): array
    {
        if ($classId > 0) {
            if ($isAdmin) {
                return $this->db->fetchAll(
                    'SELECT r.id, r.answers, r.user_id, r.class_id, r.created_at, c.class_name, u.full_name, u.email
                     FROM class_requests r
                     JOIN classes c ON r.class_id = c.id
                     JOIN users u ON r.user_id = u.id
                     WHERE r.status = "pending" AND r.class_id = ?
                     ORDER BY r.created_at DESC',
                    [$classId], 'i'
                );
            }
            return $this->db->fetchAll(
                'SELECT r.id, r.answers, r.user_id, r.class_id, r.created_at, c.class_name, u.full_name, u.email
                 FROM class_requests r
                 JOIN classes c ON r.class_id = c.id
                 JOIN users u ON r.user_id = u.id
                 WHERE c.owner_id = ? AND r.status = "pending" AND r.class_id = ?
                 ORDER BY r.created_at DESC',
                [$ownerId, $classId], 'ii'
            );
        }

        if ($isAdmin) {
            return $this->db->fetchAll(
                'SELECT r.id, r.answers, r.user_id, r.class_id, r.created_at, c.class_name, u.full_name, u.email
                 FROM class_requests r
                 JOIN classes c ON r.class_id = c.id
                 JOIN users u ON r.user_id = u.id
                 WHERE r.status = "pending"
                 ORDER BY r.created_at DESC'
            );
        }

        return $this->db->fetchAll(
            'SELECT r.id, r.answers, r.user_id, r.class_id, r.created_at, c.class_name, u.full_name, u.email
             FROM class_requests r
             JOIN classes c ON r.class_id = c.id
             JOIN users u ON r.user_id = u.id
             WHERE c.owner_id = ? AND r.status = "pending"
             ORDER BY r.created_at DESC',
            [$ownerId], 'i'
        );
    }

    public function findRequestById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM class_requests WHERE id = ? LIMIT 1',
            [$id], 'i'
        );
    }

    public function updateRequestStatus(int $requestId, string $status): bool
    {
        $affected = $this->db->execute(
            'UPDATE class_requests SET status = ? WHERE id = ?',
            [$status, $requestId], 'si'
        );
        return $affected > 0;
    }

    public function createRequest(int $classId, int $userId, ?string $answersJson): int
    {
        return $this->db->insert(
            'INSERT INTO class_requests (class_id, user_id, answers, status, created_at) VALUES (?, ?, ?, "pending", NOW())',
            [$classId, $userId, $answersJson],
            'iis'
        );
    }

    public function hasPendingRequest(int $classId, int $userId): bool
    {
        return $this->db->count(
            'SELECT id FROM class_requests WHERE class_id = ? AND user_id = ? AND status = "pending"',
            [$classId, $userId], 'ii'
        ) > 0;
    }
}
