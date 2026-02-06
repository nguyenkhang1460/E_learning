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

// Kiểm tra quyền truy cập
$is_owner = ($class_data['owner_id'] == $current_user_id);
$is_member = false;
if (!$is_owner) {
    $sql_member = "SELECT id FROM class_members WHERE user_id = '$current_user_id' AND class_id = '$class_id'";
    $res_member = mysqli_query($conn, $sql_member);
    if (mysqli_num_rows($res_member) > 0) {
        $is_member = true;
    }
}

if (!$is_owner && !$is_member) {
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
    <link rel="stylesheet" href="user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .assignment-item {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        .assignment-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .assignment-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .assignment-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .assignment-info {
            color: #777;
            font-size: 14px;
            margin: 8px 0;
        }
        .assignment-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        .btn-do, .btn-report {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-do {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        .btn-do:hover {
            background: linear-gradient(135deg, #0e8073 0%, #2dd168 100%);
        }
        .btn-report {
            background: #dc3545;
            color: white;
        }
        .btn-report:hover {
            background: #c82333;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
        }
        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
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
                    
                    <?php if ($has_submitted && $submission['score'] !== null): ?>
                        <div class="assignment-info" style="color: #28a745; font-weight: bold;">
                            <i class="fas fa-check-circle"></i> Điểm: <?php echo $submission['score']; ?>/100
                        </div>
                    <?php endif; ?>
                    
                    <div class="assignment-actions">
                        <a href="do_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn-do">
                            <i class="fas fa-pen"></i> <?php echo $has_submitted ? 'Xem lại bài làm' : 'Làm bài tập'; ?>
                        </a>
                        <button class="btn-report" onclick="reportAssignment(<?php echo $assignment['id']; ?>)">
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

    <script>
        function reportAssignment(assignmentId) {
            const reason = prompt("Nhập lý do báo cáo bài tập này:");
            if (reason && reason.trim() !== '') {
                fetch('report_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `assignment_id=${assignmentId}&reason=${encodeURIComponent(reason)}&class_id=<?php echo $class_id; ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Báo cáo của bạn đã được gửi!');
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Đã xảy ra lỗi khi gửi báo cáo!');
                });
            }
        }
    </script>
</body>
</html>
