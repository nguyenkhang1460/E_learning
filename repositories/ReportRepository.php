<?php
/**
 * ReportRepository — Data Access Layer cho bảng reports
 */
class ReportRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(int $userId, int $classId, string $reportType, int $targetId, string $reason): int
    {
        return $this->db->insert(
            'INSERT INTO reports (user_id, class_id, report_type, target_id, reason, created_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [$userId, $classId, $reportType, $targetId, $reason],
            'iisis'
        );
    }

    public function getAllWithDetails(): array
    {
        return $this->db->fetchAll(
            'SELECT r.*, u.username, u.full_name, c.class_name
             FROM reports r
             LEFT JOIN users u ON r.user_id = u.id
             LEFT JOIN classes c ON r.class_id = c.id
             ORDER BY r.created_at DESC'
        );
    }

    public function countAll(): int
    {
        $row = $this->db->fetchOne("SELECT COUNT(*) as total FROM reports WHERE report_type = 'class'");
        return (int)($row['total'] ?? 0);
    }

    public function deleteById(int $id): bool
    {
        return $this->db->execute('DELETE FROM reports WHERE id = ?', [$id], 'i') > 0;
    }
}
