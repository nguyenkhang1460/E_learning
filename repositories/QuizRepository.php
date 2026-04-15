<?php
/**
 * QuizRepository — Data Access Layer cho bảng quiz_questions + quiz_submissions
 */
class QuizRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

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

    public function hasSubmitted(int $assignmentId, int $userId): bool
    {
        return $this->db->count(
            'SELECT id FROM quiz_submissions WHERE assignment_id = ? AND user_id = ?',
            [$assignmentId, $userId], 'ii'
        ) > 0;
    }

    public function saveSubmission(int $assignmentId, int $userId, string $answersJson, float $score): int
    {
        return $this->db->insert(
            'INSERT INTO quiz_submissions (assignment_id, user_id, answers, score) VALUES (?, ?, ?, ?)',
            [$assignmentId, $userId, $answersJson, $score],
            'iisd'
        );
    }

    public function getSubmission(int $assignmentId, int $userId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM quiz_submissions WHERE assignment_id = ? AND user_id = ? LIMIT 1',
            [$assignmentId, $userId], 'ii'
        );
    }
}
