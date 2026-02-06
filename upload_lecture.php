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
    <link rel="stylesheet" href="user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .upload-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .upload-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(255,255,255,0.95) 100%);
            padding: 50px 45px;
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            animation: fadeInUp 0.6s ease;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .upload-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .upload-header i {
            font-size: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            display: block;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .upload-header h2 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .upload-header p {
            color: #666;
            font-size: 15px;
        }

        .upload-form {
            margin-top: 35px;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: #667eea;
            font-size: 18px;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .file-upload-wrapper {
            position: relative;
            margin-top: 10px;
        }

        .file-upload-area {
            border: 3px dashed #d0d0d0;
            border-radius: 20px;
            padding: 50px 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            position: relative;
            overflow: hidden;
        }

        .file-upload-area:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f4ff 0%, #faf5ff 100%);
            transform: translateY(-3px);
        }

        .file-upload-area.dragover {
            border-color: #667eea;
            background: linear-gradient(135deg, #e8edff 0%, #f5efff 100%);
            transform: scale(1.02);
        }

        .file-upload-icon {
            font-size: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }

        .file-upload-text h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 8px;
        }

        .file-upload-text p {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .file-upload-text .file-types {
            display: inline-flex;
            gap: 10px;
            margin-top: 15px;
        }

        .file-type-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        input[type="file"] {
            display: none;
        }

        .file-info {
            margin-top: 20px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            border-radius: 15px;
            border-left: 4px solid #4caf50;
            display: none;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .file-info.show {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .file-info i {
            font-size: 24px;
            color: #4caf50;
        }

        .file-info-text {
            flex: 1;
        }

        .file-info-text strong {
            display: block;
            color: #2e7d32;
            margin-bottom: 3px;
        }

        .file-info-text span {
            color: #558b2f;
            font-size: 14px;
        }

        .info-note {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            padding: 15px 20px;
            border-radius: 15px;
            border-left: 4px solid #ff9800;
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-note i {
            color: #f57c00;
            font-size: 20px;
        }

        .info-note p {
            margin: 0;
            color: #e65100;
            font-size: 14px;
            line-height: 1.6;
        }

        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            margin-top: 35px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn i {
            font-size: 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            gap: 12px;
            color: #764ba2;
        }

        @media (max-width: 768px) {
            .upload-card {
                padding: 35px 25px;
            }

            .upload-header h2 {
                font-size: 26px;
            }

            .file-upload-area {
                padding: 35px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <a href="user_dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Quay lại Dashboard
        </a>

        <div class="upload-card">
            <div class="upload-header">
                <i class="fas fa-cloud-upload-alt"></i>
                <h2>Đăng Tải Bài Giảng</h2>
                <p>Upload tài liệu học tập của bạn lên hệ thống</p>
            </div>
            
            <form action="upload_lecture_process.php" method="POST" enctype="multipart/form-data" class="upload-form">
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
                    Tải lên Cloud
                </button>
            </form>
        </div>
    </div>

    <script>
        const fileUploadArea = document.getElementById('fileUploadArea');
        const lectureFile = document.getElementById('lectureFile');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');

        // Click to upload
        fileUploadArea.addEventListener('click', () => {
            lectureFile.click();
        });

        // File selection
        lectureFile.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        // Drag and drop
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            lectureFile.files = files;
            handleFiles(files);
        });

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.classList.add('show');
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    </script>
</body>
</html>