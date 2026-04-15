<?php
/**
 * Autoloader — Tự động load class khi cần
 * Thay thế hàng chục require_once rải rác
 *
 * Sử dụng: require_once __DIR__ . '/../core/autoload.php';
 * Sau đó dùng class bình thường: new AuthService(), Auth::requireLogin(), v.v.
 */

// Tính root path của project (E_learning/)
$_ELEARNING_ROOT = dirname(__DIR__); // core/ -> E_learning/

spl_autoload_register(function (string $class) use ($_ELEARNING_ROOT): void {
    $classMap = [
        // Core
        'Database'              => 'core/Database.php',
        'Auth'                  => 'core/Auth.php',
        'Session'               => 'core/Session.php',
        'Redirect'              => 'core/Redirect.php',

        // Config
        'Routes'                => 'config/routes.php',

        // Repositories
        'UserRepository'        => 'repositories/UserRepository.php',
        'ClassRepository'       => 'repositories/ClassRepository.php',
        'LectureRepository'     => 'repositories/LectureRepository.php',
        'AssignmentRepository'  => 'repositories/AssignmentRepository.php',
        'QuizRepository'        => 'repositories/QuizRepository.php',
        'MemberRepository'      => 'repositories/MemberRepository.php',
        'ReportRepository'      => 'repositories/ReportRepository.php',

        // Services
        'AuthService'           => 'services/AuthService.php',
        'ClassService'          => 'services/ClassService.php',
        'LectureService'        => 'services/LectureService.php',
        'QuizService'           => 'services/QuizService.php',
        'GradeService'          => 'services/GradeService.php',
        'ReportService'         => 'services/ReportService.php',
        'MemberService'         => 'services/MemberService.php',
    ];

    if (isset($classMap[$class])) {
        require_once $_ELEARNING_ROOT . '/' . $classMap[$class];
    }
});
