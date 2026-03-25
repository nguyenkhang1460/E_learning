<?php
session_start();
include 'db_config.php';

if (isset($_POST['btn_create'])) {
    $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $class_type = $_POST['class_type'];
    $owner_id = $_SESSION['user_id'];
    $invite_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    // Xử lý mảng yêu cầu thành JSON
    $req_json = null;
    if ($class_type == 'private' && isset($_POST['requirements'])) {
        $req_json = json_encode($_POST['requirements'], JSON_UNESCAPED_UNICODE);
    }

    $sql = "INSERT INTO classes (class_name, description, invite_code, owner_id, type, requirements) 
            VALUES ('$class_name', '$description', '$invite_code', '$owner_id', '$class_type', '$req_json')";

    if (mysqli_query($conn, $sql)) {
        header("Location: user_dashboard.php");
    } else {
        echo "Lỗi: " . mysqli_error($conn);
    }
}
?>