<?php
/**
 * LectureRepository — Data Access Layer cho bảng lectures
 */
class LectureRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM lectures WHERE id = ? LIMIT 1',
            [$id], 'i'
        );
    }

    public function getByClass(int $classId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM lectures WHERE class_id = ? ORDER BY id DESC',
            [$classId], 'i'
        );
    }

    public function create(int $classId, string $title, string $fileUrl, string $fileType): int
    {
        return $this->db->insert(
            'INSERT INTO lectures (class_id, title, file_url, file_type) VALUES (?, ?, ?, ?)',
            [$classId, $title, $fileUrl, $fileType],
            'isss'
        );
    }

    public function update(int $id, string $title): bool
    {
        $affected = $this->db->execute(
            'UPDATE lectures SET title = ? WHERE id = ?',
            [$title, $id], 'si'
        );
        return $affected > 0;
    }

    public function delete(int $id): ?string
    {
        $lecture = $this->findById($id);
        if (!$lecture) return null;

        $this->db->execute('DELETE FROM lectures WHERE id = ?', [$id], 'i');

        // Trả về file_url để caller tự xóa file nếu cần
        return $lecture['file_url'] ?? null;
    }

    public function getByClassWithTitle(int $classId): array
    {
        return $this->db->fetchAll(
            'SELECT id, title FROM lectures WHERE class_id = ? ORDER BY id ASC',
            [$classId], 'i'
        );
    }
}
