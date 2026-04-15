<?php
/**
 * MemberService — Business logic cho quản lý yêu cầu tham gia lớp
 */
class MemberService
{
    private MemberRepository $members;
    private ClassRepository $classes;

    public function __construct()
    {
        $this->members = new MemberRepository();
        $this->classes = new ClassRepository();
    }

    /**
     * Duyệt hoặc từ chối yêu cầu tham gia
     * FIX: Thêm kiểm tra quyền owner - bất kỳ ai cũng không thể approve nữa!
     */
    public function handleRequest(int $requestId, string $status, int $currentUserId, bool $isAdmin): array
    {
        if (!in_array($status, ['approved', 'rejected'])) {
            return ['success' => false, 'message' => 'Trạng thái không hợp lệ!'];
        }

        $request = $this->members->findRequestById($requestId);
        if (!$request) {
            return ['success' => false, 'message' => 'Yêu cầu không tồn tại!'];
        }

        // Kiểm tra quyền: phải là admin hoặc owner của lớp
        $class = $this->classes->findById((int)$request['class_id']);
        if (!$class) {
            return ['success' => false, 'message' => 'Lớp học không tồn tại!'];
        }

        if (!$isAdmin && (int)$class['owner_id'] !== $currentUserId) {
            return ['success' => false, 'message' => 'Bạn không có quyền xử lý yêu cầu này!'];
        }

        $this->members->updateRequestStatus($requestId, $status);

        if ($status === 'approved') {
            $this->classes->addMember((int)$request['class_id'], (int)$request['user_id']);
        }

        $message = $status === 'approved' ? 'Đã phê duyệt yêu cầu!' : 'Đã từ chối yêu cầu!';
        return ['success' => true, 'message' => $message, 'classId' => (int)$request['class_id']];
    }

    /**
     * Xử lý form yêu cầu tham gia lớp private
     */
    public function createRequest(int $classId, int $userId, ?array $answers): array
    {
        // Kiểm tra đã có yêu cầu pending chưa
        if ($this->members->hasPendingRequest($classId, $userId)) {
            return ['success' => false, 'message' => 'Bạn đã gửi yêu cầu tham gia lớp này rồi!'];
        }

        $answersJson = null;
        if (!empty($answers)) {
            $answersJson = json_encode($answers, JSON_UNESCAPED_UNICODE);
        }

        $this->members->createRequest($classId, $userId, $answersJson);
        return ['success' => true, 'message' => 'Đã gửi yêu cầu tham gia. Chờ chủ phòng duyệt!'];
    }
}
