<?php
/**
 * Quiz Result View — Hiển thị kết quả bài làm trắc nghiệm
 * Nhận dữ liệu qua $_SESSION['quiz_result']
 */
session_start();
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../core/autoload.php';

Auth::requireLogin();

$result = $_SESSION['quiz_result'] ?? null;
if (!$result) {
    Redirect::to(Routes::DASHBOARD_USER);
}
// Xóa session result sau khi đọc
unset($_SESSION['quiz_result']);

$score          = $result['score'];
$correctCount   = $result['correctCount'];
$totalQuestions = $result['totalQuestions'];
$title          = htmlspecialchars($result['title']);
$percentage     = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 1) : 0;
$violationCount = $result['violationCount'] ?? 0;
$originalScore  = $result['originalScore'] ?? $score;
$classId        = $result['classId'] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả bài làm - <?= $title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .result-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
            width: 90%;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .icon-success { font-size: 5em; color: #4CAF50; margin-bottom: 20px; }
        h2 { color: #2c3e50; margin: 10px 0; }
        .quiz-title { color: #7f8c8d; margin: 10px 0; font-size: 1em; }
        .score-display {
            font-size: 3.5em;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 20px 0;
        }
        .details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .details p { margin: 10px 0; color: #555; font-size: 1.1em; }
        .violation-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
        }
        .violation-info p { margin: 5px 0; color: #856404; font-size: 0.95em; }
        .redirect-text { color: #888; font-size: 0.9em; margin-top: 20px; }
        .btn-dashboard {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 15px;
            transition: all 0.3s;
        }
        .btn-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="result-card">
        <i class="fas fa-check-circle icon-success"></i>
        <h2>🎉 Hoàn thành bài kiểm tra!</h2>
        <p class="quiz-title"><?= $title ?></p>

        <div class="score-display"><?= number_format($score, 2) ?>/10</div>

        <div class="details">
            <p><i class="fas fa-check" style="color:#4CAF50"></i>
               <strong>Trả lời đúng:</strong> <?= $correctCount ?>/<?= $totalQuestions ?> câu</p>
            <p><i class="fas fa-percentage" style="color:#3498db"></i>
               <strong>Tỷ lệ chính xác:</strong> <?= $percentage ?>%</p>
        </div>

        <?php if ($violationCount > 0): ?>
        <div class="violation-info">
            <p><i class="fas fa-exclamation-triangle"></i>
               <strong>Số lần vi phạm:</strong> <?= $violationCount ?></p>
            <p><i class="fas fa-calculator"></i>
               <strong>Điểm trước khi trừ:</strong> <?= number_format($originalScore, 2) ?>/10</p>
            <p><i class="fas fa-minus-circle"></i>
               <strong>Điểm bị trừ:</strong> -<?= $violationCount ?> (do vi phạm quy định)</p>
        </div>
        <?php endif; ?>

        <a href="<?= $classId > 0 ? Routes::manageClass($classId) : Routes::DASHBOARD_USER ?>" class="btn-dashboard">
            <i class="fas fa-arrow-left"></i> Quay lại lớp học
        </a>

        <p class="redirect-text">
            <i class="fas fa-info-circle"></i>
            Tự động chuyển trang sau <span id="countdown">5</span> giây...
        </p>
    </div>

    <script>
        let seconds = 5;
        const countdown = document.getElementById('countdown');
        const redirectUrl = '<?= $classId > 0 ? Routes::manageClass($classId) : Routes::DASHBOARD_USER ?>';
        const interval = setInterval(() => {
            seconds--;
            countdown.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = redirectUrl;
            }
        }, 1000);
    </script>
</body>
</html>
