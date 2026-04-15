<?php
/**
 * Routes — Tập trung tất cả URL constants
 * Sử dụng: Redirect::to(Routes::LOGIN)
 */
class Routes
{
    // ── Auth ──────────────────────────────────────────────────────────────
    const LOGIN              = '/E_learning/modules/auth/login.php';
    const LOGOUT             = '/E_learning/modules/auth/logout.php';
    const REGISTER           = '/E_learning/modules/auth/register.html';
    const CHANGE_PASSWORD    = '/E_learning/modules/auth/change_password.php';

    // ── Dashboard ─────────────────────────────────────────────────────────
    const DASHBOARD_USER     = '/E_learning/dashboard/user_dashboard.php';
    const DASHBOARD_ADMIN    = '/E_learning/dashboard/admin_dashboard.php';

    // ── Class ─────────────────────────────────────────────────────────────
    const CLASS_CREATE       = '/E_learning/modules/class/create_class.html';
    const CLASS_JOIN         = '/E_learning/modules/class/join_class.html';

    // ── View ──────────────────────────────────────────────────────────────
    const VIEW_REPORTS       = '/E_learning/views/view_reports.php';
    const QUIZ_RESULT        = '/E_learning/views/quiz_result.php';

    // ── Dynamic routes ────────────────────────────────────────────────────
    public static function manageClass(int $id): string
    {
        return '/E_learning/modules/class/manage_class.php?id=' . $id;
    }

    public static function manageLectures(int $id): string
    {
        return '/E_learning/modules/lecture/manage_lectures.php?id=' . $id;
    }

    public static function manageRequests(int $classId = 0): string
    {
        $base = '/E_learning/modules/member/manage_requests.php';
        return $classId > 0 ? $base . '?id=' . $classId : $base;
    }

    public static function viewAssignments(int $classId): string
    {
        return '/E_learning/modules/assignment/view_assignments.php?id=' . $classId;
    }

    public static function takeQuiz(int $assignmentId): string
    {
        return '/E_learning/modules/quiz/take_quiz.php?id=' . $assignmentId;
    }
}
