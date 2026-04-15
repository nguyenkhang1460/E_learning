<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
mysqli_set_charset($conn, "utf8mb4");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignment_id = intval($_POST['assignment_id']);
    $user_id = $_SESSION['user_id'];
    $user_answers = $_POST['ans']; // Mảng [question_id => selected_index]

    // Kiểm tra deadline trước khi cho phép nộp bài và lấy class_id
    $sql_assignment = "SELECT deadline, class_id FROM assignments WHERE id = $assignment_id";
    $res_assignment = mysqli_query($conn, $sql_assignment);
    $assignment_info = mysqli_fetch_assoc($res_assignment);
    $class_id = $assignment_info['class_id'];
    
    if ($assignment_info['deadline']) {
        $deadline_timestamp = strtotime($assignment_info['deadline']);
        if (time() > $deadline_timestamp) {
            $_SESSION['flash_error'] = '❌ Đã hết thời hạn nộp bài! Bài làm không được chấp nhận.';
            header("Location: /E_learning/modules/assignment/view_assignment_detail.php?id=$assignment_id");
            exit();
        }
    }

    // 1. Lấy toàn bộ câu hỏi và đáp án đúng của bài tập này
    $sql_q = "SELECT id, correct_option FROM quiz_questions WHERE assignment_id = $assignment_id";
    $res_q = mysqli_query($conn, $sql_q);

    $total_questions = mysqli_num_rows($res_q);
    $correct_count = 0;
    $details = [];

    while ($q = mysqli_fetch_assoc($res_q)) {
        $q_id = $q['id'];
        $correct_idx = intval($q['correct_option']);
        $selected_idx = isset($user_answers[$q_id]) ? intval($user_answers[$q_id]) : -1;

        $is_correct = ($selected_idx === $correct_idx) ? 1 : 0;
        if ($is_correct) $correct_count++;

        $details[] = [
            'question_id' => $q_id,
            'selected' => $selected_idx,
            'is_correct' => $is_correct
        ];
    }

    // 2. Tính điểm hệ 10
    $final_score = ($total_questions > 0) ? ($correct_count / $total_questions) * 10 : 0;
    
    // 2.5. Lấy thông tin chống gian lận
    $violation_count = isset($_POST['violation_count']) ? intval($_POST['violation_count']) : 0;
    $tab_switches = isset($_POST['tab_switches']) ? mysqli_real_escape_string($conn, $_POST['tab_switches']) : '[]';
    
    // Giảm điểm nếu có vi phạm (mỗi lần vi phạm trừ 1 điểm)
    if ($violation_count > 0) {
        $final_score = max(0, $final_score - $violation_count);
    }

    // 3. Lưu vào bảng submissions (Bảng chính)
    // Thử insert với các cột chống gian lận, nếu lỗi thì insert không có các cột đó
    $sql_sub = "INSERT INTO submissions (assignment_id, user_id, ai_score, violation_count, violation_log) 
            VALUES ($assignment_id, $user_id, $final_score, $violation_count, '$tab_switches')";
    
    $insert_success = mysqli_query($conn, $sql_sub);
    
    // Nếu lỗi (có thể do chưa có cột violation), thử lại không có các cột đó
    if (!$insert_success) {
        $sql_sub = "INSERT INTO submissions (assignment_id, user_id, ai_score) 
                VALUES ($assignment_id, $user_id, $final_score)";
        $insert_success = mysqli_query($conn, $sql_sub);
    }
    
    if ($insert_success) {
        $submission_id = mysqli_insert_id($conn);

        // 4. Lưu chi tiết từng câu vào bảng quiz_submissions
        foreach ($details as $item) {
            $sql_det = "INSERT INTO quiz_submissions (submission_id, question_id, selected_option, is_correct) 
                        VALUES ($submission_id, {$item['question_id']}, {$item['selected']}, {$item['is_correct']})";
            mysqli_query($conn, $sql_det);
        }

        // Tạo thông báo kết quả với thông tin vi phạm
        $result_message = "✅ Đã nộp bài thành công!\\n\\n";
        $result_message .= "📊 Số câu đúng: $correct_count/$total_questions\\n";
        
        if ($violation_count > 0) {
            $original_score = ($total_questions > 0) ? ($correct_count / $total_questions) * 10 : 0;
            $result_message .= "⚠️ Số lần vi phạm: $violation_count\\n";
            $result_message .= "⚠️ Điểm ban đầu: " . round($original_score, 2) . "/10\\n";
            $result_message .= "⚠️ Bị trừ: " . $violation_count . " điểm (do vi phạm)\\n";
        }
        
        $result_message .= "🎯 Điểm cuối cùng: " . round($final_score, 2) . "/10";

        $_SESSION['flash_info'] = $result_message;
        header("Location: /E_learning/modules/class/manage_class.php?id=$class_id");
        exit();
    } else {
        echo "❌ Lỗi lưu kết quả: " . mysqli_error($conn);
    }
}
?>