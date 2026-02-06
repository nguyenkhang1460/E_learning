<?php 
session_start();
// Lấy ID lớp học từ URL để biết bài tập này thuộc về lớp nào
$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thiết lập bài tập & Rubric AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 40px 20px;
        }
        .container { 
            max-width: 900px; margin: auto; background: white; 
            padding: 40px 50px; border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeInUp 0.5s ease-out;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header { text-align: center; margin-bottom: 40px; }
        .header h2 { color: #2d3748; font-size: 2em; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 15px; }
        .header .robot-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .form-section { 
            border: 2px solid #e2e8f0; border-radius: 16px; 
            padding: 30px; margin-bottom: 30px; 
            transition: all 0.3s ease; background: #fafafa;
        }
        .form-section:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .form-section h3 { margin-bottom: 25px; color: #2d3748; display: flex; align-items: center; gap: 12px; }
        .section-icon { width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 12px; color: white; }

        /* Zones Colors */
        .assignment-zone { border-left: 5px solid #4299e1; }
        .assignment-zone .section-icon { background: #4299e1; }
        .answer-zone { border-left: 5px solid #48bb78; }
        .answer-zone .section-icon { background: #48bb78; }
        .rubric-zone { border-left: 5px solid #ed64a6; background: #fff5f7; }
        .rubric-zone .section-icon { background: #ed64a6; }

        label { display: block; font-weight: 600; margin-bottom: 8px; color: #2d3748; }
        input[type="text"], input[type="file"], textarea, input[type="number"] { 
            width: 100%; padding: 12px 15px; margin-bottom: 20px; 
            border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit;
        }

        /* Rubric Dynamic Rows */
        .rubric-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .btn-add-row { 
            background: #ed64a6; color: white; border: none; padding: 10px 20px; 
            border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s;
        }
        .btn-add-row:hover { opacity: 0.9; transform: scale(1.02); }

        .btn-submit { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 18px; border: none; border-radius: 12px; 
            cursor: pointer; font-weight: 700; width: 100%; font-size: 1.1em;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); transition: 0.3s;
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4); }
        
        .note { font-size: 0.85em; color: #718096; background: #edf2f7; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-robot robot-icon"></i> Cấu hình bài tập & Rubric AI</h2>
            <p>Tạo đề bài và thiết lập tiêu chí chấm điểm tự động trong một bước</p>
        </div>
        
        <form action="create_assignment_process.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">

            <div class="form-section assignment-zone">
                <h3><i class="fas fa-file-alt section-icon"></i> Thông tin bài tập</h3>
                
                <label>Tiêu đề bài tập</label>
                <input type="text" name="title" placeholder="Ví dụ: Kiểm tra giữa kỳ môn Java" required>

                <label>File đề bài (PDF hoặc Ảnh)</label>
                <input type="file" name="assignment_file" accept=".pdf, image/*" required>

                <label>Mô tả/Ghi chú</label>
                <textarea name="content" placeholder="Nhập hướng dẫn làm bài cho sinh viên..."></textarea>
            </div>

            <div class="form-section answer-zone">
                <h3><i class="fas fa-key section-icon"></i> Đáp án chuẩn (AI dùng để đối chiếu)</h3>
                
                <label>Tải lên File đáp án chuẩn</label>
                <input type="file" name="answer_key" accept=".pdf, image/*" required>
                <p class="note">AI sẽ đọc file này để hiểu các ý đúng cần có trong bài làm sinh viên.</p>
            </div>

            <div class="form-section rubric-zone">
                <h3><i class="fas fa-tasks section-icon"></i> Tiêu chí chấm điểm (Rubric)</h3>
                <p class="note">Chia nhỏ các ý chấm để AI chấm điểm chính xác tuyệt đối (Ví dụ: Ý 1 - 2đ, Ý 2 - 3đ...)</p>
                
                <div id="rubricContainer">
                    <div class="rubric-row">
                        <input type="text" name="rubric_content[]" placeholder="Nội dung ý chấm (Ví dụ: Nêu đúng khái niệm OOP)" style="flex: 4" required>
                        <input type="number" name="rubric_score[]" step="0.5" placeholder="Điểm" style="flex: 1" required>
                    </div>
                </div>

                <button type="button" class="btn-add-row" onclick="addRubricRow()">
                    <i class="fas fa-plus-circle"></i> Thêm tiêu chí chấm điểm
                </button>
            </div>

            <button type="submit" name="btn_create" class="btn-submit">
                <i class="fas fa-rocket"></i> Đăng bài tập & Kích hoạt AI Chấm điểm
            </button>
        </form>
    </div>

    <script>
        // Hàm JavaScript để thêm hàng Rubric mới khi nhấn nút
        function addRubricRow() {
            const container = document.getElementById('rubricContainer');
            const newRow = document.createElement('div');
            newRow.className = 'rubric-row';
            newRow.innerHTML = `
                <input type="text" name="rubric_content[]" placeholder="Tiêu chí tiếp theo..." style="flex: 4" required>
                <input type="number" name="rubric_score[]" step="0.5" placeholder="Điểm" style="flex: 1" required>
            `;
            container.appendChild(newRow);
        }
    </script>
</body>
</html>