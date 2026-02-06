<?php
include 'db_config.php';

$assignment_id = $_GET['assignment_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contents = $_POST['content'];
    $scores   = $_POST['score'];

    $rubric = [];
    foreach ($contents as $i => $text) {
        if (trim($text) !== '') {
            $rubric[] = [
                "id" => $i + 1,
                "content" => $text,
                "score" => (float)$scores[$i]
            ];
        }
    }

    $rubric_json = json_encode($rubric, JSON_UNESCAPED_UNICODE);

    $sql = "INSERT INTO assignment_rubrics (assignment_id, rubric_json)
            VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $assignment_id, $rubric_json);
    $stmt->execute();

    echo "<script>alert('Lưu rubric thành công');</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Nhập Rubric</title>
<style>
body { font-family: Arial; background:#f4f6f8; }
.container {
  width: 700px; margin: 30px auto; background:#fff;
  padding: 20px; border-radius: 8px;
}
.row { display:flex; gap:10px; margin-bottom:10px; }
.row input { padding:8px; }
button { padding:8px 14px; cursor:pointer; }
</style>
</head>

<body>
<div class="container">
<h2>Nhập Rubric chấm bài</h2>

<form method="post" id="rubricForm">
  <div id="rubricRows">
    <div class="row">
      <input name="content[]" placeholder="Nội dung ý chấm" style="flex:3">
      <input name="score[]" type="number" step="0.5" placeholder="Điểm" style="flex:1">
    </div>
  </div>

  <button type="button" onclick="addRow()">➕ Thêm ý</button>
  <br><br>
  <button type="submit">💾 Lưu Rubric</button>
</form>
</div>

<script>
function addRow() {
  const div = document.createElement('div');
  div.className = 'row';
  div.innerHTML = `
    <input name="content[]" placeholder="Nội dung ý chấm" style="flex:3">
    <input name="score[]" type="number" step="0.5" placeholder="Điểm" style="flex:1">
  `;
  document.getElementById('rubricRows').appendChild(div);
}
</script>
</body>
</html>
