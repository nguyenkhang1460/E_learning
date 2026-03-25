<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die("Bạn cần đăng nhập để truy cập trang này.");
}

$current_user_id = $_SESSION['user_id'];
$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;

if ($assignment_id == 0) {
    die("ID bài tập không hợp lệ.");
}

// Lấy thông tin bài tập và lớp học
$sql_assignment = "SELECT a.*, c.* 
                   FROM assignments a
                   JOIN classes c ON a.class_id = c.id
                   WHERE a.id = '$assignment_id'";
$result_assignment = mysqli_query($conn, $sql_assignment);
$assignment_data = mysqli_fetch_assoc($result_assignment);

if (!$assignment_data) {
    die("Bài tập không tồn tại!");
}

$class_id = $assignment_data['class_id'];
$class_data = $assignment_data; // Chứa cả thông tin class

// Kiểm tra quyền (chỉ Admin hoặc Chủ phòng mới được xuất)
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
$is_owner = ($class_data['owner_id'] == $current_user_id);

if (!$is_admin && !$is_owner) {
    die("Bạn không có quyền xuất điểm của lớp học này.");
}

// Lấy danh sách tất cả học viên trong lớp (bao gồm cả chủ phòng)
$sql_members = "SELECT DISTINCT u.id, u.full_name, u.email 
                FROM users u
                WHERE u.id = '{$class_data['owner_id']}'
                UNION
                SELECT DISTINCT u.id, u.full_name, u.email
                FROM class_members cm 
                JOIN users u ON cm.user_id = u.id 
                WHERE cm.class_id = '$class_id'
                ORDER BY full_name ASC";
$result_members = mysqli_query($conn, $sql_members);

// Tạo mảng học viên với điểm bài tập cụ thể
$students = [];
while ($member = mysqli_fetch_assoc($result_members)) {
    $students[$member['id']] = [
        'full_name' => $member['full_name'],
        'email' => $member['email'],
        'score' => null,
        'submitted_at' => null
    ];
}

// Lấy điểm của bài tập này (lấy bài nộp mới nhất của mỗi học viên)
$sql_submissions = "SELECT s.user_id, s.ai_score, s.submitted_at
                    FROM submissions s
                    WHERE s.assignment_id = '$assignment_id' AND s.ai_score IS NOT NULL
                    ORDER BY s.user_id, s.submitted_at DESC";
$result_submissions = mysqli_query($conn, $sql_submissions);

// Điền điểm vào mảng (chỉ lấy bài nộp mới nhất)
$processed = [];
while ($submission = mysqli_fetch_assoc($result_submissions)) {
    $user_id = $submission['user_id'];
    
    // Chỉ lấy điểm lần đầu tiên (bài nộp mới nhất)
    if (!isset($processed[$user_id]) && isset($students[$user_id])) {
        $students[$user_id]['score'] = $submission['ai_score'];
        $students[$user_id]['submitted_at'] = $submission['submitted_at'];
        $processed[$user_id] = true;
    }
}

// Tạo file CSV với UTF-8 BOM để Excel đọc được tiếng Việt
$assignment_title = preg_replace('/[^a-zA-Z0-9]/', '_', $assignment_data['title']);
$filename = "Diem_" . $assignment_title . "_" . date('Y-m-d') . ".csv";

// Set headers để tải file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Tạo output stream
$output = fopen('php://output', 'w');

// Thêm UTF-8 BOM để Excel nhận diện encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Header row
$header = ['STT', 'Họ và tên', 'Email', 'Điểm', 'Thời gian nộp', 'Trạng thái'];
fputcsv($output, $header);

// Data rows
$stt = 1;
$total_score = 0;
$count_scored = 0;

foreach ($students as $student_id => $student) {
    $row = [
        $stt++,
        $student['full_name'],
        $student['email']
    ];
    
    if ($student['score'] !== null) {
        $row[] = number_format($student['score'], 2);
        $row[] = date('d/m/Y H:i', strtotime($student['submitted_at']));
        $row[] = 'Đã chấm';
        $total_score += $student['score'];
        $count_scored++;
    } else {
        $row[] = '--';
        $row[] = '--';
        $row[] = 'Chưa nộp/Chưa chấm';
    }
    
    fputcsv($output, $row);
}

// Thống kê
fputcsv($output, []);
fputcsv($output, ['THỐNG KÊ']);
fputcsv($output, ['Tổng số học viên:', count($students)]);
fputcsv($output, ['Số học viên đã chấm:', $count_scored]);
fputcsv($output, ['Tỷ lệ hoàn thành:', $count_scored > 0 ? round(($count_scored / count($students)) * 100, 2) . '%' : '0%']);
if ($count_scored > 0) {
    $average = $total_score / $count_scored;
    fputcsv($output, ['Điểm trung bình:', number_format($average, 2)]);
    fputcsv($output, ['Điểm cao nhất:', number_format(max(array_filter(array_column($students, 'score'))), 2)]);
    fputcsv($output, ['Điểm thấp nhất:', number_format(min(array_filter(array_column($students, 'score'))), 2)]);
}

fclose($output);
mysqli_close($conn);
exit();
?>
