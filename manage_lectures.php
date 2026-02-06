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

// Kiểm tra quyền chủ phòng
if ($class_data['owner_id'] != $current_user_id) {
    die("Bạn không có quyền quản lý lớp học này!");
}

// Xử lý xóa bài giảng
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $sql_delete = "DELETE FROM lectures WHERE id = '$delete_id' AND class_id = '$class_id'";
    if (mysqli_query($conn, $sql_delete)) {
        echo "<script>alert('Đã xóa bài giảng!'); window.location='manage_lectures.php?id=$class_id';</script>";
    }
}

// Lấy danh sách bài giảng
$sql_lectures = "SELECT * FROM lectures WHERE class_id = '$class_id' ORDER BY created_at DESC";
$result_lectures = mysqli_query($conn, $sql_lectures);

// Đếm số lượng bài giảng
$total_lectures = mysqli_num_rows($result_lectures);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bài giảng - <?php echo htmlspecialchars($class_data['class_name']); ?></title>
    <link rel="stylesheet" href="user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        .stats-card h3 {
            margin: 0;
            font-size: 48px;
        }
        .stats-card p {
            margin: 10px 0 0 0;
            font-size: 16px;
        }
        .lecture-item {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
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
            margin-bottom: 15px;
        }
        .lecture-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lecture-info {
            color: #777;
            font-size: 14px;
            margin: 8px 0;
        }
        .lecture-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-action {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-view:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
        }
        .btn-edit {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .btn-edit:hover {
            background: linear-gradient(135deg, #d67fe2 0%, #e04456 100%);
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .btn-upload {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 30px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-upload:hover {
            background: linear-gradient(135deg, #0e8073 0%, #2dd168 100%);
            transform: translateY(-2px);
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
        .file-type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-pdf {
            background: #ff4757;
            color: white;
        }
        .badge-doc {
            background: #2e86de;
            color: white;
        }
        .badge-image {
            background: #26de81;
            color: white;
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
            <h1><i class="fas fa-folder-open"></i> Quản lý bài giảng</h1>
            <p class="subtitle">Lớp: <strong><?php echo htmlspecialchars($class_data['class_name']); ?></strong></p>
        </div>

        <div class="stats-card">
            <h3><?php echo $total_lectures; ?></h3>
            <p><i class="fas fa-file-alt"></i> Tổng số bài giảng đã đăng tải</p>
        </div>

        <a href="upload_lecture.php?id=<?php echo $class_id; ?>" class="btn-upload">
            <i class="fas fa-cloud-upload-alt"></i> Đăng tải bài giảng mới
        </a>

        <?php if (mysqli_num_rows($result_lectures) > 0): ?>
            <?php while($lecture = mysqli_fetch_assoc($result_lectures)): ?>
                <?php
                // Xác định loại file
                $file_url = $lecture['file_url'];
                $file_extension = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
                
                if (in_array($file_extension, ['pdf'])) {
                    $badge_class = 'badge-pdf';
                    $badge_text = 'PDF';
                } elseif (in_array($file_extension, ['doc', 'docx'])) {
                    $badge_class = 'badge-doc';
                    $badge_text = 'DOCX';
                } elseif (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $badge_class = 'badge-image';
                    $badge_text = 'IMAGE';
                } else {
                    $badge_class = 'badge-doc';
                    $badge_text = 'FILE';
                }
                ?>
                <div class="lecture-item">
                    <div class="lecture-header">
                        <div class="lecture-title">
                            <i class="fas fa-file-alt"></i> 
                            <?php echo htmlspecialchars($lecture['title']); ?>
                            <span class="file-type-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                        </div>
                    </div>
                    
                    <div class="lecture-info">
                        <i class="fas fa-calendar-plus"></i> Đăng ngày: <?php echo date('d/m/Y H:i', strtotime($lecture['created_at'])); ?>
                    </div>
                    
                    <?php if ($lecture['description']): ?>
                        <div class="lecture-info">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($lecture['description']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="lecture-info">
                        <i class="fas fa-link"></i> URL: 
                        <a href="<?php echo htmlspecialchars($lecture['file_url']); ?>" target="_blank" style="color: #667eea;">
                            <?php echo substr($lecture['file_url'], 0, 60); ?>...
                        </a>
                    </div>
                    
                    <div class="lecture-actions">
                        <a href="<?php echo htmlspecialchars($lecture['file_url']); ?>" target="_blank" class="btn-action btn-view">
                            <i class="fas fa-eye"></i> Xem tài liệu
                        </a>
                        <a href="edit_lecture.php?id=<?php echo $lecture['id']; ?>&class_id=<?php echo $class_id; ?>" class="btn-action btn-edit">
                            <i class="fas fa-edit"></i> Chỉnh sửa
                        </a>
                        <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $lecture['id']; ?>)">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h2>Chưa có bài giảng nào</h2>
                <p>Hãy đăng tải bài giảng đầu tiên cho lớp học này.</p>
                <a href="upload_lecture.php?id=<?php echo $class_id; ?>" class="btn-upload" style="margin-top: 20px;">
                    <i class="fas fa-cloud-upload-alt"></i> Đăng tải ngay
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function confirmDelete(lectureId) {
            if (confirm('Bạn có chắc chắn muốn xóa bài giảng này?\nHành động này không thể hoàn tác!')) {
                window.location.href = 'manage_lectures.php?id=<?php echo $class_id; ?>&delete_id=' + lectureId;
            }
        }
    </script>
</body>
</html>
