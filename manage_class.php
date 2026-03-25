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
    die("Lớp học không tồn tại!");
}

// Lấy thông tin chủ phòng
$sql_owner = "SELECT full_name FROM users WHERE id = '{$class_data['owner_id']}'";
$result_owner = mysqli_query($conn, $sql_owner);
$owner_data = mysqli_fetch_assoc($result_owner);
$owner_name = $owner_data ? $owner_data['full_name'] : 'Không xác định';

// 3. Xác định vai trò (Admin, Chủ phòng hay Thành viên)
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
    die("Bạn không có quyền truy cập lớp học này.");
}

// 4. Lấy dữ liệu hiển thị
// Danh sách thành viên (bao gồm cả chủ phòng)
$sql_members = "SELECT u.id, u.full_name, u.email, u.role, 
                CASE WHEN u.id = '$current_user_id' THEN 1 ELSE 0 END as is_current_user,
                CASE WHEN u.id = '{$class_data['owner_id']}' THEN 1 ELSE 0 END as is_owner
                FROM class_members cm 
                JOIN users u ON cm.user_id = u.id 
                WHERE cm.class_id = '$class_id'
                UNION
                SELECT u.id, u.full_name, u.email, u.role, 
                CASE WHEN u.id = '$current_user_id' THEN 1 ELSE 0 END as is_current_user,
                1 as is_owner
                FROM users u 
                WHERE u.id = '{$class_data['owner_id']}'
                ORDER BY is_owner DESC, full_name ASC";
$result_members = mysqli_query($conn, $sql_members);
$total_members = mysqli_num_rows($result_members);

// Danh sách bài giảng
$result_lectures = mysqli_query($conn, "SELECT * FROM lectures WHERE class_id = '$class_id' ORDER BY created_at DESC");

// Danh sách bài tập
$result_assignments = mysqli_query($conn, "SELECT * FROM assignments WHERE class_id = '$class_id' ORDER BY created_at DESC");

// Danh sách bài nộp (Nếu là Admin hoặc GV thì xem tất cả, nếu là HV thì chỉ xem bài của mình)
if ($is_admin || $is_owner) {
    $sql_subs = "SELECT s.*, u.full_name, a.title as asm_title 
                 FROM submissions s 
                 JOIN users u ON s.user_id = u.id 
                 JOIN assignments a ON s.assignment_id = a.id 
                 WHERE a.class_id = '$class_id' ORDER BY s.submitted_at DESC";
} else {
    $sql_subs = "SELECT s.*, a.title as asm_title 
                 FROM submissions s 
                 JOIN assignments a ON s.assignment_id = a.id 
                 WHERE s.user_id = '$current_user_id' AND a.class_id = '$class_id' ORDER BY s.submitted_at DESC";
}
$result_submissions = mysqli_query($conn, $sql_subs);

// Đếm số yêu cầu chờ duyệt cho lớp học này (chỉ cho owner)
$pending_count = 0;
if ($is_owner) {
    $sql_pending = "SELECT COUNT(*) as count FROM class_requests WHERE class_id = '$class_id' AND status = 'pending'";
    $result_pending = mysqli_query($conn, $sql_pending);
    $row_pending = mysqli_fetch_assoc($result_pending);
    $pending_count = $row_pending['count'];
}

