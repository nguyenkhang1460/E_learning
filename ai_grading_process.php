<?php
session_start();
include 'db_config.php';
require 'vendor/autoload.php';

$API_KEY = "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";
$API_URL = "https://openrouter.ai/api/v1/chat/completions";
$MODEL   = "google/gemini-2.0-flash-001"; 

$submission_id = intval($_GET['sub_id'] ?? 0);
$class_id      = intval($_GET['class_id'] ?? 0);
if ($submission_id === 0) die("❌ ID bài nộp không hợp lệ");

// 1. LẤY DỮ LIỆU TỔNG HỢP
$sql = "SELECT s.student_text, a.answer_key_url, r.rubric_json FROM submissions s 
        JOIN assignments a ON s.assignment_id = a.id 
        LEFT JOIN assignment_rubrics r ON a.id = r.assignment_id WHERE s.id = $submission_id";
$res = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($res);

$raw_student_text = trim($data['student_text']); 
$raw_teacher_url  = $data['answer_key_url'];    
$rubric_json      = $data['rubric_json'] ?? '{}';

// Trích xuất văn bản từ PDF đáp án
$parser = new \Smalot\PdfParser\Parser();
$pdf_ans = $parser->parseContent(file_get_contents($raw_teacher_url));
$raw_teacher_text = trim($pdf_ans->getText());

/* =====================================================
   GIAI ĐOẠN 1: CHUẨN HÓA DỮ LIỆU ĐA TẦNG
===================================================== */
$prompt_1 = "Bạn là chuyên gia xử lý dữ liệu đa ngành. Hãy trích xuất câu trả lời từ:
1. VĂN BẢN SV: '$raw_student_text'
2. VĂN BẢN GV: '$raw_teacher_text'

YÊU CẦU:
- Không tự ý sửa lỗi. Giữ nguyên định dạng gốc của người dùng.
- Trả về JSON: {\"student_json\": {...}, \"teacher_json\": {...}}";

$payload_1 = ["model" => $MODEL, "messages" => [["role" => "user", "content" => $prompt_1]], "temperature" => 0, "response_format" => ["type" => "json_object"]];

$ch = curl_init($API_URL);
curl_setopt_array($ch, [CURLOPT_HTTPHEADER => ["Authorization: Bearer $API_KEY", "Content-Type: application/json"], CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload_1), CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false]);
$res_1 = json_decode(curl_exec($ch), true);
$structured_data = json_decode($res_1['choices'][0]['message']['content'] ?? '{}', true);

$student_json_str = json_encode($structured_data['student_json'], JSON_UNESCAPED_UNICODE);
$teacher_json_str = json_encode($structured_data['teacher_json'], JSON_UNESCAPED_UNICODE);

mysqli_query($conn, "UPDATE submissions SET structured_answer = '".mysqli_real_escape_string($conn, $student_json_str)."' WHERE id = $submission_id");

/* =====================================================
   GIAI ĐOẠN 2: GIÁM KHẢO ĐA NĂNG (THE UNIVERSAL GRADER)
===================================================== */
$prompt_2 = "Bạn là một Hệ thống Giám khảo Trí tuệ Nhân tạo Đa năng. Nhiệm vụ của bạn là chấm điểm bài làm dựa trên sự đối chiếu thông minh.

QUY TRÌNH TƯ DUY:
1. PHÂN TÍCH BẢN CHẤT CÂU TRẢ LỜI:
   - Nếu câu trả lời là 'Dữ liệu Cứng' (Công thức, con số, mã nguồn, phương trình): Hãy so khớp từng ký tự. Sai một dấu phẩy hoặc một con số nhỏ cũng tính là SAI.
   - Nếu câu trả lời là 'Dữ liệu Mềm' (Ý kiến, sự kiện lịch sử, mô tả): Hãy so khớp về mặt ngữ nghĩa và thông tin cốt lõi. Khác từ ngữ nhưng đúng ý thì cho ĐÚNG.

