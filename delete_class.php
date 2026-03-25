<?php
session_start();
include 'db_config.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Lấy thông tin lớp học
$sql_class = "SELECT * FROM classes WHERE id = '$class_id'";
$result_class = mysqli_query($conn, $sql_class);
$class_data = mysqli_fetch_assoc($result_class);

if (!$class_data) {
    $_SESSION['error'] = "Lớp học không tồn tại!";
    header("Location: user_dashboard.php");
    exit();
}

// 3. Kiểm tra quyền (chỉ admin hoặc chủ phòng mới được xóa)
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
if (!$is_admin && $class_data['owner_id'] != $current_user_id) {
    $_SESSION['error'] = "Bạn không có quyền xóa lớp học này. Chỉ admin hoặc chủ phòng mới được xóa.";
    header("Location: user_dashboard.php");
    exit();
}

// 4. Xóa các dữ liệu liên quan (xóa cascade theo đúng thứ tự)

// Lấy danh sách ID bài tập để sử dụng cho các lệnh xóa
$sql_get_assignments = "SELECT id FROM assignments WHERE class_id = '$class_id'";
$result_assignments = mysqli_query($conn, $sql_get_assignments);
$assignment_ids = [];
while ($assignment = mysqli_fetch_assoc($result_assignments)) {
    $assignment_ids[] = $assignment['id'];
}

// Nếu có bài tập, xóa dữ liệu liên quan
if (!empty($assignment_ids)) {
    $assignment_ids_str = implode(',', $assignment_ids);
    
    // Xóa câu hỏi trắc nghiệm (quiz_questions)
    $check_quiz_table = mysqli_query($conn, "SHOW TABLES LIKE 'quiz_questions'");
    if (mysqli_num_rows($check_quiz_table) > 0) {
        mysqli_query($conn, "DELETE FROM quiz_questions WHERE assignment_id IN ($assignment_ids_str)");
    }
    
    // Xóa student_submissions (nếu có bảng này)
    $check_student_subs_table = mysqli_query($conn, "SHOW TABLES LIKE 'student_submissions'");
    if (mysqli_num_rows($check_student_subs_table) > 0) {
        mysqli_query($conn, "DELETE FROM student_submissions WHERE assignment_id IN ($assignment_ids_str)");
    }
    
    // Xóa file bài nộp và dữ liệu submissions
    $sql_get_submissions = "SELECT student_file_url FROM submissions WHERE assignment_id IN ($assignment_ids_str)";
    $result_submissions = mysqli_query($conn, $sql_get_submissions);
    while ($submission = mysqli_fetch_assoc($result_submissions)) {
        if (!empty($submission['student_file_url']) && file_exists($submission['student_file_url'])) {
            unlink($submission['student_file_url']);
        }
    }
    mysqli_query($conn, "DELETE FROM submissions WHERE assignment_id IN ($assignment_ids_str)");
    
    // Xóa file đáp án của bài tập
    $sql_get_answer_keys = "SELECT answer_key_url FROM assignments WHERE id IN ($assignment_ids_str)";
    $result_answer_keys = mysqli_query($conn, $sql_get_answer_keys);
    while ($answer_key = mysqli_fetch_assoc($result_answer_keys)) {
        if (!empty($answer_key['answer_key_url']) && file_exists($answer_key['answer_key_url'])) {
            unlink($answer_key['answer_key_url']);
        }
    }
}

// Xóa bài tập trong database
mysqli_query($conn, "DELETE FROM assignments WHERE class_id = '$class_id'");

// Xóa file bài giảng
$sql_get_lectures = "SELECT file_url FROM lectures WHERE class_id = '$class_id'";
$result_lectures = mysqli_query($conn, $sql_get_lectures);
while ($lecture = mysqli_fetch_assoc($result_lectures)) {
    if (!empty($lecture['file_url']) && file_exists($lecture['file_url'])) {
        unlink($lecture['file_url']);
    }
}

// Xóa bài giảng trong database
mysqli_query($conn, "DELETE FROM lectures WHERE class_id = '$class_id'");

// Xóa thành viên lớp học
mysqli_query($conn, "DELETE FROM class_members WHERE class_id = '$class_id'");

// Xóa các yêu cầu tham gia lớp học
mysqli_query($conn, "DELETE FROM class_requests WHERE class_id = '$class_id'");

// Xóa các báo cáo liên quan đến lớp học (nếu có bảng reports)
$check_reports_table = mysqli_query($conn, "SHOW TABLES LIKE 'reports'");
if (mysqli_num_rows($check_reports_table) > 0) {
    mysqli_query($conn, "DELETE FROM reports WHERE class_id = '$class_id'");
}

// 5. Xóa lớp học
$sql_delete_class = "DELETE FROM classes WHERE id = '$class_id'";
if (mysqli_query($conn, $sql_delete_class)) {
    $_SESSION['success'] = "Đã xóa lớp học thành công!";
} else {
    $_SESSION['error'] = "Lỗi khi xóa lớp học: " . mysqli_error($conn);
}

// 6. Chuyển về trang dashboard
header("Location: user_dashboard.php");
exit();
?>
