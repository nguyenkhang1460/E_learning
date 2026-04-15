<?php
/**
 * QuizService — Business logic cho tạo quiz AI, chấm điểm, và lưu quiz
 */
class QuizService
{
    private QuizRepository $quizRepo;
    private AssignmentRepository $assignmentRepo;
    private LectureRepository $lectureRepo;

    public function __construct()
    {
        $this->quizRepo       = new QuizRepository();
        $this->assignmentRepo = new AssignmentRepository();
        $this->lectureRepo    = new LectureRepository();
    }

    /**
     * Lưu quiz (assignment + questions) vào database
     * Thay thế logic phức tạp trong save_quiz.php
     */
    public function save(int $classId, string $title, string $difficulty, ?int $duration, ?string $deadline, int $showScore, array $questions, array $options, array $correctIndexes): array
    {
        if (empty($title)) {
            return ['success' => false, 'message' => 'Tiêu đề bài tập không được để trống!'];
        }
        if (empty($questions)) {
            return ['success' => false, 'message' => 'Không có câu hỏi nào!'];
        }

        $assignmentId = $this->assignmentRepo->createQuiz(
            $classId, $title, $difficulty, $duration, $deadline, $showScore
        );

        if ($assignmentId <= 0) {
            return ['success' => false, 'message' => 'Lỗi khi tạo bài tập!'];
        }

        foreach ($questions as $i => $questionText) {
            $optionsJson   = json_encode($options[$i] ?? [], JSON_UNESCAPED_UNICODE);
            $correctOption = (int)($correctIndexes[$i] ?? 0);
            $this->quizRepo->saveQuestion($assignmentId, $questionText, $optionsJson, $correctOption);
        }

        return ['success' => true, 'assignmentId' => $assignmentId, 'classId' => $classId];
    }

    /**
     * Chấm điểm bài làm quiz
     */
    public function grade(int $assignmentId, int $userId, array $postData): array
    {
        $assignment = $this->assignmentRepo->findById($assignmentId);
        if (!$assignment) {
            return ['success' => false, 'message' => 'Không tìm thấy bài tập!'];
        }

        // Lấy đáp án đúng từ quiz_questions
        $questions = $this->quizRepo->getQuestionsByAssignment($assignmentId);
        if (empty($questions)) {
            return ['success' => false, 'message' => 'Bài tập không có câu hỏi!'];
        }

        // Kiểm tra đã nộp chưa
        if ($this->quizRepo->hasSubmitted($assignmentId, $userId)) {
            return ['success' => false, 'message' => 'Bạn đã nộp bài rồi!'];
        }

        $correctCount   = 0;
        $totalQuestions = count($questions);
        $userAnswers    = [];

        foreach ($questions as $q) {
            $qId       = $q['id'];
            $fieldName = 'answer_' . $qId;
            $userAns   = isset($postData[$fieldName]) ? (int)$postData[$fieldName] : -1;
            $userAnswers[$qId] = $userAns;

            if ($userAns === (int)$q['correct_option']) {
                $correctCount++;
            }
        }

        // Tính điểm trên thang 10
        $score = $totalQuestions > 0
               ? round(($correctCount / $totalQuestions) * 10, 2)
               : 0;

        $answersJson = json_encode($userAnswers, JSON_UNESCAPED_UNICODE);

        // Lưu vào quiz_submissions
        $this->quizRepo->saveSubmission($assignmentId, $userId, $answersJson, $score);

        // Lưu vào submissions (backward compat)
        $this->assignmentRepo->saveLegacySubmission($assignmentId, $userId, $score);

        return [
            'success'        => true,
            'score'          => $score,
            'correctCount'   => $correctCount,
            'totalQuestions' => $totalQuestions,
            'title'          => $assignment['title'],
        ];
    }

