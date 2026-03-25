<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($class_id == 0) {
    die("ID lớp học không hợp lệ!");
}

// Lấy thông tin lớp học
$sql_class = "SELECT * FROM classes WHERE id = '$class_id'";
$result_class = mysqli_query($conn, $sql_class);
$class_data = mysqli_fetch_assoc($result_class);

if (!$class_data) {
    die("Lớp học không tồn tại!");
}

// Kiểm tra: không cho phép chủ phòng rời lớp của chính mình
if ($class_data['owner_id'] == $current_user_id) {
    echo "<script>
        alert('❌ Bạn là chủ phòng! Không thể rời khỏi lớp học của chính mình.\\n\\nNếu muốn xóa lớp học, vui lòng sử dụng nút \"Xóa lớp học\".');
        window.location.href = 'manage_class.php?id=$class_id';
    </script>";
    exit();
}

// Kiểm tra: Admin không được rời lớp (vì admin có quyền truy cập tất cả)
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
if ($is_admin) {
    echo "<script>
        alert('❌ Admin có quyền truy cập tất cả lớp học và không cần rời lớp.');
        window.location.href = 'manage_class.php?id=$class_id';
    </script>";
    exit();
}

// Kiểm tra xem người dùng có phải là thành viên của lớp không
$sql_check = "SELECT id FROM class_members WHERE user_id = '$current_user_id' AND class_id = '$class_id'";
$result_check = mysqli_query($conn, $sql_check);

if (mysqli_num_rows($result_check) == 0) {
    echo "<script>
        alert('❌ Bạn không phải là thành viên của lớp học này!');
        window.location.href = 'user_dashboard.php';
    </script>";
    exit();
}

// Xóa người dùng khỏi class_members
$sql_delete = "DELETE FROM class_members WHERE user_id = '$current_user_id' AND class_id = '$class_id'";

if (mysqli_query($conn, $sql_delete)) {
    // Ghi log hoạt động (optional - nếu có bảng logs)
    $class_name = $class_data['class_name'];
    $user_name = $_SESSION['full_name'] ?? 'User';
    
    // Log thành công
    error_log("User ID $current_user_id ($user_name) left class ID $class_id ($class_name)");
    
    echo "<script>
        alert('✅ Bạn đã rời khỏi lớp học \"" . addslashes($class_name) . "\" thành công!\\n\\nBạn có thể tham gia lại bằng cách nhập mã mời hoặc chờ chủ phòng phê duyệt.');
        window.location.href = 'user_dashboard.php';
    </script>";
} else {
    echo "<script>
        alert('❌ Đã xảy ra lỗi khi rời lớp học. Vui lòng thử lại!\\n\\nLỗi: " . addslashes(mysqli_error($conn)) . "');
        window.location.href = 'manage_class.php?id=$class_id';
    </script>";
}

mysqli_close($conn);
?>
