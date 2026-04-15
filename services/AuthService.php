<?php
/**
 * AuthService — Business logic cho authentication
 * SRP: chỉ lo việc xác thực + quản lý tài khoản
 */
class AuthService
{
    private UserRepository $users;

    public function __construct()
    {
        $this->users = new UserRepository();
    }

    /**
     * Đăng nhập: tìm user, verify password, set session
     * @return array ['success' => bool, 'message' => string, 'role' => string]
     */
    public function login(string $username, string $password): array
    {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin!'];
        }

        $user = $this->users->findByUsername(trim($username));
        if (!$user) {
            return ['success' => false, 'message' => 'Tài khoản không tồn tại! Vui lòng kiểm tra lại tên đăng nhập.'];
        }

        // Chỉ dùng password_verify — không fallback plain text
        if (!password_verify($password, $user['password'])) {
            // Backward compat: nếu password chưa hash (plain text cũ), tự hash lại
            if ($password !== $user['password']) {
                return ['success' => false, 'message' => 'Sai mật khẩu! Vui lòng thử lại.'];
            }
            // Tự động upgrade plain-text password sang hash
            $this->users->updatePassword((int)$user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Auth::setSession($user);

        return ['success' => true, 'role' => $user['role'], 'message' => 'Đăng nhập thành công!'];
    }

    /**
     * Đăng ký tài khoản mới
     */
    public function register(string $fullName, string $username, string $email, string $password, string $confirmPassword): array
    {
        // Validate input
        if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'invalid_data'];
        }
        if ($password !== $confirmPassword) {
            return ['success' => false, 'error' => 'password_mismatch'];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'password_too_short'];
        }
        if ($this->users->usernameExists($username)) {
            return ['success' => false, 'error' => 'username_exists'];
        }
        if ($this->users->emailExists($email)) {
            return ['success' => false, 'error' => 'email_exists'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $id = $this->users->create($username, $email, $hashedPassword, $fullName);

        if ($id > 0) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'database_error'];
    }

    /**
     * Đổi mật khẩu
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            return ['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin!'];
        }
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự!'];
        }
        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'Mật khẩu mới và xác nhận mật khẩu không khớp!'];
        }
        if ($currentPassword === $newPassword) {
            return ['success' => false, 'message' => 'Mật khẩu mới không được trùng với mật khẩu hiện tại!'];
        }

        $user = $this->users->findById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Không tìm thấy thông tin người dùng!'];
        }

        // Verify mật khẩu hiện tại
        $isCorrect = password_verify($currentPassword, $user['password'])
                  || $currentPassword === $user['password']; // backward compat

        if (!$isCorrect) {
            return ['success' => false, 'message' => 'Mật khẩu hiện tại không chính xác!'];
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($this->users->updatePassword($userId, $hashed)) {
            return ['success' => true, 'message' => 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại.'];
        }
        return ['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật mật khẩu.'];
    }

    public function logout(): void
    {
        Auth::clearSession();
    }
}
