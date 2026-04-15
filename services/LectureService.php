<?php
/**
 * LectureService — Business logic cho upload và quản lý bài giảng
 */
class LectureService
{
    private LectureRepository $lectures;

    public function __construct()
    {
        $this->lectures = new LectureRepository();
    }

    /**
     * Upload file lên Cloudinary và lưu DB
     * @param int    $classId
     * @param string $title
     * @param array  $file  — $_FILES['lecture_file']
     * @param array  $cloudinaryConfig — ['cloud_name'=>..., 'upload_preset'=>...]
     */
    public function uploadAndSave(int $classId, string $title, array $file, array $cloudinaryConfig): array
    {
        // Validate file type
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Chỉ cho phép tải lên file PDF hoặc Hình ảnh!'];
        }

        $cloudName    = $cloudinaryConfig['cloud_name'] ?? '';
        $uploadPreset = $cloudinaryConfig['upload_preset'] ?? '';

        if (empty($cloudName) || empty($uploadPreset)) {
            return ['success' => false, 'message' => 'Thiếu cấu hình Cloudinary trong config/api_keys.php'];
        }

        // Upload lên Cloudinary
        $url     = "https://api.cloudinary.com/v1_1/$cloudName/auto/upload";
        $postData = [
            'file'          => new CURLFile($file['tmp_name']),
            'upload_preset' => $uploadPreset,
            'resource_type' => 'auto',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!isset($response['secure_url'])) {
            $errorMsg = $response['error']['message'] ?? 'Lỗi không xác định';
            return ['success' => false, 'message' => 'Cloudinary error: ' . $errorMsg];
        }

        $fileUrl  = $response['secure_url'];
        $fileType = ($ext === 'pdf') ? 'pdf' : 'image';

        $id = $this->lectures->create($classId, $title, $fileUrl, $fileType);
        return ['success' => $id > 0, 'message' => 'Đã tải lên tài liệu thành công!', 'classId' => $classId];
    }

    /**
     * Xóa bài giảng
     */
    public function delete(int $lectureId, int $classId): array
    {
        $lecture = $this->lectures->findById($lectureId);
        if (!$lecture || (int)$lecture['class_id'] !== $classId) {
            return ['success' => false, 'message' => 'Bài giảng không tồn tại hoặc không thuộc lớp này!'];
        }

        $fileUrl = $this->lectures->delete($lectureId);
        // File trên Cloudinary không cần xóa local (URL online)
        return ['success' => true, 'message' => 'Đã xóa bài giảng!'];
    }

    /**
     * Chỉnh sửa tiêu đề bài giảng
     */
    public function updateTitle(int $lectureId, string $newTitle): array
    {
        if (empty(trim($newTitle))) {
            return ['success' => false, 'message' => 'Tiêu đề không được để trống!'];
        }

        if ($this->lectures->update($lectureId, trim($newTitle))) {
            return ['success' => true, 'message' => 'Đã cập nhật tiêu đề!'];
        }
        return ['success' => false, 'message' => 'Lỗi khi cập nhật tiêu đề!'];
    }
}
