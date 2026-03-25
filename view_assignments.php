<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$class_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

// Lấy thông tin lớp học
$sql_class = "SELECT * FROM classes WHERE id = '$class_id'";
$result_class = mysqli_query($conn, $sql_class);
$class_data = mysqli_fetch_assoc($result_class);

if (!$class_data) {
    die("Lớp học không tồn tại!");
}

// Kiểm tra quyền truy cập (phải là admin, owner hoặc member)
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
$is_owner = ($class_data['owner_id'] == $current_user_id);
$is_member = false;
if (!$is_owner && !$is_admin) {
    $sql_member = "SELECT id FROM class_members WHERE user_id = '$current_user_id' AND class_id = '$class_id'";
    $res_member = mysqli_query($conn, $sql_member);
    if (mysqli_num_rows($res_member) > 0) {
        $is_member = true;
    }
}

// Admin có quyền truy cập tất cả các lớp học
if (!$is_admin && !$is_owner && !$is_member) {
    die("Bạn không có quyền truy cập lớp học này!");
}

// Lấy danh sách bài tập
$sql_assignments = "SELECT * FROM assignments WHERE class_id = '$class_id' ORDER BY created_at DESC";
$result_assignments = mysqli_query($conn, $sql_assignments);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài tập - <?php echo htmlspecialchars($class_data['class_name']); ?></title>
    <link rel="stylesheet" href="user_style.css?v=<?php echo filemtime('user_style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="view-assignments-page">
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i> AI-Learning
        </div>
        <div class="nav-links">
            <a href="manage_class.php?id=<?php echo $class_id; ?>" style="text-decoration: none; color: #666;">
                <i class="fas fa-arrow-left"></i> Quay lại lớp học
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-section">
            <h1><i class="fas fa-tasks"></i> Danh sách bài tập</h1>
            <p class="subtitle">Lớp: <strong><?php echo htmlspecialchars($class_data['class_name']); ?></strong></p>
        </div>

        <?php if (mysqli_num_rows($result_assignments) > 0): ?>
            <?php while($assignment = mysqli_fetch_assoc($result_assignments)): ?>
                <?php
                // Kiểm tra xem học viên đã làm bài chưa
                $sql_check = "SELECT * FROM student_submissions WHERE assignment_id = '{$assignment['id']}' AND user_id = '$current_user_id'";
                $result_check = mysqli_query($conn, $sql_check);
                $has_submitted = mysqli_num_rows($result_check) > 0;
                $submission = $has_submitted ? mysqli_fetch_assoc($result_check) : null;
                ?>
                <div class="assignment-item">
                    <div class="assignment-header">
                        <div class="assignment-title">
                            <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($assignment['title']); ?>
                            <?php if (isset($assignment['difficulty'])): ?>
                                <?php 
                                    $difficulty_badges = [
                                        'easy' => ['label' => 'Dễ', 'color' => '#4ade80', 'icon' => '🟢'],
                                        'medium' => ['label' => 'Vừa', 'color' => '#fbbf24', 'icon' => '🟡'],
                                        'hard' => ['label' => 'Khó', 'color' => '#f87171', 'icon' => '🔴']
                                    ];
                                    $badge = $difficulty_badges[$assignment['difficulty']] ?? $difficulty_badges['medium'];
                                ?>
                                <span style="background: <?php echo $badge['color']; ?>; color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; margin-left: 10px;">
                                    <?php echo $badge['icon'] . ' ' . $badge['label']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="assignment-status <?php echo $has_submitted ? 'status-completed' : 'status-pending'; ?>">
                            <?php echo $has_submitted ? 'Đã làm' : 'Chưa làm'; ?>
                        </span>
                    </div>
                    
                    <?php if ($assignment['description']): ?>
                        <div class="assignment-info">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($assignment['description']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="assignment-info">
                        <i class="fas fa-calendar-plus"></i> Ngày đăng: <?php echo date('d/m/Y H:i', strtotime($assignment['created_at'])); ?>
                    </div>
                    
                    <?php if ($assignment['duration']): ?>
                        <div class="assignment-info" style="color: #764ba2;">
                            <i class="fas fa-clock"></i> Thời gian làm bài: <strong><?php echo $assignment['duration']; ?> phút</strong>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($assignment['deadline']): 
                        $deadline_timestamp = strtotime($assignment['deadline']);
                        $is_expired = (time() > $deadline_timestamp);
                        $time_left = $deadline_timestamp - time();
                        $hours_left = floor($time_left / 3600);
                    ?>
                        <div class="assignment-info" style="color: <?php echo $is_expired ? '#dc3545' : ($hours_left < 24 ? '#f5576c' : '#667eea'); ?>; font-weight: bold;">
                            <i class="fas fa-calendar-times"></i> 
                            <?php if ($is_expired): ?>
                                Đã hết hạn nộp (<?php echo date('d/m/Y H:i', $deadline_timestamp); ?>)
                            <?php else: ?>
                                Hạn nộp: <?php echo date('d/m/Y H:i', $deadline_timestamp); ?>
                                <?php if ($hours_left < 24): ?>
                                    <span style="color: #f5576c;">(Còn <?php echo $hours_left; ?> giờ)</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($has_submitted && $submission['score'] !== null): ?>
                        <div class="assignment-info" style="color: #28a745; font-weight: bold;">
                            <i class="fas fa-check-circle"></i> Điểm: <?php echo $submission['score']; ?>/100
                        </div>
                    <?php endif; ?>
                    
                    <div class="assignment-actions">
                        <a href="view_assignment_detail.php?id=<?php echo $assignment['id']; ?>" class="btn-do">
                            <i class="fas fa-pen"></i> <?php echo $has_submitted ? 'Xem lại bài làm' : 'Làm bài tập'; ?>
                        </a>
                        <button class="btn-report js-report-assignment" data-assignment-id="<?php echo $assignment['id']; ?>" data-class-id="<?php echo $class_id; ?>">
                            <i class="fas fa-flag"></i> Report
                        </button>
                        <?php if ($is_owner): ?>
                            <a href="delete_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn-report" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa bài kiểm tra này? Tất cả bài nộp sẽ bị xóa!')">
                                <i class="fas fa-trash"></i> Xóa
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h2>Chưa có bài tập nào</h2>
                <p>Giáo viên chưa tạo bài tập cho lớp này.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="xu_ly.js?v=<?php echo filemtime('xu_ly.js'); ?>"></script>
</body>
</html>
