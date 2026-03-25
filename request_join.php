<?php
session_start();
include 'db_config.php';

$class_id = intval($_GET['class_id']);
$res = mysqli_query($conn, "SELECT class_name, requirements FROM classes WHERE id = $class_id");
$class = mysqli_fetch_assoc($res);

// Kiểm tra xem lớp học có tồn tại không
if (!$class) {
    echo "<script>
        alert('❌ Không tìm thấy lớp học!\\n\\nVui lòng kiểm tra lại mã lớp học.');
        window.location.href = 'join_class.html';
    </script>";
    exit();
}

$req_list = json_decode($class['requirements'], true) ?? [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gửi yêu cầu tham gia</title>
    <link rel="stylesheet" href="create_class_style.css">
</head>
<body>
    <div class="container">
        <h2>Đăng ký vào lớp: <?php echo $class['class_name']; ?></h2>
        <form action="request_process.php" method="POST">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            
            <?php foreach($req_list as $label): ?>
                <div class="form-group">
                    <label><?php echo $label; ?> (Bắt buộc)</label>
                    <input type="text" name="answers[<?php echo $label; ?>]" required>
                </div>
            <?php endforeach; ?>
            
            <button type="submit">Gửi yêu cầu xét duyệt</button>
        </form>
    </div>
</body>
</html>