$manage_class_css_ver = @filemtime(__DIR__ . '/user_style.css');
$manage_class_js_ver = @filemtime(__DIR__ . '/xu_ly.js');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($class_data['class_name']); ?> - Quản lý lớp học</title>
    <link rel="stylesheet" href="user_style.css?v=<?php echo $manage_class_css_ver ?: time(); ?>"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="class-header">
            <div class="owner-badge-wrap">
                <div class="owner-badge">
                    <i class="fas fa-user-tie owner-badge-icon"></i>
                    <div>
                        <div class="owner-badge-label">Chủ phòng</div>
                        <div class="owner-badge-name"><?php echo htmlspecialchars($owner_name); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="class-header-row">
                <div>
                    <a href="user_dashboard.php" class="back-btn">
                        <i class="fas fa-chevron-left"></i> Quay lại
                    </a>
                    <h1 class="class-title"><i class="fas fa-school"></i> <?php echo htmlspecialchars($class_data['class_name']); ?></h1>
                    <div class="invite-code-badge">
                        Mã mời: <strong class="invite-code-value"><?php echo $class_data['invite_code']; ?></strong>
                    </div>
                </div>
                <?php if ($is_owner): ?>
                    <a href="delete_class.php?id=<?php echo $class_id; ?>" 
                       class="action-btn action-btn-danger"
                       onclick="return confirm('⚠️ CẢNH BÁO: Xóa lớp học sẽ xóa tất cả bài giảng, bài tập và bài nộp!\n\nBạn có chắc chắn muốn xóa lớp học này?')">
                        <i class="fas fa-trash-alt"></i> Xóa lớp học
                    </a>
                <?php elseif (!$is_admin && ($is_member || !$is_owner)): ?>
                    <div class="header-action-group">
                        <button onclick="openReportModal()" 
                           class="action-btn action-btn-warning" type="button">
                            <i class="fas fa-flag"></i> Báo cáo lớp học
                        </button>
                        <a href="leave_class.php?id=<?php echo $class_id; ?>" 
                           class="action-btn action-btn-danger action-btn-inline"
                           onclick="return confirm('⚠️ Bạn có chắc chắn muốn rời khỏi lớp học này?\n\nSau khi rời, bạn cần xin phê duyệt lại để tham gia.')">
                            <i class="fas fa-sign-out-alt"></i> Rời lớp học
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($is_owner && $pending_count > 0): ?>
        <div class="request-alert">
            <div class="request-alert-content">
                <div class="request-alert-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="request-alert-text">
                    <h4><i class="fas fa-exclamation-circle"></i> Yêu cầu tham gia mới!</h4>
                    <p>Có <strong><?php echo $pending_count; ?></strong> học viên đang chờ bạn phê duyệt tham gia lớp học này.</p>
                </div>
            </div>
            <a href="manage_requests.php?id=<?php echo $class_id; ?>" class="request-alert-btn">
                <i class="fas fa-tasks"></i> Xem yêu cầu (<?php echo $pending_count; ?>)
            </a>
        </div>
        <?php endif; ?>

        <?php if ($is_owner): ?>
            <section class="owner-tools">
                <h3 class="owner-tools-title"><i class="fas fa-toolbox"></i> Công cụ quản trị</h3>
                <div class="management-grid">
                    <div class="action-card" onclick="location.href='upload_lecture.php?id=<?php echo $class_id; ?>'">
                        <i class="fas fa-file-upload action-icon-blue"></i>
                        <strong>Đăng bài giảng</strong>
                    </div>
                    <div class="action-card" onclick="location.href='create_assignment.php?id=<?php echo $class_id; ?>'">
                        <i class="fas fa-robot action-icon-green"></i>
                        <strong> Bài tập </strong>
                    </div>
                    <div class="action-card" onclick="openMembersModal()">
                        <i class="fas fa-users-cog action-icon-purple"></i>
                        <strong>Học viên (<?php echo $total_members; ?>)</strong>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="section-box">
            <h3 class="section-title section-title-blue">
                <i class="fas fa-book-open"></i> Tài liệu bài học
            </h3>
            <?php if (mysqli_num_rows($result_lectures) > 0): ?>
                <?php while($lec = mysqli_fetch_assoc($result_lectures)): ?>
                    <div class="item-row">
                        <span>
                            <i class="fas fa-file-alt lecture-file-icon"></i>
                            <strong><?php echo htmlspecialchars($lec['title']); ?></strong>
                            <span class="badge <?php echo ($lec['file_type'] == 'pdf') ? 'badge-pdf' : 'badge-img'; ?>">
                                <?php echo strtoupper($lec['file_type']); ?>
                            </span>
                        </span>
                        <div class="inline-actions-row">
                            <a href="<?php echo $lec['file_url']; ?>" target="_blank" class="btn-sm btn-sm-blue">
                                <i class="fas fa-eye"></i> Xem tài liệu
                            </a>
                            <?php if ($is_admin || $is_owner): ?>
                                <a href="delete_lecture.php?id=<?php echo $lec['id']; ?>" class="btn-sm btn-sm-red" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa tài liệu này?')">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty-state-text">Chưa có tài liệu nào.</p>
            <?php endif; ?>
        </section>

        <section class="section-box">
            <h3 class="section-title section-title-green section-title-with-margin">
                <i class="fas fa-tasks"></i> Bài tập và kết quả
            </h3>
            <?php if (mysqli_num_rows($result_assignments) > 0): ?>
                <?php 
                mysqli_data_seek($result_assignments, 0);
                while($asm = mysqli_fetch_assoc($result_assignments)): 
                    // Lấy danh sách bài nộp cho bài tập này
                    $assignment_id = $asm['id'];
                    
                    // Biến để lưu trạng thái của học viên hiện tại
                    $my_submission = null;
                    $has_submitted = false;
                    
                    if ($is_admin || $is_owner) {
                        $sql_asm_subs = "SELECT s.*, u.full_name 
                                        FROM submissions s 
                                        JOIN users u ON s.user_id = u.id 
                                        WHERE s.assignment_id = '$assignment_id' 
                                        ORDER BY s.submitted_at DESC";
                        $result_asm_subs = mysqli_query($conn, $sql_asm_subs);
                        $submission_count = mysqli_num_rows($result_asm_subs);
                    } else {
                        // Học viên: kiểm tra xem đã nộp bài chưa
                        $sql_my_sub = "SELECT ai_score, submitted_at FROM submissions WHERE assignment_id = '$assignment_id' AND user_id = '$current_user_id' LIMIT 1";
                        $result_my_sub = mysqli_query($conn, $sql_my_sub);
                        if (mysqli_num_rows($result_my_sub) > 0) {
                            $has_submitted = true;
                            $my_submission = mysqli_fetch_assoc($result_my_sub);
                        }
                        
                        // Đếm tổng số bài nộp
                        $sql_count = "SELECT COUNT(*) as total FROM submissions WHERE assignment_id = '$assignment_id'";
                        $result_count = mysqli_query($conn, $sql_count);
                        $count_data = mysqli_fetch_assoc($result_count);
                        $submission_count = $count_data['total'];
                    }
                ?>
                    <div class="assignment-card">
                        <div class="assignment-card-header">
                            <div class="assignment-main">
                                <h4 class="assignment-title">
                                    <i class="fas fa-file-alt assignment-title-icon"></i> 
                                    <?php echo htmlspecialchars($asm['title']); ?>
                                    <?php if (isset($asm['difficulty'])): ?>
                                        <?php 
                                            $difficulty_badges = [
                                                'easy' => ['label' => 'Dễ', 'class' => 'difficulty-easy', 'icon' => '🟢'],
                                                'medium' => ['label' => 'Vừa', 'class' => 'difficulty-medium', 'icon' => '🟡'],
                                                'hard' => ['label' => 'Khó', 'class' => 'difficulty-hard', 'icon' => '🔴']
                                            ];
                                            $badge = $difficulty_badges[$asm['difficulty']] ?? $difficulty_badges['medium'];
                                        ?>
                                        <span class="difficulty-badge <?php echo $badge['class']; ?>">
                                            <?php echo $badge['icon'] . ' ' . $badge['label']; ?>
                                        </span>
                                    <?php endif; ?>
                                </h4>
                                <?php 
                                // Parse content để kiểm tra loại bài tập
                                $content_data = json_decode($asm['content'], true);
                                if ($content_data && isset($content_data['questions']) && is_array($content_data['questions'])):
                                    // Bài tập trắc nghiệm
                                    $total_q = count($content_data['questions']);
                                    $quiz_type = $content_data['type'] ?? 'multiple_choice';
                                    $difficulty = $content_data['difficulty'] ?? 'medium';
                                    $time_limit = $content_data['time_limit_minutes'] ?? 30;
                                    $total_points = $content_data['total_points'] ?? $total_q;
                                    
                                    $diff_labels = ['easy' => 'Dễ', 'medium' => 'TB', 'hard' => 'Khó'];
                                ?>
                                    <div class="quiz-stats-row">
                                        <span class="quiz-stat-badge quiz-stat-questions">
                                            <i class="fas fa-question-circle"></i> <?php echo $total_q; ?> câu hỏi
                                        </span>
                                        <span class="quiz-stat-badge quiz-stat-difficulty-<?php echo $difficulty; ?>">
                                            <i class="fas fa-signal"></i> <?php echo $diff_labels[$difficulty]; ?>
                                        </span>
                                        <span class="quiz-stat-badge quiz-stat-time">
                                            <i class="fas fa-clock"></i> <?php echo $time_limit; ?> phút
                                        </span>
                                        <span class="quiz-stat-badge quiz-stat-points">
                                            <i class="fas fa-star"></i> Tổng <?php echo $total_points; ?> điểm
                                        </span>
                                    </div>
                                    <p class="assignment-meta-text assignment-meta-italic">
                                        <i class="fas fa-robot"></i> Bài trắc nghiệm tự động từ AI - 
                                        Nguồn: <?php echo htmlspecialchars($content_data['source_lecture_title'] ?? 'Tài liệu lớp học'); ?>
                                    </p>
                                <?php else: ?>
                                    <!-- Bài tập tự luận -->
                                    <p class="assignment-meta-text assignment-meta-bottom-gap">
                                        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars(mb_substr($asm['content'], 0, 200)); ?><?php echo mb_strlen($asm['content']) > 200 ? '...' : ''; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php 
                                // Kiểm tra deadline
                                $is_expired = false;
                                $deadline_info = '';
                                if (!empty($asm['deadline'])) {
                                    $deadline_timestamp = strtotime($asm['deadline']);
                                    $is_expired = (time() > $deadline_timestamp);
                                    $deadline_formatted = date('d/m/Y H:i', $deadline_timestamp);
                                    
                                    if ($is_expired) {
                                        $deadline_info = '<span class="deadline-badge deadline-expired">
                                            <i class="fas fa-calendar-times"></i> Đã hết hạn: ' . $deadline_formatted . '
                                        </span>';
                                    } else {
                                        $time_left = $deadline_timestamp - time();
                                        $hours_left = floor($time_left / 3600);
                                        $days_left = floor($hours_left / 24);
                                        
                                        if ($days_left > 0) {
                                            $deadline_info = '<span class="deadline-badge deadline-normal">
                                                <i class="fas fa-calendar-check"></i> Hạn nộp: ' . $deadline_formatted . ' (còn ' . $days_left . ' ngày)
                                            </span>';
                                        } elseif ($hours_left > 0) {
                                            $deadline_info = '<span class="deadline-badge deadline-soon">
                                                <i class="fas fa-clock"></i> Hạn nộp: ' . $deadline_formatted . ' (còn ' . $hours_left . ' giờ)
                                            </span>';
                                        } else {
                                            $minutes_left = floor($time_left / 60);
                                            $deadline_info = '<span class="deadline-badge deadline-urgent">
                                                <i class="fas fa-exclamation-triangle"></i> Sắp hết hạn: ' . $deadline_formatted . ' (còn ' . $minutes_left . ' phút)
                                            </span>';
                                        }
                                    }
                                }
                                ?>
                                
                                <div class="assignment-chips-row">
                                    <span class="info-chip info-chip-success">
                                        <i class="fas fa-paper-plane"></i> <?php echo $submission_count; ?> bài nộp
                                    </span>
                                    <?php if ($is_admin || $is_owner): ?>
                                        <span class="info-chip info-chip-warning">
                                            <i class="fas fa-users"></i> <?php echo $total_members; ?> học viên
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($deadline_info)): ?>
                                        <?php echo $deadline_info; ?>
                                    <?php endif; ?>
                                    
                                    <?php if (!($is_admin || $is_owner) && $has_submitted): ?>
                                        <span class="status-chip status-done">
                                            <i class="fas fa-check-circle"></i> Đã hoàn thành
                                        </span>
                                        <?php if ($my_submission && $my_submission['ai_score'] !== null): ?>
                                            <span class="status-chip status-score">
                                                <i class="fas fa-star"></i> <?php echo number_format($my_submission['ai_score'], 2); ?>/10
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif (!($is_admin || $is_owner) && !$has_submitted): ?>
                                        <?php if ($is_expired): ?>
                                            <span class="status-chip status-expired">
                                                <i class="fas fa-times-circle"></i> Đã hết hạn
                                            </span>
                                        <?php else: ?>
                                            <span class="status-chip status-pending">
                                                <i class="fas fa-exclamation-circle"></i> Chưa làm
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="assignment-actions">
                                <?php if ($is_admin || $is_owner): ?>
                                    <a href="view_assignment_detail.php?id=<?php echo $asm['id']; ?>" 
                                       class="btn-sm btn-sm-blue" title="Xem bài tập đã tạo">
                                        <i class="fas fa-eye"></i> Xem đề bài
                                    </a>
                                    <?php if ($submission_count > 0): ?>
                                        <a href="export_grades.php?assignment_id=<?php echo $asm['id']; ?>" 
                                           class="btn-sm btn-sm-teal" title="Xuất điểm Excel">
                                            <i class="fas fa-file-excel"></i> Xuất điểm
                                        </a>
                                    <?php endif; ?>
                                    <a href="delete_assignment.php?id=<?php echo $asm['id']; ?>" 
                                       class="btn-sm btn-sm-red" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa bài kiểm tra này? Tất cả bài nộp sẽ bị xóa!')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <?php if ($has_submitted): ?>
                                        <a href="view_assignment_detail.php?id=<?php echo $asm['id']; ?>" 
                                           class="btn-sm btn-sm-blue" title="Xem kết quả chi tiết">
                                            <i class="fas fa-eye"></i> Xem chi tiết
                                        </a>
                                    <?php elseif ($is_expired): ?>
                                        <!-- Bài tập đã hết hạn -->
                                        <a href="view_assignment_detail.php?id=<?php echo $asm['id']; ?>" 
                                           class="btn-sm btn-sm-gray" title="Đã hết hạn nộp bài - Chỉ xem đề">
                                            <i class="fas fa-eye"></i> Xem đề (Hết hạn)
                                        </a>
                                    <?php else: ?>
                                        <!-- Chưa làm và còn hạn -->
                                        <a href="view_assignment_detail.php?id=<?php echo $asm['id']; ?>" 
                                           class="btn-sm btn-sm-green" title="Bắt đầu làm bài">
                                            <i class="fas fa-edit"></i> Làm bài ngay
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!($is_admin || $is_owner) && $has_submitted && $my_submission): ?>
                            <div class="my-result-box">
                                <div class="my-result-content">
                                    <div>
                                        <div class="my-result-title">
                                            <i class="fas fa-check-double"></i> Kết quả bài làm của bạn
                                        </div>
                                        <div class="my-result-time">
                                            <i class="fas fa-clock"></i> Đã nộp lúc: <strong><?php echo date('d/m/Y H:i', strtotime($my_submission['submitted_at'])); ?></strong>
                                        </div>
                                    </div>
                                    <?php if ($my_submission['ai_score'] !== null): ?>
                                        <div class="my-result-score-wrap">
                                            <div class="my-result-score-label">Điểm AI chấm</div>
                                            <div class="my-result-score-value">
                                                <?php echo number_format($my_submission['ai_score'], 2); ?><span class="my-result-score-max">/10</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="my-result-waiting">
                                            <i class="fas fa-hourglass-half"></i> Đang chờ AI chấm điểm...
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($submission_count > 0 && ($is_admin || $is_owner)): ?>
                            <div class="submission-list-box">
                                <h5 class="submission-list-title">
                                    <i class="fas fa-graduation-cap"></i> Danh sách bài nộp
                                </h5>
                                <table class="submission-table">
                                    <thead>
                                        <tr>
                                            <th>Học viên</th>
                                            <th>Điểm</th>
                                            <th>Thời gian nộp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($sub = mysqli_fetch_assoc($result_asm_subs)): ?>
                                            <tr>
                                                <td>
                                                    <i class="fas fa-user"></i> 
                                                    <strong><?php echo htmlspecialchars($sub['full_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($sub['ai_score'] !== null): ?>
                                                        <strong class="score-good">
                                                            <?php echo number_format($sub['ai_score'], 2); ?>/10
                                                        </strong>
                                                    <?php else: ?>
                                                        <span class="score-empty">Chưa có điểm</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="submission-time-cell">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('d/m/Y H:i', strtotime($sub['submitted_at'])); ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($submission_count == 0 && ($is_admin || $is_owner)): ?>
                            <div class="no-submission-box">
                                <i class="fas fa-inbox"></i> Chưa có bài nộp nào
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-assignment-state">
                    <i class="fas fa-clipboard-list empty-assignment-icon"></i>
                    <p class="empty-assignment-text">Chưa có bài tập nào.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Modal hiển thị thành viên -->
    <div id="membersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeMembersModal()">&times;</span>
                <h2 class="modal-title"><i class="fas fa-users"></i> Thành viên lớp học (<?php echo $total_members; ?>)</h2>
            </div>
            <div class="member-modal-toolbar">
                <a href="manage_requests.php?id=<?php echo $class_id; ?>" 
                   class="member-request-btn">
                    <i class="fas fa-user-clock"></i> Xem yêu cầu vào lớp
                </a>
            </div>
            <div class="modal-body">
                <?php 
                mysqli_data_seek($result_members, 0); // Reset pointer
                while($member = mysqli_fetch_assoc($result_members)): 
                    $initial = mb_substr($member['full_name'], 0, 1, 'UTF-8');
                ?>
                    <div class="member-item">
                        <div class="member-avatar"><?php echo strtoupper($initial); ?></div>
                        <div class="member-info">
                            <div class="member-name">
                                <?php echo htmlspecialchars($member['full_name']); ?>
                                <?php if ($member['is_owner']): ?>
                                    <span class="badge-owner"><i class="fas fa-crown"></i> Chủ phòng</span>
                                <?php endif; ?>
                                <?php if ($member['is_current_user']): ?>
                                    <span class="badge badge-current-user">Bạn</span>
                                <?php endif; ?>
                            </div>
                            <div class="member-email">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($member['email']); ?>
                                <span class="badge member-role-badge <?php echo ($member['role'] == 'teacher') ? 'badge-teacher' : 'badge-student'; ?>">
                                    <?php echo ($member['role'] == 'teacher') ? 'Giáo viên' : 'Học viên'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Modal báo cáo lớp học -->
    <div id="reportModal" class="report-modal">
        <div class="report-modal-content">
            <div class="report-modal-header">
                <span class="close" onclick="closeReportModal()">&times;</span>
                <h2 class="modal-title"><i class="fas fa-flag"></i> Báo cáo lớp học</h2>
            </div>
            <div class="report-modal-body">
                <p class="report-description">Vui lòng mô tả chi tiết vấn đề bạn gặp phải với lớp học này. Chúng tôi sẽ xem xét và xử lý báo cáo của bạn.</p>
                <form id="reportForm" onsubmit="submitReport(event)">
                    <div class="report-form-group">
                        <label for="reportReason">
                            <i class="fas fa-edit"></i> Lý do báo cáo <span class="required-mark">*</span>
                        </label>
                        <textarea 
                            id="reportReason" 
                            name="reason" 
                            placeholder="Ví dụ: Nội dung không phù hợp, sai sự thật, spam, vi phạm quy định..."
                            required
                        ></textarea>
                    </div>
                    <div class="report-btn-group">
                        <button type="button" class="report-btn report-btn-cancel" onclick="closeReportModal()">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                        <button type="submit" class="report-btn report-btn-submit">
                            <i class="fas fa-paper-plane"></i> Gửi báo cáo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="manageClassConfig" data-class-id="<?php echo $class_id; ?>" hidden></div>
    <script src="xu_ly.js?v=<?php echo $manage_class_js_ver ?: time(); ?>"></script>
</body>
</html>