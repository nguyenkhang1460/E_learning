<?php
session_start();
include 'db_config.php';

// Chỉ giảng viên và admin mới xem được trang này
if (!isset($_SESSION['user_id'])) die("Bạn cần đăng nhập!");

$owner_id = $_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Nếu có class_id, chỉ lấy yêu cầu của lớp đó
if ($class_id > 0) {
    if ($is_admin) {
        // Admin có thể xem tất cả yêu cầu
        $sql = "SELECT r.id, r.answers, r.user_id, r.class_id, r.created_at, c.class_name, u.full_name, u.email 
                FROM class_requests r 
                JOIN classes c ON r.class_id = c.id 
                JOIN users u ON r.user_id = u.id
                WHERE r.status = 'pending' AND r.class_id = $class_id
                ORDER BY r.created_at DESC";
        
        // Lấy thông tin lớp học
        $class_info_sql = "SELECT class_name FROM classes WHERE id = $class_id";
    } else {
        // Owner chỉ xem yêu cầu của lớp mình quản lý
        $sql = "SELECT r.id, r.answers, r.user_id, r.class_id, r.created_at, c.class_name, u.full_name, u.email 
                FROM class_requests r 
                JOIN classes c ON r.class_id = c.id 
                JOIN users u ON r.user_id = u.id
                WHERE c.owner_id = $owner_id AND r.status = 'pending' AND r.class_id = $class_id
                ORDER BY r.created_at DESC";
        
        // Lấy thông tin lớp học
        $class_info_sql = "SELECT class_name FROM classes WHERE id = $class_id AND owner_id = $owner_id";
    }
    $class_info_result = mysqli_query($conn, $class_info_sql);
    $class_info = mysqli_fetch_assoc($class_info_result);
} else {
    if ($is_admin) {
        // Admin xem tất cả yêu cầu của tất cả các lớp
        $sql = "SELECT r.id, r.answers, r.user_id, r.class_id, r.created_at, c.class_name, u.full_name, u.email 
                FROM class_requests r 
                JOIN classes c ON r.class_id = c.id 
                JOIN users u ON r.user_id = u.id
                WHERE r.status = 'pending'
                ORDER BY r.created_at DESC";
    } else {
        // Owner chỉ xem yêu cầu của các lớp do mình làm chủ
        $sql = "SELECT r.id, r.answers, r.user_id, r.class_id, r.created_at, c.class_name, u.full_name, u.email 
                FROM class_requests r 
                JOIN classes c ON r.class_id = c.id 
                JOIN users u ON r.user_id = u.id
                WHERE c.owner_id = $owner_id AND r.status = 'pending'
                ORDER BY r.created_at DESC";
    }
}

$result = mysqli_query($conn, $sql);
$total_requests = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt yêu cầu tham gia - AI Learning</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="manage-requests-page">
    <div class="requests-container">
        <a href="<?php echo $class_id > 0 ? 'manage_class.php?id=' . $class_id : 'user_dashboard.php'; ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Quay lại <?php echo $class_id > 0 ? 'Lớp học' : 'Dashboard'; ?>
        </a>
        
        <div class="page-header">
            <h1>
                <i class="fas fa-user-clock"></i>
                Yêu cầu tham gia lớp học
            </h1>
            <p>
                <?php if ($class_id > 0 && isset($class_info)): ?>
                    Lớp: <strong><?php echo htmlspecialchars($class_info['class_name']); ?></strong>
                <?php else: ?>
                    Quản lý tất cả yêu cầu đang chờ duyệt
                <?php endif; ?>
            </p>
        </div>
        
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_requests; ?></h3>
                    <p>Yêu cầu đang chờ</p>
                </div>
            </div>
            
            <?php if ($total_requests > 0): ?>
                <div class="manage-requests-tip">
                    <i class="fas fa-info-circle"></i> Hãy xem xét kỹ thông tin trước khi phê duyệt
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($total_requests > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): 
                $answers = json_decode($row['answers'], true);
                $initial = mb_substr($row['full_name'], 0, 1, 'UTF-8');
                
                // Tính thời gian đã yêu cầu
                $time_diff = time() - strtotime($row['created_at']);
                if ($time_diff < 3600) {
                    $time_ago = floor($time_diff / 60) . ' phút trước';
                } elseif ($time_diff < 86400) {
                    $time_ago = floor($time_diff / 3600) . ' giờ trước';
                } else {
                    $time_ago = floor($time_diff / 86400) . ' ngày trước';
                }
            ?>
                <div class="request-card">
                    <div class="request-header">
                        <div class="student-info">
                            <div class="student-avatar"><?php echo strtoupper($initial); ?></div>
                            <div class="student-details">
                                <h3><?php echo htmlspecialchars($row['full_name']); ?></h3>
                                <p>
                                    <i class="fas fa-envelope"></i>
                                    <?php echo htmlspecialchars($row['email']); ?>
                                </p>
                                <div class="time-badge">
                                    <i class="fas fa-clock"></i>
                                    Yêu cầu <?php echo $time_ago; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($class_id == 0): ?>
                            <span class="class-badge">
                                <i class="fas fa-school"></i>
                                <?php echo htmlspecialchars($row['class_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($answers && count($answers) > 0): ?>
                        <div class="answers-section">
                            <h4>
                                <i class="fas fa-clipboard-check"></i>
                                Thông tin khai báo
                            </h4>
                            <?php foreach ($answers as $label => $value): ?>
                                <div class="answer-item">
                                    <span class="answer-label"><?php echo htmlspecialchars($label); ?>:</span>
                                    <span class="answer-value"><?php echo htmlspecialchars($value); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="action-buttons">
                        <a href="approve_action.php?id=<?php echo $row['id']; ?>&status=rejected<?php echo $class_id > 0 ? '&class_id=' . $class_id : ''; ?>" 
                           class="btn btn-reject js-confirm-action"
                           data-confirm-message="❌ Bạn có chắc muốn từ chối yêu cầu của <?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?>?">
                            <i class="fas fa-times-circle"></i>
                            Từ chối
                        </a>
                        <a href="approve_action.php?id=<?php echo $row['id']; ?>&status=approved<?php echo $class_id > 0 ? '&class_id=' . $class_id : ''; ?>" 
                           class="btn btn-approve">
                            <i class="fas fa-check-circle"></i>
                            Phê duyệt
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Không có yêu cầu nào đang chờ</h3>
                <p>Tất cả yêu cầu tham gia đã được xử lý hoặc chưa có yêu cầu mới</p>
            </div>
        <?php endif; ?>
    </div>
    <script src="xu_ly.js"></script>
</body>
</html>