<?php 
session_start();
include 'db_config.php';
mysqli_set_charset($conn, "utf8mb4"); // Sửa lỗi hiển thị tiếng Việt

$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; 
// Lấy danh sách giáo trình để chọn nguồn cho AI đọc
$lectures = mysqli_query($conn, "SELECT id, title FROM lectures WHERE class_id = $class_id");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Quiz Generator - Tạo đề trắc nghiệm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body class="create-assignment-page">
    <div class="container">
        <h2><i class="fas fa-robot"></i> AI Soạn Đề Trắc Nghiệm</h2>
        <form id="quizForm" action="save_quiz.php" method="POST" data-class-id="<?= $class_id ?>">
            <input type="hidden" name="class_id" value="<?= $class_id ?>">
            
            <div class="form-group">
                <label><i class="fas fa-heading"></i> Tiêu đề bài tập</label>
                <input type="text" name="title" id="quizTitle" placeholder="VD: Kiểm tra Chương 1" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-file-alt"></i> Nguồn tài liệu (AI sẽ đọc file này)</label>
                <div class="source-options">
                    <label class="radio-label">
                        <input type="radio" name="source_type" value="existing" checked>
                        <span>📚 Chọn file có sẵn trong lớp</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="source_type" value="upload">
                        <span>📤 Upload file mới</span>
                    </label>
                </div>
                
                <div id="existingFileSection">
                    <select name="lecture_id" id="lecture_id">
                        <option value="">-- Chọn giáo trình trong lớp --</option>
                        <?php while($l = mysqli_fetch_assoc($lectures)): ?>
                            <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['title']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div id="uploadFileSection" class="ca-hidden">
                    <input type="file" id="uploadFile" accept=".pdf,.jpg,.jpeg,.png">
                    <small>
                        <i class="fas fa-info-circle"></i> Hỗ trợ: PDF, JPG, PNG (tối đa 10MB)
                    </small>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-list-ol"></i> Số lượng câu hỏi</label>
                <input type="number" id="num_q" value="5" min="1" max="20" placeholder="Từ 1 đến 20 câu">
            </div>

            <div class="form-group">
                <label><i class="fas fa-signal"></i> Mức độ bài tập</label>
                <select name="difficulty" required>
                    <option value="">-- Chọn mức độ --</option>
                    <option value="easy">🟢 Dễ</option>
                    <option value="medium" selected>🟡 Vừa</option>
                    <option value="hard">🔴 Khó</option>
                </select>
                <small>💡 Giúp phân loại bài tập theo độ khó để học viên dễ theo dõi</small>
            </div>

            <div class="form-group">
                <label><i class="fas fa-clock"></i> Thời gian làm bài (phút)</label>
                <input type="number" name="duration" placeholder="VD: 30" min="1">
                <small>⏱️ Để trống nếu không giới hạn thời gian</small>
            </div>

            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Thời hạn nộp bài</label>
                <input type="datetime-local" name="deadline">
                <small>📅 Để trống nếu không có thời hạn</small>
            </div>

            <div class="form-group">
                <label class="show-score-toggle">
                    <input type="checkbox" name="show_score" value="1" checked class="show-score-checkbox">
                    <div>
                        <div class="show-score-title">
                            <i class="fas fa-eye"></i> Công bố điểm ngay sau khi học viên làm xong
                        </div>
                        <small class="show-score-desc">
                            ✅ Tích = Học viên thấy điểm ngay | ❌ Không tích = Chủ phòng sẽ công bố sau
                        </small>
                    </div>
                </label>
            </div>

            <button type="button" class="btn-ai js-generate-quiz">
                <i class="fas fa-magic"></i> Yêu cầu AI soạn đề
            </button>

            <div id="loading"><i class="fas fa-spinner fa-spin"></i> AI đang đọc tài liệu vui lòng chờ...</div>

            <div id="reviewZone">
                <h3><i class="fas fa-edit"></i> Kiểm tra & Chỉnh sửa đề bài</h3>
                <div id="questionsContainer"></div>
                <button type="submit" class="btn-save"><i class="fas fa-check-circle"></i> XÁC NHẬN & ĐĂNG BÀI</button>
            </div>
        </form>
    </div>

    <script src="xu_ly.js?v=<?php echo filemtime('xu_ly.js'); ?>"></script>
</body>
</html>