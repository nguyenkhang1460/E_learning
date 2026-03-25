<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$invite_code = mysqli_real_escape_string($conn, $_POST['invite_code']);
$res = mysqli_query($conn, "SELECT id, type FROM classes WHERE invite_code = '$invite_code'");
$class = mysqli_fetch_assoc($res);

// Kiểm tra xem lớp học có tồn tại không
if (!$class) {
    echo "<script>
        alert('❌ Không tìm thấy lớp học!\\n\\nMã lớp học không đúng. Vui lòng kiểm tra lại.');
        window.location.href = 'join_class.html';
    </script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Kiểm tra xem đã là thành viên chưa
$sql_check = "SELECT id FROM class_members WHERE class_id = {$class['id']} AND user_id = $user_id";
$result_check = mysqli_query($conn, $sql_check);
if (mysqli_num_rows($result_check) > 0) {
    echo "<script>
        alert('ℹ️ Bạn đã là thành viên của lớp học này rồi!');
        window.location.href = 'manage_class.php?id={$class['id']}';
    </script>";
    exit();
}

if ($class['type'] == 'public') {
    // Nếu Public: Thêm thẳng vào bảng class_members
    mysqli_query($conn, "INSERT INTO class_members (class_id, user_id) VALUES (".$class['id'].", $user_id)");
    echo "<script>
        alert('✅ Tham gia lớp học thành công!');
        window.location.href = 'manage_class.php?id=".$class['id']."';
    </script>";
} else {
    // Nếu Private: Chuyển sang trang điền thông tin yêu cầu
    header("Location: request_join.php?class_id=".$class['id']);
}
?>