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

// 3. Xác định vai trò (Chủ phòng hay Thành viên)
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

// Danh sách bài nộp (Nếu là GV thì xem tất cả, nếu là HV thì chỉ xem bài của mình)
if ($is_owner) {
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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($class_data['class_name']); ?> - Quản lý lớp học</title>
    <link rel="stylesheet" href="user_style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .management-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .action-card { background: white; padding: 20px; border-radius: 12px; text-align: center; cursor: pointer; border: 1px solid #eee; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .action-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .action-card i { font-size: 35px; margin-bottom: 10px; display: block; }
        
        .section-box { background: white; padding: 20px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .item-row { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #f8f9fa; }
        .item-row:last-child { border-bottom: none; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.8em; font-weight: bold; }
        .badge-pdf { background: #ffebee; color: #c62828; }
        .badge-img { background: #e3f2fd; color: #1565c1; }
        .btn-sm { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85em; font-weight: bold; }
        
        /* Modal styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 5% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 700px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .modal-header { padding: 20px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px 12px 0 0; }
        .modal-body { padding: 30px; max-height: 500px; overflow-y: auto; }
        .close { color: white; float: right; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 20px; }
        .close:hover { opacity: 0.8; }
        .member-item { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #f0f0f0; transition: 0.3s; }
        .member-item:hover { background-color: #f8f9fa; }
        .member-item:last-child { border-bottom: none; }
        .member-avatar { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2em; margin-right: 15px; }
        .member-info { flex: 1; }
        .member-name { font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
        .member-email { color: #7f8c8d; font-size: 0.9em; }
        .badge-owner { background: #f39c12; color: white; padding: 3px 10px; border-radius: 15px; font-size: 0.75em; margin-left: 10px; }
        .badge-teacher { background: #3498db; color: white; }
        .badge-student { background: #95a5a6; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <header style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <a href="user_dashboard.php" style="color: #666; text-decoration: none;"><i class="fas fa-chevron-left"></i> Quay lại Dashboard</a>
                    <h1 style="margin-top: 15px; color: #2c3e50;"><i class="fas fa-school"></i> <?php echo htmlspecialchars($class_data['class_name']); ?></h1>
                    <div style="background: #e9ecef; display: inline-block; padding: 5px 15px; border-radius: 5px;">
                        Mã mời: <strong style="color: #007bff;"><?php echo $class_data['invite_code']; ?></strong>
                    </div>
                </div>
                <?php if ($is_owner): ?>
                    <a href="delete_class.php?id=<?php echo $class_id; ?>" 
                       style="background: #e74c3c; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold;"
                       onclick="return confirm('⚠️ CẢNH BÁO: Xóa lớp học sẽ xóa tất cả bài giảng, bài tập và bài nộp!\n\nBạn có chắc chắn muốn xóa lớp học này?')">
                        <i class="fas fa-trash-alt"></i> Xóa lớp học
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($is_owner): ?>
            <section class="owner-tools">
                <h3 style="color: #f39c12;"><i class="fas fa-toolbox"></i> Công cụ quản trị</h3>
                <div class="management-grid">
                    <div class="action-card" onclick="location.href='upload_lecture.php?id=<?php echo $class_id; ?>'">
                        <i class="fas fa-file-upload" style="color: #3498db;"></i>
                        <strong>Đăng bài giảng</strong>
                    </div>
                    <div class="action-card" onclick="location.href='create_assignment.php?id=<?php echo $class_id; ?>'">
                        <i class="fas fa-robot" style="color: #27ae60;"></i>
                        <strong>Tạo bài tập AI</strong>
                    </div>
                    <div class="action-card" onclick="openMembersModal()">
                        <i class="fas fa-users-cog" style="color: #9b59b6;"></i>
                        <strong>Học viên (<?php echo $total_members; ?>)</strong>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="section-box">
            <h3 style="color: #3498db; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
                <i class="fas fa-book-open"></i> Tài liệu bài học
            </h3>
            <?php if (mysqli_num_rows($result_lectures) > 0): ?>
                <?php while($lec = mysqli_fetch_assoc($result_lectures)): ?>
                    <div class="item-row">
                        <span>
                            <i class="fas fa-file-alt" style="margin-right: 10px; color: #bdc3c7;"></i>
                            <strong><?php echo htmlspecialchars($lec['title']); ?></strong>
                            <span class="badge <?php echo ($lec['file_type'] == 'pdf') ? 'badge-pdf' : 'badge-img'; ?>">
                                <?php echo strtoupper($lec['file_type']); ?>
                            </span>
                        </span>
                        <div style="display: flex; gap: 10px;">
                            <a href="<?php echo $lec['file_url']; ?>" target="_blank" class="btn-sm" style="background: #3498db; color: white;">
                                <i class="fas fa-eye"></i> Xem tài liệu
                            </a>
                            <?php if ($is_owner): ?>
                                <a href="delete_lecture.php?id=<?php echo $lec['id']; ?>" class="btn-sm" style="background: #e74c3c; color: white;" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa tài liệu này?')">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #95a5a6; padding: 20px;">Chưa có tài liệu nào.</p>
            <?php endif; ?>
        </section>

        <section class="section-box">
            <h3 style="color: #27ae60; border-bottom: 2px solid #27ae60; padding-bottom: 10px;">
                <i class="fas fa-tasks"></i> Bài tập tự luận AI
            </h3>
            <?php if (mysqli_num_rows($result_assignments) > 0): ?>
                <?php while($asm = mysqli_fetch_assoc($result_assignments)): ?>
                    <div class="item-row">
                        <div>
                            <strong><?php echo htmlspecialchars($asm['title']); ?></strong><br>
                            <small style="color: #7f8c8d;"><?php echo htmlspecialchars($asm['content']); ?></small>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <?php if ($is_owner): ?>
                                <a href="<?php echo $asm['answer_key_url']; ?>" target="_blank" title="Xem đáp án chuẩn" style="color: #f39c12; font-size: 1.2em;">
                                    <i class="fas fa-key"></i>
                                </a>
                            <?php endif; ?>
                            <a href="view_assignment_detail.php?id=<?php echo $asm['id']; ?>" class="btn-sm" style="background: #27ae60; color: white;">
                                <i class="fas fa-edit"></i> Làm bài
                            </a>
                            <?php if ($is_owner): ?>
                                <a href="delete_assignment.php?id=<?php echo $asm['id']; ?>" class="btn-sm" style="background: #e74c3c; color: white;" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa bài kiểm tra này? Tất cả bài nộp sẽ bị xóa!')">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #95a5a6; padding: 20px;">Chưa có bài tập nào.</p>
            <?php endif; ?>
        </section>

        <section class="section-box">
            <h3 style="color: #e67e22; border-bottom: 2px solid #e67e22; padding-bottom: 10px;">
                <i class="fas fa-graduation-cap"></i> Kết quả chấm điểm AI
            </h3>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid #eee; color: #7f8c8d;">
                        <th style="padding: 10px;">Bài tập / Người nộp</th>
                        <th>File bài làm</th>
                        <th>Điểm AI</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($sub = mysqli_fetch_assoc($result_submissions)): ?>
                        <tr style="border-bottom: 1px solid #f8f9fa;">
                            <td style="padding: 12px;">
                                <strong><?php echo htmlspecialchars($sub['asm_title']); ?></strong><br>
                                <small style="color: #95a5a6;"><?php echo $is_owner ? htmlspecialchars($sub['full_name']) : 'Bạn đã nộp'; ?></small>
                            </td>
                            <td>
                                <a href="<?php echo $sub['student_file_url']; ?>" target="_blank" style="color: #3498db;">
                                    <i class="fas fa-file-download"></i> Xem file
                                </a>
                            </td>
                            <td>
                                <strong style="color: #e74c3c; font-size: 1.1em;"><?php echo $sub['ai_score'] ?? '--'; ?></strong>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <?php if ($is_owner && $sub['ai_score'] === null): ?>
                                        <a href="ai_grading_process.php?sub_id=<?php echo $sub['id']; ?>&class_id=<?php echo $class_id; ?>" 
                                           class="btn-sm" style="background: #e67e22; color: white;">
                                           <i class="fas fa-robot"></i> Chấm ngay
                                        </a>
                                    <?php elseif ($sub['ai_feedback']): ?>
                                        <button onclick="alert('Nhận xét từ AI: <?php echo addslashes($sub['ai_feedback']); ?>')" 
                                                class="btn-sm" style="background: #95a5a6; color: white; border:none; cursor:pointer;">
                                            Xem nhận xét
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($is_owner || $sub['user_id'] == $current_user_id): ?>
                                        <a href="delete_submission.php?id=<?php echo $sub['id']; ?>" class="btn-sm" style="background: #e74c3c; color: white;" 
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa bài nộp này?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- Modal hiển thị thành viên -->
    <div id="membersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeMembersModal()">&times;</span>
                <h2 style="margin: 0;"><i class="fas fa-users"></i> Thành viên lớp học (<?php echo $total_members; ?>)</h2>
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
                                    <span class="badge" style="background: #e3f2fd; color: #1976d2;">Bạn</span>
                                <?php endif; ?>
                            </div>
                            <div class="member-email">
                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($member['email']); ?>
                                <span class="badge <?php echo ($member['role'] == 'teacher') ? 'badge-teacher' : 'badge-student'; ?>" style="margin-left: 10px;">
                                    <?php echo ($member['role'] == 'teacher') ? 'Giáo viên' : 'Học viên'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        function openMembersModal() {
            document.getElementById('membersModal').style.display = 'block';
        }
        
        function closeMembersModal() {
            document.getElementById('membersModal').style.display = 'none';
        }
        
        // Đóng modal khi click bên ngoài
        window.onclick = function(event) {
            const modal = document.getElementById('membersModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>