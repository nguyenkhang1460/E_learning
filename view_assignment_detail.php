<?php
session_start();
include 'db_config.php'; // Đảm bảo gọi file này để không lỗi $conn

// 1. LẤY ID BÀI TẬP VÀ KIỂM TRA
$id_asm = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_asm <= 0) die("❌ ID bài tập không hợp lệ.");

// 2. TRUY VẤN THÔNG TIN BÀI TẬP
$sql = "SELECT title, content, file_url FROM assignments WHERE id = $id_asm";
$res = mysqli_query($conn, $sql);
$assignment = mysqli_fetch_assoc($res);

if (!$assignment) die("❌ Không tìm thấy bài tập.");

$title    = $assignment['title'];
$content  = $assignment['content']; // Đã đổi từ instructions sang content
$file_url = $assignment['file_url']; // Link file đề bài
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Làm bài: <?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 800px; margin: auto; }
        .card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .btn { padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-flex; align-items: center; gap: 10px; border: none; cursor: pointer; }
        .btn-view { background: #007bff; color: white; }
        .btn-submit { background: #28a745; color: white; width: 100%; justify-content: center; margin-top: 15px; }
        .drop-zone { border: 2px dashed #cbd5e0; padding: 40px; text-align: center; border-radius: 12px; background: #fafafa; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2 style="color: #2d3748; margin-bottom: 20px;"><i class="fas fa-book-open"></i> <?php echo htmlspecialchars($title); ?></h2>
            
            <div style="background: #ebf8ff; padding: 15px; border-left: 4px solid #4299e1; border-radius: 4px;">
                <strong><i class="fas fa-info-circle"></i> Hướng dẫn từ giáo viên:</strong>
                <p style="margin-top: 10px; color: #4a5568; line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($content)); ?>
                </p>
            </div>

            <div style="margin-top: 25px; text-align: center;">
                <p style="margin-bottom: 15px; font-weight: 600;">Xem chi tiết đề bài tại đây:</p>
                <a href="<?php echo $file_url; ?>" target="_blank" class="btn btn-view">
                    <i class="fas fa-file-pdf"></i> Mở file Đề bài
                </a>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-upload"></i> Nộp bài làm của bạn</h3>
            <p style="color: #718096; font-size: 0.9em; margin-top: 5px;">Hệ thống chấp nhận file PDF hoặc Hình ảnh.</p>

            <form action="submit_assignment_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="assignment_id" value="<?php echo $id_asm; ?>">
                
                <div class="drop-zone">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 3em; color: #cbd5e0; margin-bottom: 10px;"></i>
                    <br>
                    <input type="file" name="student_file" accept=".pdf, image/*" required>
                </div>

                <button type="submit" name="btn_submit_task" class="btn btn-submit">
                    <i class="fas fa-paper-plane"></i> Xác nhận nộp bài cho AI chấm điểm
                </button>
            </form>
        </div>
    </div>
</body>
</html>