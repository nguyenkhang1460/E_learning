<?php 
session_start();
$class_id = isset($_GET['id']) ? $_GET['id'] : 0; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng tải bài giảng</title>
    <link rel="stylesheet" href="/E_learning/assets/css/style.css">
    <link rel="stylesheet" href="/E_learning/assets/css/user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="upload-lecture-page">
    <div class="upload-container">
        <a href="/E_learning/dashboard/user_dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Quay lại Dashboard
        </a>

        <div class="upload-card">
            <div class="upload-header">
                <i class="fas fa-cloud-upload-alt"></i>
                <h2>Đăng Tải Bài Giảng</h2>
                <p>Upload tài liệu học tập của bạn lên hệ thống</p>
            </div>
            
            <form action="/E_learning/modules/lecture/upload_lecture_process.php" method="POST" enctype="multipart/form-data" class="upload-form">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Tiêu đề bài giảng</label>
                    <input type="text" name="title" placeholder="Nhập tiêu đề cho bài giảng..." required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-file-upload"></i> Chọn tài liệu</label>
                    <div class="file-upload-wrapper">
                        <div class="file-upload-area" id="fileUploadArea">
                            <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                            <div class="file-upload-text">
                                <h3>Kéo & thả file vào đây</h3>
                                <p>hoặc click để chọn file</p>
                                <div class="file-types">
                                    <span class="file-type-badge"><i class="fas fa-file-pdf"></i> PDF</span>
                                    <span class="file-type-badge"><i class="fas fa-image"></i> Images</span>
                                </div>
                            </div>
                        </div>
                        <input type="file" name="lecture_file" id="lectureFile" accept="application/pdf, image/*" required>
                        <div class="file-info" id="fileInfo">
                            <i class="fas fa-check-circle"></i>
                            <div class="file-info-text">
                                <strong id="fileName">File name</strong>
                                <span id="fileSize">File size</span>
                            </div>
                        </div>
                    </div>
                    <div class="info-note">
                        <i class="fas fa-info-circle"></i>
                        <p><strong>Lưu ý:</strong> Chỉ chấp nhận file PDF hoặc hình ảnh (JPG, PNG, GIF). Các định dạng khác sẽ bị từ chối.</p>
                    </div>
                </div>

                <button type="submit" name="btn_upload" class="submit-btn">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Tải lên
                </button>
            </form>
        </div>
    </div>

    <script src="/E_learning/assets/js/xu_ly.js"></script>
</body>
</html>