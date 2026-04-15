<?php
/**
 * ClassService — Business logic cho quản lý lớp học
 */
class ClassService
{
    private ClassRepository $classes;

    public function __construct()
    {
        $this->classes = new ClassRepository();
    }

    /**
     * Tạo lớp học mới, sinh invite code ngẫu nhiên
     */
    public function create(string $className, string $description, string $type, int $ownerId, ?array $requirements): array
    {
        $inviteCode = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
        $reqJson = null;
        if ($type === 'private' && !empty($requirements)) {
            $reqJson = json_encode($requirements, JSON_UNESCAPED_UNICODE);
        }

        $id = $this->classes->create($className, $description, $inviteCode, $ownerId, $type, $reqJson);
        return ['success' => $id > 0, 'id' => $id];
    }

    /**
     * Xóa lớp học — kiểm tra quyền trước
     */
    public function delete(int $classId, int $currentUserId, bool $isAdmin): array
    {
        $class = $this->classes->findById($classId);
        if (!$class) {
            return ['success' => false, 'message' => 'Lớp học không tồn tại!'];
        }
        if (!$isAdmin && (int)$class['owner_id'] !== $currentUserId) {
            return ['success' => false, 'message' => 'Bạn không có quyền xóa lớp học này.'];
        }

        if ($this->classes->delete($classId)) {
            return ['success' => true, 'message' => 'Đã xóa lớp học thành công!'];
        }
        return ['success' => false, 'message' => 'Lỗi khi xóa lớp học. Vui lòng thử lại.'];
    }

    /**
     * Xử lý yêu cầu tham gia lớp qua invite code
     */
    public function joinByCode(string $inviteCode, int $userId): array
    {
        $class = $this->classes->findByInviteCode(strtoupper(trim($inviteCode)));
        if (!$class) {
            return ['success' => false, 'message' => 'Mã lớp không hợp lệ!'];
        }

        $classId = (int)$class['id'];

        // Đã là thành viên hoặc chủ phòng?
        if ((int)$class['owner_id'] === $userId) {
            return ['success' => false, 'message' => 'Bạn là chủ phòng của lớp này!'];
        }
        if ($this->classes->isMember($classId, $userId)) {
            return ['success' => false, 'message' => 'Bạn đã là thành viên của lớp này!'];
        }

        // Lớp public: vào thẳng
        if ($class['type'] === 'public') {
            $this->classes->addMember($classId, $userId);
            return ['success' => true, 'classId' => $classId, 'message' => 'Đã tham gia lớp học!'];
        }

        // Lớp private: tạo yêu cầu
        return ['success' => true, 'needRequest' => true, 'class' => $class];
    }

    /**
     * Rời khỏi lớp học
     */
    public function leave(int $classId, int $userId): array
    {
        $class = $this->classes->findById($classId);
        if (!$class) {
            return ['success' => false, 'message' => 'Lớp học không tồn tại!'];
        }
        if ((int)$class['owner_id'] === $userId) {
            return ['success' => false, 'message' => 'Chủ phòng không thể rời lớp. Hãy xóa lớp thay thế.'];
        }

        $this->classes->removeMember($classId, $userId);
        return ['success' => true, 'message' => 'Đã rời khỏi lớp học!'];
    }
}
