<?php
session_start();
include 'db_config.php';
mysqli_set_charset($conn, "utf8mb4");

// 1. LẤY ID BÀI TẬP VÀ KIỂM TRA
$id_asm = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_asm <= 0) die("❌ ID bài tập không hợp lệ.");

// 2. TRUY VẤN THÔNG TIN BÀI TẬP (Lấy thêm cột type, duration, deadline và class_id để phân loại)
$sql = "SELECT a.title, a.content, a.file_url, a.type, a.duration, a.deadline, a.class_id, c.owner_id 
        FROM assignments a 
        JOIN classes c ON a.class_id = c.id 
        WHERE a.id = $id_asm";
$res = mysqli_query($conn, $sql);
$assignment = mysqli_fetch_assoc($res);

if (!$assignment) die("❌ Không tìm thấy bài tập.");

$title    = $assignment['title'];
$content  = $assignment['content']; 
$file_url = $assignment['file_url']; 
$type     = $assignment['type']; // 'quiz' hoặc 'essay'
$duration = $assignment['duration'];
$deadline = $assignment['deadline'];
$owner_id = $assignment['owner_id'];

// Xác định quyền của người dùng
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
$is_owner = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $owner_id);

// Nếu là chủ phòng hoặc admin và là bài quiz, lấy danh sách câu hỏi
$quiz_questions = [];
if (($is_admin || $is_owner) && $type == 'quiz') {
    $sql_questions = "SELECT id, question_text, options, correct_option FROM quiz_questions WHERE assignment_id = $id_asm ORDER BY id";
    $res_questions = mysqli_query($conn, $sql_questions);
    while ($row = mysqli_fetch_assoc($res_questions)) {
        $quiz_questions[] = $row;
    }
}

// Kiểm tra deadline có hết hạn không
$is_deadline_passed = false;
if ($deadline) {
    $deadline_timestamp = strtotime($deadline);
    if (time() > $deadline_timestamp) {
        $is_deadline_passed = true;
    }
}

