<?php
include 'db_config.php';
mysqli_set_charset($conn, "utf8mb4");

if(isset($_POST['class_id'])) {
    $class_id = intval($_POST['class_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    
    // Lấy mức độ bài tập (mặc định là 'medium' nếu không chọn)
    $difficulty = isset($_POST['difficulty']) && in_array($_POST['difficulty'], ['easy', 'medium', 'hard']) 
                  ? mysqli_real_escape_string($conn, $_POST['difficulty']) 
                  : 'medium';
    
    // Lấy thông tin thời gian và deadline
    $duration = isset($_POST['duration']) && $_POST['duration'] != '' ? intval($_POST['duration']) : 'NULL';
    $deadline = isset($_POST['deadline']) && $_POST['deadline'] != '' ? 
                "'" . mysqli_real_escape_string($conn, $_POST['deadline']) . "'" : 'NULL';
    
    // Lấy tùy chọn hiển thị điểm (mặc định = 1 nếu checkbox được tích)
    $show_score = isset($_POST['show_score']) ? 1 : 0;

    // Kiểm tra xem cột show_score và difficulty có tồn tại không
    $check_column = mysqli_query($conn, "SHOW COLUMNS FROM assignments LIKE 'show_score'");
    $has_show_score_column = (mysqli_num_rows($check_column) > 0);
    
    $check_difficulty = mysqli_query($conn, "SHOW COLUMNS FROM assignments LIKE 'difficulty'");
    $has_difficulty_column = (mysqli_num_rows($check_difficulty) > 0);

    // 1. Tạo bản ghi bài tập mới
    if ($has_show_score_column && $has_difficulty_column) {
        // Có cả show_score và difficulty
        $sql_insert = "INSERT INTO assignments (class_id, title, type, difficulty, duration, deadline, show_score) 
                       VALUES ($class_id, '$title', 'quiz', '$difficulty', $duration, $deadline, $show_score)";
    } elseif ($has_show_score_column) {
        // Chỉ có show_score
        $sql_insert = "INSERT INTO assignments (class_id, title, type, duration, deadline, show_score) 
                       VALUES ($class_id, '$title', 'quiz', $duration, $deadline, $show_score)";
    } elseif ($has_difficulty_column) {
        // Chỉ có difficulty
        $sql_insert = "INSERT INTO assignments (class_id, title, type, difficulty, duration, deadline) 
                       VALUES ($class_id, '$title', 'quiz', '$difficulty', $duration, $deadline)";
    } else {
        // Không có cột nào - insert cơ bản
        $sql_insert = "INSERT INTO assignments (class_id, title, type, duration, deadline) 
                       VALUES ($class_id, '$title', 'quiz', $duration, $deadline)";
    }
    
    mysqli_query($conn, $sql_insert);
    $assignment_id = mysqli_insert_id($conn);

    // 2. Lưu từng câu hỏi đã duyệt
    $questions = $_POST['questions'];
    $options = $_POST['options'];
    $corrects = $_POST['correct_index'];

    foreach($questions as $i => $q_text) {
        $q_clean = mysqli_real_escape_string($conn, $q_text);
        $opt_clean = mysqli_real_escape_string($conn, json_encode($options[$i], JSON_UNESCAPED_UNICODE));
        $ans = intval($corrects[$i]);

        mysqli_query($conn, "INSERT INTO quiz_questions (assignment_id, question_text, options, correct_option) 
                             VALUES ($assignment_id, '$q_clean', '$opt_clean', $ans)");
    }

    echo "<script>alert('✅ Đã đăng bài trắc nghiệm thành công!'); window.location='manage_class.php?id=$class_id';</script>";
}