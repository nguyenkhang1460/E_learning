<?php
/**
 * Redirect — Tập trung tất cả redirects
 * Thay thế hàng chục header("Location: ...") hardcoded
 */
class Redirect
{
    // ── Redirect đến URL cụ thể ────────────────────────────────────────────
    public static function to(string $url): never
    {
        if (headers_sent($file, $line)) {
            // Fallback khi headers đã được gửi (hiếm gặp nhưng cần handle)
            echo '<script>window.location.href=' . json_encode($url) . ';</script>';
            exit();
        }
        header('Location: ' . $url);
        exit();
    }

    // ── Redirect về trang trước (Referer) ─────────────────────────────────
    public static function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        self::to($referer ?: Routes::DASHBOARD_USER);
    }

    // ── Redirect với flash message ─────────────────────────────────────────
    public static function withSuccess(string $url, string $message): never
    {
        Session::success($message);
        self::to($url);
    }

    public static function withError(string $url, string $message): never
    {
        Session::error($message);
        self::to($url);
    }

    // ── Redirect về dashboard phù hợp với role ────────────────────────────
    public static function toDashboard(): never
    {
        self::to(Auth::isAdmin() ? Routes::DASHBOARD_ADMIN : Routes::DASHBOARD_USER);
    }
}
