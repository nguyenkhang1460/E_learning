<?php
/**
 * QuizRepository — Data Access Layer cho bảng quiz_questions + quiz_submissions
 *
 * Schema thực tế quiz_submissions:
 *   (id, submission_id, question_id, selected_option, is_correct)
 *   FK: submission_id → submissions.id (CASCADE)
 *   FK: question_id  → quiz_questions.id (CASCADE)
 */
class QuizRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Quiz Questions ─────────────────────────────────────────────────────

    public function saveQuestion(int $assignmentId, string $questionText, string $optionsJson, int $correctOption): int
    {
        return $this->db->insert(
            'INSERT INTO quiz_questions (assignment_id, question_text, options, correct_option) VALUES (?, ?, ?, ?)',
            [$assignmentId, $questionText, $optionsJson, $correctOption],
            'issi'
        );
    }

    public function getQuestionsByAssignment(int $assignmentId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM quiz_questions WHERE assignment_id = ? ORDER BY id ASC',
            [$assignmentId], 'i'
        );
    }

    // ── Quiz Submissions (chi tiết từng câu) ───────────────────────────────

    /**
     * Kiểm tra user đã nộp bài chưa — thông qua bảng submissions
     * (quiz_submissions không có cột assignment_id/user_id)
     */
    public function hasSubmitted(int $assignmentId, int $userId): bool
    {
        return $this->db->count(
            'SELECT id FROM submissions WHERE assignment_id = ? AND user_id = ?',
            [$assignmentId, $userId], 'ii'
        ) > 0;
    }

    /**
     * Lưu chi tiết đáp án từng câu hỏi vào quiz_submissions
     * Khớp với schema thực tế: (submission_id, question_id, selected_option, is_correct)
     */
    public function saveQuestionAnswer(int $submissionId, int $questionId, int $selectedOption, int $isCorrect): int
    {
        return $this->db->insert(
            'INSERT INTO quiz_submissions (submission_id, question_id, selected_option, is_correct) VALUES (?, ?, ?, ?)',
            [$submissionId, $questionId, $selectedOption, $isCorrect],
            'iiii'
        );
    }

    /**
     * Lấy chi tiết bài làm (từng câu) qua submission_id
     */
    public function getDetailsBySubmission(int $submissionId): array
    {
        return $this->db->fetchAll(
            'SELECT qs.*, qq.question_text, qq.options, qq.correct_option
             FROM quiz_submissions qs
             JOIN quiz_questions qq ON qs.question_id = qq.id
             WHERE qs.submission_id = ?
             ORDER BY qq.id ASC',
            [$submissionId], 'i'
        );
    }

    /**
     * Lấy submission_id từ bảng submissions
     */
    public function getSubmissionId(int $assignmentId, int $userId): ?int
    {
        $row = $this->db->fetchOne(
            'SELECT id FROM submissions WHERE assignment_id = ? AND user_id = ? LIMIT 1',
            [$assignmentId, $userId], 'ii'
        );
        return $row ? (int)$row['id'] : null;
    }
}
