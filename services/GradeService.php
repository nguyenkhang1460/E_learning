<?php
/**
 * GradeService — Business logic cho xuất điểm CSV
 */
class GradeService
{
    private AssignmentRepository $assignmentRepo;
    private Database $db;

    public function __construct()
    {
        $this->assignmentRepo = new AssignmentRepository();
        $this->db             = Database::getInstance();
    }

    /**
     * Xuất điểm bài tập ra CSV
     * Tách hoàn toàn việc query data và generate CSV khỏi HTTP layer
     */
    public function exportCsv(int $assignmentId, int $currentUserId, bool $isAdmin): void
    {
        $assignment = $this->assignmentRepo->findByIdWithClass($assignmentId);
        if (!$assignment) {
            http_response_code(404);
            die('Bài tập không tồn tại!');
        }

        $isOwner = (int)$assignment['owner_id'] === $currentUserId;
        if (!$isAdmin && !$isOwner) {
            http_response_code(403);
            die('Bạn không có quyền xuất điểm của lớp học này.');
        }

        $classId = (int)$assignment['class_id'];

        // Lấy danh sách thành viên
        $members = $this->db->fetchAll(
            'SELECT DISTINCT u.id, u.full_name, u.email FROM users u WHERE u.id = ?
             UNION
             SELECT DISTINCT u.id, u.full_name, u.email
             FROM class_members cm JOIN users u ON cm.user_id = u.id
             WHERE cm.class_id = ?
             ORDER BY full_name ASC',
            [$assignment['owner_id'], $classId], 'ii'
        );

        $students = [];
        foreach ($members as $m) {
            $students[$m['id']] = ['full_name' => $m['full_name'], 'email' => $m['email'], 'score' => null, 'submitted_at' => null];
        }

        // Gán điểm
        $submissions = $this->assignmentRepo->getSubmissionsForExport($assignmentId);
        $processed   = [];
        foreach ($submissions as $sub) {
            $uid = $sub['user_id'];
            if (!isset($processed[$uid]) && isset($students[$uid])) {
                $students[$uid]['score']        = $sub['ai_score'];
                $students[$uid]['submitted_at'] = $sub['submitted_at'];
                $processed[$uid] = true;
            }
        }

        // Headers CSV
        $safeTitle = preg_replace('/[^a-zA-Z0-9]/', '_', $assignment['title']);
        $filename  = 'Diem_' . $safeTitle . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

        fputcsv($output, ['STT', 'Họ và tên', 'Email', 'Điểm', 'Thời gian nộp', 'Trạng thái']);

        $stt = 1;
        $totalScore = 0;
        $countScored = 0;

        foreach ($students as $student) {
            $row = [$stt++, $student['full_name'], $student['email']];
            if ($student['score'] !== null) {
                $row[] = number_format($student['score'], 2);
                $row[] = date('d/m/Y H:i', strtotime($student['submitted_at']));
                $row[] = 'Đã chấm';
                $totalScore += $student['score'];
                $countScored++;
            } else {
                $row[] = '--';
                $row[] = '--';
                $row[] = 'Chưa nộp/Chưa chấm';
            }
            fputcsv($output, $row);
        }

        fputcsv($output, []);
        fputcsv($output, ['THỐNG KÊ']);
        fputcsv($output, ['Tổng số học viên:', count($students)]);
        fputcsv($output, ['Số học viên đã chấm:', $countScored]);
        fputcsv($output, ['Tỷ lệ hoàn thành:', $countScored > 0 ? round(($countScored / count($students)) * 100, 2) . '%' : '0%']);
        if ($countScored > 0) {
            $scores = array_filter(array_column($students, 'score'));
            fputcsv($output, ['Điểm trung bình:', number_format($totalScore / $countScored, 2)]);
            fputcsv($output, ['Điểm cao nhất:', number_format(max($scores), 2)]);
            fputcsv($output, ['Điểm thấp nhất:', number_format(min($scores), 2)]);
        }

        fclose($output);
    }
}