2. ĐỐI CHIẾU: 
   - So sánh BÀI LÀM SV: $student_json_str
   - Với ĐÁP ÁN GV: $teacher_json_str
   - Tuân thủ nghiêm ngặt RUBRIC: $rubric_json

TRẢ VỀ JSON DUY NHẤT:
{
  \"score\": (Tổng điểm),
  \"feedback\": \"Giải thích logic chấm điểm: Tại sao đúng/sai dựa trên bản chất dữ liệu.\"
}";

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["model" => $MODEL, "messages" => [["role" => "user", "content" => $prompt_2]], "temperature" => 0, "response_format" => ["type" => "json_object"]]));
$res_2 = json_decode(curl_exec($ch), true);
curl_close($ch);

$final_result = json_decode($res_2['choices'][0]['message']['content'] ?? '{}', true);
$score = floatval($final_result['score'] ?? 0);
$feedback = mysqli_real_escape_string($conn, $final_result['feedback'] ?? 'Lỗi chấm bài.');

mysqli_query($conn, "UPDATE submissions SET ai_score = $score, ai_feedback = '$feedback' WHERE id = $submission_id");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả chấm điểm AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
            padding: 40px;
            animation: slideUp 0.6s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #f0f0f0;
        }
        .header h1 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 10px;
        }
        .header .icon {
            font-size: 3em;
            color: #764ba2;
            margin-bottom: 15px;
        }
        .score-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .score-box .label {
            font-size: 1.1em;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .score-box .score {
            font-size: 4em;
            font-weight: bold;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .data-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
        }
        .data-section h3 {
            color: #333;
            font-size: 1.3em;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .data-section h3 i {
            color: #667eea;
        }
        .data-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.95em;
            line-height: 1.6;
            color: #555;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
        }
        .feedback-section {
            background: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .feedback-section h3 {
            color: #856404;
            font-size: 1.3em;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .feedback-section h3 i {
            color: #ffc107;
        }
        .feedback-content {
            color: #533f03;
            line-height: 1.8;
            font-size: 1.05em;
        }
        .btn-back {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1em;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            text-align: center;
            width: 100%;
        }
        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }
        .btn-back i {
            margin-right: 8px;
        }
        .divider {
            height: 2px;
            background: linear-gradient(to right, transparent, #667eea, transparent);
            margin: 30px 0;
        }
        /* Scrollbar styling */
        .data-content::-webkit-scrollbar {
            width: 8px;
        }
        .data-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .data-content::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }
        .data-content::-webkit-scrollbar-thumb:hover {
            background: #764ba2;
        }
        @media (max-width: 768px) {
            .container {
                padding: 25px;
            }
            .header h1 {
                font-size: 1.5em;
            }
            .score-box .score {
                font-size: 3em;
            }
            .data-section h3, .feedback-section h3 {
                font-size: 1.1em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">
                <i class="fas fa-robot"></i>
            </div>
            <h1>Hệ Thống Giám Khảo AI Đa Năng</h1>
        </div>

        <div class="score-box">
            <div class="label">
                <i class="fas fa-award"></i> ĐIỂM CUỐI CÙNG
            </div>
            <div class="score"><?php echo number_format($score, 2); ?></div>
        </div>

        <div class="data-section">
            <h3>
                <i class="fas fa-user-graduate"></i>
                Dữ liệu Học viên
            </h3>
            <div class="data-content"><?php echo htmlspecialchars($student_json_str); ?></div>
        </div>

        <div class="data-section">
            <h3>
                <i class="fas fa-chalkboard-teacher"></i>
                Dữ liệu Giáo viên (Đáp án chuẩn)
            </h3>
            <div class="data-content"><?php echo htmlspecialchars($teacher_json_str); ?></div>
        </div>

        <div class="divider"></div>

        <div class="feedback-section">
            <h3>
                <i class="fas fa-comments"></i>
                Phân tích chi tiết từ AI
            </h3>
            <div class="feedback-content"><?php echo nl2br(htmlspecialchars($feedback)); ?></div>
        </div>

        <a href="manage_class.php?id=<?php echo $class_id; ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Quay lại lớp học
        </a>
    </div>
</body>
</html>