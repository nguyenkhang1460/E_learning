<?php
/**
 * ReportService — Business logic cho báo cáo vi phạm
 */
class ReportService
{
    private ReportRepository $reports;

    public function __construct()
    {
        $this->reports = new ReportRepository();
    }

    public function submit(int $userId, array $postData): array
    {
        $classId = isset($postData['class_id']) ? (int)$postData['class_id'] : 0;
        $reason  = trim($postData['reason'] ?? '');

        if (empty($reason)) {
            return ['success' => false, 'message' => 'Vui lòng nhập lý do báo cáo'];
        }

        // Xác định loại báo cáo
        if (isset($postData['lecture_id'])) {
            $reportType = 'lecture';
            $targetId   = (int)$postData['lecture_id'];
        } elseif (isset($postData['assignment_id'])) {
            $reportType = 'assignment';
            $targetId   = (int)$postData['assignment_id'];
        } elseif (!empty($postData['report_class'])) {
            $reportType = 'class';
            $targetId   = $classId;
        } else {
            return ['success' => false, 'message' => 'Thiếu thông tin báo cáo'];
        }

        try {
            $this->reports->create($userId, $classId, $reportType, $targetId, $reason);
            return ['success' => true, 'message' => 'Báo cáo đã được gửi thành công'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Lỗi khi gửi báo cáo'];
        }
    }
}
