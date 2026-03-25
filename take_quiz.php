<?php
session_start();
include 'db_config.php';
mysqli_set_charset($conn, "utf8mb4");

$id = intval($_GET['id']);

// Kiểm tra xem học viên đã làm bài này chưa
if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $check_submission = mysqli_query($conn, "SELECT id FROM submissions WHERE assignment_id = $id AND user_id = $user_id LIMIT 1");
    if (mysqli_num_rows($check_submission) > 0) {
        $_SESSION['flash_info'] = '⚠️ Bạn đã hoàn thành bài kiểm tra này rồi! Mỗi học viên chỉ được làm bài một lần duy nhất.';
        header("Location: view_assignment_detail.php?id=$id");
        exit();
    }
}

// Lấy thông tin bài tập bao gồm thời gian và deadline
$assignment_query = mysqli_query($conn, "SELECT duration, deadline FROM assignments WHERE id = $id");
$assignment_data = mysqli_fetch_assoc($assignment_query);

// Kiểm tra deadline
$is_deadline_passed = false;
if ($assignment_data['deadline']) {
    $deadline_timestamp = strtotime($assignment_data['deadline']);
    if (time() > $deadline_timestamp) {
        $is_deadline_passed = true;
    }
}

// Lấy danh sách câu hỏi từ bảng quiz_questions đã tạo
$query = mysqli_query($conn, "SELECT * FROM quiz_questions WHERE assignment_id = $id");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đang làm bài trắc nghiệm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>
<body class="take-quiz-page">
    <?php if ($is_deadline_passed): ?>
        <div class="quiz-box">
            <div class="deadline-expired">
                <h2><i class="fas fa-exclamation-triangle"></i> Đã hết thời hạn nộp bài!</h2>
                <p>Bài tập này đã hết hạn nộp vào ngày <?php echo date('d/m/Y H:i', strtotime($assignment_data['deadline'])); ?></p>
                <button type="button" class="btn-quiz-back js-quiz-back">
                    Quay lại
                </button>
            </div>
        </div>
    <?php else: ?>
        <!-- Overlay cảnh báo -->
        <div class="overlay" id="overlay"></div>
        <div class="cheat-warning" id="cheatWarning">
            <h2><i class="fas fa-exclamation-triangle"></i> CẢNH BÁO VI PHẠM!</h2>
            <p id="warningType">Hệ thống phát hiện bạn đã vi phạm quy định!</p>
            <p id="warningMessage">Đây là vi phạm quy định làm bài thi.</p>
            <button type="button" class="btn-warning-close js-close-warning">Tôi hiểu và sẽ không tái phạm</button>
        </div>

        <div class="quiz-box">
            <!-- Hệ thống giám sát -->
            <div class="anti-cheat-box">
                <div>
                    <i class="fas fa-shield-alt"></i> <strong>Hệ thống chống gian lận đang hoạt động</strong>
                </div>
                <div class="violation-counter">
                    <i class="fas fa-eye"></i> Vi phạm: <span id="violationCount">0</span>/3
                </div>
            </div>

            <?php if ($assignment_data['duration']): ?>
                <div class="timer-box" id="timerBox" data-duration="<?php echo (int)$assignment_data['duration']; ?>">
                    <i class="fas fa-clock"></i> Thời gian còn lại: <span id="timer"><?php echo $assignment_data['duration']; ?>:00</span>
                </div>
            <?php endif; ?>

            <?php if ($assignment_data['deadline']): ?>
                <div class="deadline-warning">
                    <i class="fas fa-calendar-times"></i> <strong>Thời hạn nộp bài:</strong> <?php echo date('d/m/Y H:i', strtotime($assignment_data['deadline'])); ?>
                </div>
            <?php endif; ?>

            <form action="submit_quiz.php" method="POST" id="quizForm">
                <input type="hidden" name="assignment_id" value="<?php echo $id; ?>">
                <input type="hidden" name="start_time" value="<?php echo time(); ?>" id="startTime">
                <input type="hidden" name="violation_count" value="0" id="violationInput">
                <input type="hidden" name="tab_switches" value="[]" id="tabSwitchesInput">

                <?php while($row = mysqli_fetch_assoc($query)): 
                    $opts = json_decode($row['options'], true); // Giải mã JSON đáp án
                ?>
                <div class="question">
                    <p><strong><?php echo $row['question_text']; ?></strong></p>
                    <?php foreach($opts as $idx => $text): ?>
                        <label class="option">
                            <input type="radio" name="ans[<?php echo $row['id']; ?>]" value="<?php echo $idx; ?>" required>
                            <?php echo $text; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php endwhile; ?>

                <button type="submit" class="btn-submit">NỘP BÀI LÀM</button>
            </form>
        </div>
    <?php endif; ?>
    <script src="xu_ly.js?v=<?php echo filemtime('xu_ly.js'); ?>"></script>
</body>
</html>