-- ============================================================
-- Migration: Thêm cột show_score vào bảng assignments
-- Ngày: 2026-04-17
-- Mô tả: Cột show_score cho phép giáo viên chọn có hiển thị
--         điểm cho học viên sau khi nộp bài hay không.
--         Code trong AssignmentRepository::createQuiz() đã INSERT
--         cột này nhưng bảng chưa có → gây lỗi MySQL.
-- ============================================================

USE `e_learning`;

-- Thêm cột show_score (mặc định = 1: hiển thị điểm)
ALTER TABLE `assignments`
    ADD COLUMN `show_score` TINYINT(1) NOT NULL DEFAULT 1
    COMMENT 'Hiển thị điểm cho học viên (1=có, 0=không)'
    AFTER `deadline`;
