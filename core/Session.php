<?php
/**
 * Session — Quản lý flash messages tập trung
 * Thay thế $_SESSION['error'] / $_SESSION['success'] rải rác
 */
class Session
{
    // ── Ghi flash message (xem 1 lần rồi tự xóa) ─────────────────────────
    public static function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    // ── Lấy và xóa flash message ──────────────────────────────────────────
    public static function getFlash(string $key): ?string
    {
        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }

    // ── Kiểm tra có flash message không ──────────────────────────────────
    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    // ── Xóa tất cả flash messages ─────────────────────────────────────────
    public static function clearFlashes(): void
    {
        unset($_SESSION['_flash']);
    }

    // ── Helper cho success message ────────────────────────────────────────
    public static function success(string $message): void
    {
        self::flash('success', $message);
    }

    // ── Helper cho error message ──────────────────────────────────────────
    public static function error(string $message): void
    {
        self::flash('error', $message);
    }

    // ── Lấy error message (backward compat với $_SESSION['error']) ─────────
    public static function getError(): ?string
    {
        // Hỗ trợ cả cách cũ lẫn cách mới
        $fromFlash = self::getFlash('error');
        if ($fromFlash !== null) return $fromFlash;

        // Backward compat với code cũ dùng $_SESSION['error']
        $legacy = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        return $legacy;
    }

    // ── Lấy success message (backward compat) ─────────────────────────────
    public static function getSuccess(): ?string
    {
        $fromFlash = self::getFlash('success');
        if ($fromFlash !== null) return $fromFlash;

        $legacy = $_SESSION['success'] ?? null;
        unset($_SESSION['success']);
        return $legacy;
    }
}
