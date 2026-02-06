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

// Kiểm tra quyền truy cập (phải là owner hoặc member)
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

// Lấy danh sách bài giảng
$sql_lectures = "SELECT * FROM lectures WHERE class_id = '$class_id' ORDER BY created_at DESC";
$result_lectures = mysqli_query($conn, $sql_lectures);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài liệu - <?php echo htmlspecialchars($class_data['class_name']); ?></title>
    <link rel="stylesheet" href="user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .lecture-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: 0.3s;
        }
        .lecture-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .lecture-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .lecture-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .lecture-actions {
            display: flex;
            gap: 10px;
        }
        .btn-view, .btn-report {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-view:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
        }
        .btn-report {
            background: #dc3545;
            color: white;
        }
        .btn-report:hover {
            background: #c82333;
        }
        .lecture-info {
            color: #777;
            font-size: 14px;
            margin-top: 10px;
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
            <h1><i class="fas fa-book"></i> Tài liệu học tập</h1>
            <p class="subtitle">Lớp: <strong><?php echo htmlspecialchars($class_data['class_name']); ?></strong></p>
        </div>

        <?php if (mysqli_num_rows($result_lectures) > 0): ?>
            <?php while($lecture = mysqli_fetch_assoc($result_lectures)): ?>
                <div class="lecture-item">
                    <div class="lecture-header">
                        <div class="lecture-title">
                            <i class="fas fa-file-pdf"></i> <?php echo htmlspecialchars($lecture['title']); ?>
                        </div>
                        <div class="lecture-actions">
                            <a href="<?php echo htmlspecialchars($lecture['file_url']); ?>" target="_blank" class="btn-view">
                                <i class="fas fa-eye"></i> Xem tài liệu
                            </a>
                            <button class="btn-report" onclick="reportLecture(<?php echo $lecture['id']; ?>)">
                                <i class="fas fa-flag"></i> Report
                            </button>
                        </div>
                    </div>
                    <div class="lecture-info">
                        <i class="fas fa-calendar"></i> Đăng ngày: <?php echo date('d/m/Y H:i', strtotime($lecture['created_at'])); ?>
                    </div>
                    <?php if ($lecture['description']): ?>
                        <div class="lecture-info">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($lecture['description']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h2>Chưa có tài liệu nào</h2>
                <p>Giáo viên chưa đăng tải tài liệu học tập cho lớp này.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function reportLecture(lectureId) {
            const reason = prompt("Nhập lý do báo cáo bài giảng này:");
            if (reason && reason.trim() !== '') {
                fetch('report_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `lecture_id=${lectureId}&reason=${encodeURIComponent(reason)}&class_id=<?php echo $class_id; ?>`
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
