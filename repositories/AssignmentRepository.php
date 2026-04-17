<?php
/**
 * AssignmentRepository — Data Access Layer cho bảng assignments + submissions
 */
class AssignmentRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM assignments WHERE id = ? LIMIT 1',
            [$id], 'i'
        );
    }

    public function findByIdWithClass(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT a.*, c.class_name, c.owner_id FROM assignments a
             JOIN classes c ON a.class_id = c.id
             WHERE a.id = ? LIMIT 1',
            [$id], 'i'
        );
    }

    public function getByClass(int $classId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM assignments WHERE class_id = ? ORDER BY id DESC',
            [$classId], 'i'
        );
    }

    public function createQuiz(int $classId, string $title, string $difficulty, ?int $duration, ?string $deadline, int $showScore): int
    {
        return $this->db->insert(
            'INSERT INTO assignments (class_id, title, type, difficulty, duration, deadline, show_score)
             VALUES (?, ?, "quiz", ?, ?, ?, ?)',
            [$classId, $title, $difficulty, $duration, $deadline, $showScore],
            'issisi'
        );
    }

    public function delete(int $id): bool
    {
        $affected = $this->db->execute(
            'DELETE FROM assignments WHERE id = ?',
            [$id], 'i'
        );
        return $affected > 0;
    }

    public function countByClass(int $classId): int
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) as total FROM assignments WHERE class_id = ?',
            [$classId], 'i'
        );
        return (int)($row['total'] ?? 0);
    }

    public function countAll(): int
    {
        $row = $this->db->fetchOne('SELECT COUNT(*) as total FROM assignments');
        return (int)($row['total'] ?? 0);
    }

    // ── Submissions ────────────────────────────────────────────────────────

    /**
     * Kiểm tra user đã nộp bài chưa — qua bảng submissions
     */
    public function hasSubmitted(int $assignmentId, int $userId): bool
    {
        return $this->db->count(
            'SELECT id FROM submissions WHERE assignment_id = ? AND user_id = ?',
            [$assignmentId, $userId], 'ii'
        ) > 0;
    }

    /**
     * Lưu kết quả quiz vào bảng submissions
     * Khớp với schema thực tế: (assignment_id, user_id, student_file_url, student_file_type, ai_score, violation_count, violation_log)
     * Trả về submission_id để dùng cho quiz_submissions
     */
    public function saveQuizSubmission(int $assignmentId, int $userId, float $score, int $violationCount = 0, string $violationLog = '[]'): int
    {
        return $this->db->insert(
            "INSERT INTO submissions (assignment_id, user_id, student_file_url, student_file_type, ai_score, violation_count, violation_log)
             VALUES (?, ?, '', 'quiz', ?, ?, ?)",
            [$assignmentId, $userId, $score, $violationCount, $violationLog],
            'iidis'
        );
    }

    public function getSubmissionsForExport(int $assignmentId): array
    {
        return $this->db->fetchAll(
            'SELECT s.user_id, s.ai_score, s.submitted_at
             FROM submissions s
             WHERE s.assignment_id = ? AND s.ai_score IS NOT NULL
             ORDER BY s.user_id, s.submitted_at DESC',
            [$assignmentId], 'i'
        );
    }

    public function countAllSubmissions(): int
    {
        $row = $this->db->fetchOne('SELECT COUNT(*) as total FROM submissions');
        return (int)($row['total'] ?? 0);
    }
}