// Kiểm tra xem học viên đã nộp bài chưa
$has_submitted = false;
$submission_score = null;
$submission_date = null;
if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $check_sql = "SELECT ai_score, submitted_at FROM submissions WHERE assignment_id = $id_asm AND user_id = $user_id LIMIT 1";
    $check_res = mysqli_query($conn, $check_sql);
    if (mysqli_num_rows($check_res) > 0) {
        $has_submitted = true;
        $submission_data = mysqli_fetch_assoc($check_res);
        $submission_score = $submission_data['ai_score'];
        $submission_date = $submission_data['submitted_at'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết bài tập: <?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body class="assignment-detail-page">
    <div class="container">
        <div class="card">
            <div class="assignment-header">
                <i class="fas fa-bolt fa-2x" style="color: #4299e1;"></i>
                <h2><?php echo htmlspecialchars($title); ?></h2>
            </div>
            
            <div style="background: #f1f5f9; padding: 20px; border-radius: 12px;">
                <p style="font-weight: 600; color: #475569;"><i class="fas fa-info-circle"></i> Yêu cầu từ giảng viên:</p>
                <p style="margin-top: 10px; color: #1e293b; line-height: 1.7;">
                    <?php echo nl2br(htmlspecialchars($content)); ?>
                </p>
                
                <?php if ($duration): ?>
                    <div style="margin-top: 15px; padding: 10px; background: white; border-left: 4px solid #764ba2; border-radius: 5px;">
                        <i class="fas fa-clock" style="color: #764ba2;"></i>
                        <strong>Thời gian làm bài:</strong> <?php echo $duration; ?> phút
                    </div>
                <?php endif; ?>
                
                <?php if ($deadline): ?>
                    <div style="margin-top: 10px; padding: 10px; background: white; border-left: 4px solid <?php echo $is_deadline_passed ? '#dc3545' : '#667eea'; ?>; border-radius: 5px;">
                        <i class="fas fa-calendar-times" style="color: <?php echo $is_deadline_passed ? '#dc3545' : '#667eea'; ?>;"></i>
                        <strong>Thời hạn nộp bài:</strong> <?php echo date('d/m/Y H:i', strtotime($deadline)); ?>
                        <?php if ($is_deadline_passed): ?>
                            <span style="color: #dc3545; font-weight: bold;"> (Đã hết hạn)</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <?php if ($type == 'quiz'): ?>
                <?php if ($is_admin || $is_owner): ?>
                    <!-- PHẦN DÀNH CHO GIẢNG VIÊN: XEM LẠI CÁC CÂU HỎI ĐÃ TẠO -->
                    <div class="teacher-review-zone">
                        <div class="review-header">
                            <i class="fas fa-chalkboard-teacher fa-2x"></i>
                            <h3><i class="fas fa-eye"></i> Danh sách câu hỏi đã tạo</h3>
                            <p><i class="fas fa-info-circle"></i> Chỉ giảng viên mới có thể xem phần này</p>
                        </div>
                        
                        <?php if (count($quiz_questions) > 0): ?>
                            <?php 
                            $question_number = 1;
                            foreach ($quiz_questions as $q): 
                                $options = json_decode($q['options'], true);
                                $correct_index = $q['correct_option'];
                            ?>
                                <div class="question-review-item">
                                    <span class="question-number">
                                        <i class="fas fa-question-circle"></i> Câu hỏi <?php echo $question_number++; ?>
                                    </span>
                                    <div class="question-text">
                                        <?php echo nl2br(htmlspecialchars($q['question_text'])); ?>
                                    </div>
                                    <div style="margin-top: 15px;">
                                        <?php 
                                        $option_labels = ['A', 'B', 'C', 'D', 'E', 'F'];
                                        foreach ($options as $idx => $option_text): 
                                            $is_correct = ($idx == $correct_index);
                                        ?>
                                            <div class="option-item <?php echo $is_correct ? 'correct' : ''; ?>">
                                                <span class="option-label"><?php echo $option_labels[$idx]; ?>.</span>
                                                <span><?php echo htmlspecialchars($option_text); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div style="text-align: center; padding: 20px; background: white; border-radius: 10px; margin-top: 20px;">
                                <p style="color: #667eea; font-weight: 600; margin: 0;">
                                    <i class="fas fa-check-double"></i> Tổng số: <strong><?php echo count($quiz_questions); ?> câu hỏi</strong>
                                </p>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; background: white; border-radius: 10px;">
                                <i class="fas fa-inbox fa-3x" style="color: #cbd5e0; margin-bottom: 15px;"></i>
                                <p style="color: #64748b; margin: 0;">Chưa có câu hỏi nào được tạo cho bài kiểm tra này.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- PHẦN DÀNH CHO HỌC VIÊN: LÀM BÀI -->
                    <div class="quiz-start-zone">
                        <i class="fas fa-brain fa-3x" style="color: #48bb78; margin-bottom: 20px;"></i>
                        <h3>Bài kiểm tra</h3>
                        <p style="color: #64748b; margin-bottom: 30px;">
                            Hệ thống đã chuẩn bị sẵn các câu hỏi từ giáo trình.<br>
                            <strong style="color: #667eea;">💡 KHUYẾN KHÍCH: Nhấn F11 để vào chế độ toàn màn hình (giúp tập trung hơn)</strong><br>
                            <strong style="color: #dc3545;">⚠️ BẮT BUỘC: KHÔNG được chuyển tab/cửa sổ trong khi làm bài</strong><br>
                            Vi phạm quá 3 lần sẽ bị tự động nộp bài.
                        </p>
                        
                        <?php if ($has_submitted): ?>
                        <div style="background: #d4edda; color: #155724; padding: 25px; border-radius: 12px; border: 2px solid #c3e6cb;">
                            <i class="fas fa-check-circle" style="font-size: 2em; margin-bottom: 10px;"></i>
                            <h4 style="margin: 10px 0;"><strong>✅ Bạn đã hoàn thành bài kiểm tra này!</strong></h4>
                            <p style="margin: 10px 0;">
                                <i class="fas fa-calendar-check"></i> <strong>Thời gian nộp:</strong> <?php echo date('d/m/Y H:i', strtotime($submission_date)); ?>
                            </p>
                            <?php if ($submission_score !== null): ?>
                                <p style="margin: 10px 0; font-size: 1.1em;">
                                    <i class="fas fa-star" style="color: #ffc107;"></i> <strong>Điểm số:</strong> 
                                    <span style="font-size: 1.3em; color: #28a745; font-weight: bold;"><?php echo number_format($submission_score, 2); ?>/10</span>
                                </p>
                            <?php endif; ?>
                            <p style="margin-top: 15px; font-style: italic; color: #6c757d;">
                                <i class="fas fa-info-circle"></i> Mỗi học viên chỉ được làm bài một lần duy nhất.
                            </p>
                        </div>
                    <?php elseif ($is_deadline_passed): ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; border: 1px solid #f5c6cb;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Đã hết thời hạn nộp bài!</strong><br>
                            Bạn không thể làm bài tập này nữa.
                        </div>
                    <?php else: ?>
                        <a href="take_quiz.php?id=<?php echo $id_asm; ?>" class="btn-start-quiz">
                            <i class="fas fa-play-circle"></i> BẮT ĐẦU LÀM BÀI
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; // End of is_admin/is_owner check ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>