    /**
     * Gọi AI (Groq) generate quiz từ tài liệu
     * Tách logic gọi API ra khỏi file controller
     */
    public function generateWithAI(array $groqConfig, ?int $lectureId, ?array $uploadedFile, int $numQuestions, string $difficulty): array
    {
        $apiKey      = $groqConfig['api_key'] ?? '';
        $endpoint    = $groqConfig['endpoint'] ?? 'https://api.groq.com/openai/v1/chat/completions';
        $textModel   = $groqConfig['text_model'] ?? 'llama-3.3-70b-versatile';
        $visionModel = $groqConfig['vision_model'] ?? 'llama-3.2-90b-vision-preview';

        if (empty($apiKey)) {
            return ['error' => 'Chưa cấu hình GROQ API key'];
        }

        // Xác định nguồn tài liệu
        if ($uploadedFile !== null) {
            $fileContent  = file_get_contents($uploadedFile['tmp_name']);
            $fileName     = $uploadedFile['name'];
            $lectureType  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        } else {
            if (!$lectureId) {
                return ['error' => 'Vui lòng chọn tài liệu hoặc upload file!'];
            }
            $lecture = $this->lectureRepo->findById($lectureId);
            if (!$lecture) {
                return ['error' => 'Không tìm thấy tài liệu trong hệ thống!'];
            }
            $fileContent = @file_get_contents($lecture['file_url']);
            if (!$fileContent) {
                return ['error' => 'Không thể tải tài liệu từ máy chủ lưu trữ!'];
            }
            $lectureType = strtolower($lecture['file_type'] ?? 'pdf');
        }

        $promptText = "NHIỆM VỤ: Tạo $numQuestions câu hỏi trắc nghiệm chất lượng cao, độ khó $difficulty.
                       Trả về DUY NHẤT một JSON object mảng 'questions'.
                       Mỗi câu có: 'question', 'options' (mảng 4 câu bắt đầu bằng A., B., C., D.), 'correct_index' (0-3), và 'explanation'.";

        if ($lectureType === 'pdf') {
            $payload = $this->buildPdfPayload($fileContent, $promptText, $textModel);
        } else {
            $payload = $this->buildImagePayload($fileContent, $lectureType, $promptText, $visionModel);
        }

        return $this->callGroq($apiKey, $endpoint, $payload);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function buildPdfPayload(string $fileContent, string $prompt, string $model): array
    {
        $parser  = new \Smalot\PdfParser\Parser();
        $pdf     = $parser->parseContent($fileContent);
        $docText = mb_substr(mb_convert_encoding($pdf->getText(), 'UTF-8', 'UTF-8'), 0, 10000);

        return [
            'model'           => $model,
            'temperature'     => 0.3,
            'messages'        => [
                ['role' => 'system', 'content' => 'Bạn là chuyên gia giáo dục. Chỉ trả về JSON thuần.'],
                ['role' => 'user',   'content' => "Nội dung: $docText\n\n$prompt"],
            ],
            'response_format' => ['type' => 'json_object'],
        ];
    }

    private function buildImagePayload(string $fileContent, string $ext, string $prompt, string $model): array
    {
        $mime      = ($ext === 'png') ? 'image/png' : 'image/jpeg';
        $imageB64  = base64_encode($fileContent);

        return [
            'model'    => $model,
            'messages' => [[
                'role'    => 'user',
                'content' => [
                    ['type' => 'image_url', 'image_url' => ['url' => "data:$mime;base64,$imageB64"]],
                    ['type' => 'text',      'text'      => $prompt],
                ],
            ]],
            'response_format' => ['type' => 'json_object'],
        ];
    }

    private function callGroq(string $apiKey, string $endpoint, array $payload): array
    {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST,           true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     $jsonPayload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,        120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);

        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['error' => 'Lỗi kết nối: ' . $curlErr];
        }

        $decoded = json_decode($response, true);
        if (!$decoded || isset($decoded['error'])) {
            return ['error' => $decoded['error']['message'] ?? 'Lỗi API Groq không xác định.'];
        }

        if (!isset($decoded['choices'][0]['message']['content'])) {
            return ['error' => 'Response từ Groq không đúng cấu trúc.'];
        }

        $raw = $decoded['choices'][0]['message']['content'];
        $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $raw = preg_replace('/\s*```$/', '', $raw);

        if (!(str_starts_with(trim($raw), '{') && str_ends_with(trim($raw), '}'))) {
            if (preg_match('/\{.*\}/s', $raw, $matches)) {
                $raw = $matches[0];
            }
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'AI trả về dữ liệu không phải JSON hợp lệ.'];
        }

        return $data;
    }
}
