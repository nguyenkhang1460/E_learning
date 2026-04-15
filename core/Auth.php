<?php
/**
 * Auth — Tập trung toàn bộ logic authentication & authorization
 * Thay thế 15+ block copy-paste kiểm tra session
 */
class Auth
{
    // ── Kiểm tra đăng nhập, redirect nếu chưa ─────────────────────────────
    public static function requireLogin(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Vui lòng đăng nhập để tiếp tục.');
            Redirect::to(Routes::LOGIN);
        }
    }

    // ── Yêu cầu quyền Admin ───────────────────────────────────────────────
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            Session::flash('error', 'Bạn không có quyền truy cập trang này.');
            Redirect::to(Routes::DASHBOARD_USER);
        }
    }

    // ── Nếu đã đăng nhập thì redirect (dùng cho trang login/register) ─────
    public static function requireGuest(): void
    {
        if (self::check()) {
            Redirect::to(self::isAdmin() ? Routes::DASHBOARD_ADMIN : Routes::DASHBOARD_USER);
        }
    }

    // ── Kiểm tra có đăng nhập không ───────────────────────────────────────
    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    // ── Kiểm tra là admin không ───────────────────────────────────────────
    public static function isAdmin(): bool
    {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    // ── Kiểm tra là owner của một lớp (hoặc admin) ────────────────────────
    public static function isOwnerOrAdmin(int $ownerId): bool
    {
        return self::isAdmin() || self::userId() === $ownerId;
    }

    // ── Lấy user ID hiện tại ──────────────────────────────────────────────
    public static function userId(): int
    {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    // ── Lấy username hiện tại ─────────────────────────────────────────────
    public static function username(): string
    {
        return (string)($_SESSION['username'] ?? '');
    }

    // ── Lấy role hiện tại ─────────────────────────────────────────────────
    public static function role(): string
    {
        return (string)($_SESSION['role'] ?? '');
    }

    // ── Set session sau khi login thành công ──────────────────────────────
    public static function setSession(array $user): void
    {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
    }

    // ── Xóa session khi logout ────────────────────────────────────────────
    public static function clearSession(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
}
