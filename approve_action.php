<?php
session_start();
include 'db_config.php';

$request_id = intval($_GET['id']);
$status = $_GET['status']; // 'approved' hoặc 'rejected'
$redirect_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if ($request_id > 0 && ($status == 'approved' || $status == 'rejected')) {
    
    // 1. Lấy thông tin class_id và user_id từ yêu cầu này trước khi cập nhật
    $info_sql = "SELECT class_id, user_id FROM class_requests WHERE id = $request_id";
    $info_res = mysqli_query($conn, $info_sql);
    $request_data = mysqli_fetch_assoc($info_res);

    if ($request_data) {
        $class_id = $request_data['class_id'];
        $user_id = $request_data['user_id'];

        // 2. Cập nhật trạng thái trong bảng class_requests
        $update_sql = "UPDATE class_requests SET status = '$status' WHERE id = $request_id";
        
        if (mysqli_query($conn, $update_sql)) {
            
            // 3. Nếu Duyệt thành công, chính thức thêm vào bảng class_members
            if ($status == 'approved') {
                $join_sql = "INSERT INTO class_members (class_id, user_id) VALUES ($class_id, $user_id)";
                mysqli_query($conn, $join_sql);
            }

            // Redirect về trang phù hợp
            $redirect_url = $redirect_class_id > 0 ? "manage_requests.php?id=$redirect_class_id" : "manage_requests.php";
            $message = $status == 'approved' ? 'Đã phê duyệt yêu cầu!' : 'Đã từ chối yêu cầu!';
            echo "<script>alert('$message'); window.location='$redirect_url';</script>";
        }
    }
} else {
    echo "Dữ liệu không hợp lệ!";
}
?>