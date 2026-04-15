<?php
/**
 * ClassRepository — Data Access Layer cho bảng classes + liên quan
 * Đặc biệt: delete() dùng TRANSACTION để đảm bảo data integrity
 */
class ClassRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $classId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM classes WHERE id = ? LIMIT 1',
            [$classId], 'i'
        );
    }

    public function create(string $className, string $description, string $inviteCode, int $ownerId, string $type, ?string $requirements): int
    {
        return $this->db->insert(
            'INSERT INTO classes (class_name, description, invite_code, owner_id, type, requirements) VALUES (?, ?, ?, ?, ?, ?)',
            [$className, $description, $inviteCode, $ownerId, $type, $requirements],
            'sssiss'
        );
    }

    public function findByInviteCode(string $code): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM classes WHERE invite_code = ? LIMIT 1',
            [$code], 's'
        );
    }

    public function getClassesForUser(int $userId, int $limit = 10): array
    {
        // Lớp do user làm owner
        return $this->db->fetchAll(
            'SELECT c.*, u.full_name as owner_name,
                    (SELECT COUNT(*) FROM class_members WHERE class_id = c.id) as member_count
             FROM classes c
             JOIN users u ON c.owner_id = u.id
             ORDER BY c.created_at DESC LIMIT ?',
            [$limit], 'i'
        );
    }

    public function getRecentClasses(int $limit = 10): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, u.full_name as owner_name,
                    (SELECT COUNT(*) FROM class_members WHERE class_id = c.id) as member_count
             FROM classes c
             JOIN users u ON c.owner_id = u.id
             ORDER BY c.created_at DESC LIMIT ?',
            [$limit], 'i'
        );
    }

    public function countAll(): int
    {
        $row = $this->db->fetchOne('SELECT COUNT(*) as total FROM classes');
        return (int)($row['total'] ?? 0);
    }

    /**
     * Xóa lớp học và toàn bộ dữ liệu liên quan
     * Dùng TRANSACTION để đảm bảo không có dữ liệu bị xóa một phần
     */
    public function delete(int $classId): bool
    {
        $db = $this->db;
        $db->beginTransaction();

        try {
            // 1. Lấy assignment IDs để xóa related data
            $assignments = $db->fetchAll(
                'SELECT id FROM assignments WHERE class_id = ?',
                [$classId], 'i'
            );
            $assignmentIds = array_column($assignments, 'id');

            if (!empty($assignmentIds)) {
                $placeholders = implode(',', array_fill(0, count($assignmentIds), '?'));
                $types = str_repeat('i', count($assignmentIds));

                // Xóa quiz_questions
                if ($db->count("SHOW TABLES LIKE 'quiz_questions'") > 0) {
                    $db->execute(
                        "DELETE FROM quiz_questions WHERE assignment_id IN ($placeholders)",
                        $assignmentIds, $types
                    );
                }

                // Lấy và xóa file bài nộp
                $submissions = $db->fetchAll(
                    "SELECT student_file_url FROM submissions WHERE assignment_id IN ($placeholders)",
                    $assignmentIds, $types
                );
                foreach ($submissions as $sub) {
                    if (!empty($sub['student_file_url']) && file_exists($sub['student_file_url'])) {
                        @unlink($sub['student_file_url']);
                    }
                }

                $db->execute(
                    "DELETE FROM submissions WHERE assignment_id IN ($placeholders)",
                    $assignmentIds, $types
                );

                // Xóa quiz_submissions nếu có bảng
                if ($db->count("SHOW TABLES LIKE 'quiz_submissions'") > 0) {
                    $db->execute(
                        "DELETE FROM quiz_submissions WHERE assignment_id IN ($placeholders)",
                        $assignmentIds, $types
                    );
                }

                // Xóa file đáp án
                $answerKeys = $db->fetchAll(
                    "SELECT answer_key_url FROM assignments WHERE id IN ($placeholders)",
                    $assignmentIds, $types
                );
                foreach ($answerKeys as $ak) {
                    if (!empty($ak['answer_key_url']) && file_exists($ak['answer_key_url'])) {
                        @unlink($ak['answer_key_url']);
                    }
                }

                $db->execute(
                    "DELETE FROM assignments WHERE id IN ($placeholders)",
                    $assignmentIds, $types
                );
            }

            // 2. Xóa lectures và file
            $lectures = $db->fetchAll(
                'SELECT file_url FROM lectures WHERE class_id = ?',
                [$classId], 'i'
            );
            foreach ($lectures as $lec) {
                if (!empty($lec['file_url']) && file_exists($lec['file_url'])) {
                    @unlink($lec['file_url']);
                }
            }
            $db->execute('DELETE FROM lectures WHERE class_id = ?', [$classId], 'i');

            // 3. Xóa members, requests, reports
            $db->execute('DELETE FROM class_members WHERE class_id = ?', [$classId], 'i');
            $db->execute('DELETE FROM class_requests WHERE class_id = ?', [$classId], 'i');

            if ($db->count("SHOW TABLES LIKE 'reports'") > 0) {
                $db->execute('DELETE FROM reports WHERE class_id = ?', [$classId], 'i');
            }

            // 4. Cuối cùng mới xóa lớp
            $db->execute('DELETE FROM classes WHERE id = ?', [$classId], 'i');

            $db->commit();
            return true;

        } catch (Throwable $e) {
            $db->rollback();
            error_log('ClassRepository::delete error: ' . $e->getMessage());
            return false;
        }
    }

    public function isMember(int $classId, int $userId): bool
    {
        return $this->db->count(
            'SELECT id FROM class_members WHERE class_id = ? AND user_id = ?',
            [$classId, $userId], 'ii'
        ) > 0;
    }

    public function addMember(int $classId, int $userId): bool
    {
        try {
            $this->db->insert(
                'INSERT INTO class_members (class_id, user_id) VALUES (?, ?)',
                [$classId, $userId], 'ii'
            );
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function removeMember(int $classId, int $userId): bool
    {
        $affected = $this->db->execute(
            'DELETE FROM class_members WHERE class_id = ? AND user_id = ?',
            [$classId, $userId], 'ii'
        );
        return $affected > 0;
    }

    public function getMembers(int $classId): array
    {
        return $this->db->fetchAll(
            'SELECT u.id, u.username, u.full_name, u.email
             FROM class_members cm
             JOIN users u ON cm.user_id = u.id
             WHERE cm.class_id = ?
             ORDER BY u.full_name ASC',
            [$classId], 'i'
        );
    }
}
