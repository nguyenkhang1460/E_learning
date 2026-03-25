<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("❌ Invalid request method");
}

$assignment_id = intval($_POST['assignment_id']);
$user_id = $_SESSION['user_id'];

// Lấy thông tin bài tập
$sql = "SELECT * FROM assignments WHERE id = $assignment_id";
$result = mysqli_query($conn, $sql);
$assignment = mysqli_fetch_assoc($result);

if (!$assignment) {
    die("❌ Không tìm thấy bài tập!");
}

// Parse quiz data
$quiz_data = json_decode($assignment['content'], true);
$answer_key = json_decode($assignment['answer_key_url'], true);

if (!$quiz_data || !isset($quiz_data['questions'])) {
    die("❌ Đây không phải bài trắc nghiệm!");
}

// Kiểm tra đã nộp chưa
$sql_check = "SELECT id FROM quiz_submissions WHERE assignment_id = $assignment_id AND user_id = $user_id";
$result_check = mysqli_query($conn, $sql_check);
if (mysqli_num_rows($result_check) > 0) {
    die("❌ Bạn đã nộp bài rồi!");
}

// Thu thập câu trả lời
$user_answers = [];
$correct_count = 0;
$total_questions = count($quiz_data['questions']);

foreach ($quiz_data['questions'] as $q) {
    $question_id = $q['id'];
    $answer_key_field = "answer_$question_id";
    
    // Lấy đáp án user chọn
    $user_answer = isset($_POST[$answer_key_field]) ? intval($_POST[$answer_key_field]) : -1;
    $user_answers[$question_id] = $user_answer;
    
    // Lấy đáp án đúng
    $correct_answer = -1;
    foreach ($answer_key['answers'] as $ans) {
        if ($ans['question_id'] == $question_id) {
            $correct_answer = $ans['correct_index'];
            break;
        }
    }
    
    // Kiểm tra đúng/sai
    if ($user_answer == $correct_answer) {
        $correct_count++;
    }
}

// Tính điểm
$total_points = $quiz_data['total_points'];
$score = round(($correct_count / $total_questions) * $total_points, 2);

// Lưu kết quả
$answers_json = json_encode($user_answers, JSON_UNESCAPED_UNICODE);
$answers_json_escaped = mysqli_real_escape_string($conn, $answers_json);

$sql_insert = "INSERT INTO quiz_submissions (assignment_id, user_id, answers, score) 
               VALUES ($assignment_id, $user_id, '$answers_json_escaped', $score)";

if (mysqli_query($conn, $sql_insert)) {
    // Cũng lưu vào bảng submissions cũ để tương thích
    $title = mysqli_real_escape_string($conn, $assignment['title']);
    $sql_legacy = "INSERT INTO submissions (assignment_id, user_id, student_file, submitted_date, score) 
                   VALUES ($assignment_id, $user_id, 'quiz_submission', NOW(), $score)";
    mysqli_query($conn, $sql_legacy);
    
    // Hiển thị trang kết quả đẹp
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Kết quả bài làm</title>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .result-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; max-width: 500px; animation: slideUp 0.5s ease-out; }
            @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
            .icon-success { font-size: 5em; color: #4CAF50; margin-bottom: 20px; animation: bounceIn 0.8s ease-out; }
            @keyframes bounceIn { 0%, 20%, 50%, 80%, 100% { transform: scale(1); } 40% { transform: scale(1.1); } 60% { transform: scale(1.05); } }
            h2 { color: #2c3e50; margin: 10px 0; }
            .score-display { font-size: 3.5em; font-weight: bold; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 20px 0; }
            .details { background: #f8f9fa; padding: 20px; border-radius: 12px; margin: 20px 0; }
            .details p { margin: 10px 0; color: #555; font-size: 1.1em; }
            .redirect-text { color: #888; font-size: 0.9em; margin-top: 20px; }
            .btn-dashboard { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; border-radius: 25px; text-decoration: none; font-weight: bold; margin-top: 15px; transition: all 0.3s; }
            .btn-dashboard:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4); }
        </style>
    </head>
    <body>
        <div class='result-card'>
            <i class='fas fa-check-circle icon-success'></i>
            <h2>🎉 Hoàn thành bài kiểm tra!</h2>
            <p style='color: #7f8c8d; margin: 10px 0;'>" . htmlspecialchars($assignment['title']) . "</p>
            
            <div class='score-display'>" . number_format($score, 2) . "/10</div>
            
            <div class='details'>
                <p><i class='fas fa-check' style='color: #4CAF50;'></i> <strong>Trả lời đúng:</strong> $correct_count/$total_questions câu</p>
                <p><i class='fas fa-percentage' style='color: #3498db;'></i> <strong>Tỷ lệ chính xác:</strong> " . round(($correct_count/$total_questions)*100, 1) . "%</p>
            </div>
            
            <a href='user_dashboard.php' class='btn-dashboard'>
                <i class='fas fa-home'></i> Về trang chủ
            </a>
            
            <p class='redirect-text'>
                <i class='fas fa-info-circle'></i> Tự động chuyển trang sau <span id='countdown'>5</span> giây...
            </p>
        </div>
        
        <script>
            let seconds = 5;
            const countdownEl = document.getElementById('countdown');
            const interval = setInterval(() => {
                seconds--;
                countdownEl.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(interval);
                    window.location.href = 'user_dashboard.php';
                }
            }, 1000);
        </script>
    </body>
    </html>";
    exit();
} else {
    die("❌ Lỗi lưu kết quả: " . mysqli_error($conn));
}
?>
