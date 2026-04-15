# 🎓 E-Learning Platform

Nền tảng học tập trực tuyến hỗ trợ quản lý lớp học, bài giảng, bài tập trắc nghiệm với tính năng **tạo đề tự động bằng AI (Groq)**.

---

## 📋 Mục Lục

- [Tính Năng](#-tính-năng)
- [Công Nghệ Sử Dụng](#-công-nghệ-sử-dụng)
- [Kiến Trúc Dự Án](#-kiến-trúc-dự-án)
- [Cấu Trúc Thư Mục](#-cấu-trúc-thư-mục)
- [Cài Đặt](#-cài-đặt)
- [Cấu Hình](#-cấu-hình)
- [Hướng Dẫn Sử Dụng](#-hướng-dẫn-sử-dụng)
- [API Tích Hợp](#-api-tích-hợp)
- [Bảo Mật](#-bảo-mật)
- [Database Schema](#-database-schema)

---

## ✨ Tính Năng

### 👤 Xác Thực & Tài Khoản
- Đăng ký / Đăng nhập / Đăng xuất
- Phân quyền 3 cấp: **Admin**, **Giáo viên (Chủ phòng)**, **Học viên**
- Đổi mật khẩu an toàn (bcrypt)

### 🏫 Quản Lý Lớp Học
- Tạo lớp **Public** (tham gia ngay) hoặc **Private** (cần duyệt)
- Sinh mã mời ngẫu nhiên 6 ký tự
- Tham gia lớp bằng mã mời
- Xem danh sách thành viên, bài giảng, bài tập
- Rời lớp, xóa lớp (có xóa cascading với Transaction)

### 📚 Bài Giảng
- Upload tài liệu PDF/Hình ảnh lên **Cloudinary**
- Xem, chỉnh sửa tiêu đề, xóa bài giảng
- Quản lý danh sách bài giảng theo lớp

### ✅ Bài Tập & Trắc Nghiệm
- **Tạo đề trắc nghiệm tự động bằng AI (Groq LLM)**
  - Đọc nội dung từ PDF có sẵn trong lớp
  - Hoặc upload PDF/ảnh mới để AI phân tích
  - Hỗ trợ Vision API cho file hình ảnh
- Thiết lập số câu hỏi (1–20), mức độ (Dễ/Vừa/Khó), thời gian, deadline
- Tùy chọn công bố điểm ngay hay giữ lại

### 📝 Làm Bài & Chống Gian Lận
- Giao diện làm bài chuyên nghiệp, đếm thời gian
- **Anti-cheat system**: Phát hiện chuyển tab, thoát fullscreen
- Tối đa 3 lần cảnh cáo → Tự động nộp bài
- Ngăn chuột phải, F12, Ctrl+Shift+I trong khi làm bài

### 📊 Chấm Điểm & Xuất Điểm
- Tự động chấm điểm ngay sau khi nộp
- Xuất bảng điểm ra **file CSV** với BOM (hỗ trợ tiếng Việt trong Excel)
- Thống kê: điểm trung bình, cao nhất, thấp nhất, tỷ lệ hoàn thành

### 🚩 Báo Cáo Vi Phạm
- Học viên báo cáo lớp học, bài giảng, bài tập
- Admin xem và xử lý báo cáo

### 🛡️ Quản Trị (Admin)
- Dashboard tổng quan: tổng users, lớp học, bài tập, bài nộp, báo cáo
- Xem danh sách người dùng và lớp học mới nhất
- Truy cập và xóa bất kỳ lớp học nào

---

## 🛠 Công Nghệ Sử Dụng

| Thành phần | Công nghệ |
|------------|-----------|
| Backend    | PHP 8.x (procedural + OOP) |
| Database   | MySQL (qua XAMPP) |
| Frontend   | HTML5, CSS3 (Vanilla), JavaScript (ES6+) |
| File Storage | [Cloudinary](https://cloudinary.com/) |
| AI Engine  | [Groq API](https://groq.com/) (llama-3.3-70b + vision) |
| PDF Parser | [smalot/pdfparser](https://github.com/smalot/pdfparser) |
| Icons      | Font Awesome 6.4 |
| Server     | XAMPP (Apache + MySQL) |

---

## 🏗 Kiến Trúc Dự Án

Dự án được tổ chức theo mô hình **3-Layer Architecture** tuân thủ nguyên tắc SOLID:

```
Request → Controller (modules/) → Service (services/) → Repository (repositories/) → Database
                                                          ↑
                                               Core Layer (core/)
```

| Lớp | Thư mục | Trách nhiệm |
|-----|---------|-------------|
| **Core** | `core/` | Auth, Session, Redirect, Database wrapper |
| **Repository** | `repositories/` | Chỉ truy vấn database (Prepared Statements) |
| **Service** | `services/` | Business logic thuần túy |
| **Controller** | `modules/*/` | Nhận input, gọi service, redirect |
| **View** | `views/`, `dashboard/` | Hiển thị HTML |

---

## 📁 Cấu Trúc Thư Mục

```
E_learning/
│
├── index.php                   # Entry point — tự redirect theo session
│
├── config/
│   ├── db_config.php           # Kết nối MySQL
│   ├── routes.php              # URL constants tập trung
│   ├── api_keys.php            # API keys (KHÔNG commit lên Git)
│   └── api_keys.example.php    # Template cấu hình API
│
├── core/                       # Framework nhẹ tự viết
│   ├── autoload.php            # Class autoloader
│   ├── Database.php            # Singleton DB + Prepared Statements + Transaction
│   ├── Auth.php                # requireLogin(), requireAdmin(), isAdmin()
│   ├── Session.php             # Flash messages
│   └── Redirect.php           # Tập trung tất cả redirect
│
├── repositories/               # Data Access Layer
│   ├── UserRepository.php
│   ├── ClassRepository.php     # Có transaction-based delete
│   ├── LectureRepository.php
│   ├── AssignmentRepository.php
│   ├── QuizRepository.php
│   ├── MemberRepository.php
│   └── ReportRepository.php
│
├── services/                   # Business Logic Layer
│   ├── AuthService.php         # login, register, changePassword
│   ├── ClassService.php        # create, delete, join, leave
│   ├── LectureService.php      # upload Cloudinary, delete
│   ├── QuizService.php         # AI generate, grade, save
│   ├── GradeService.php        # exportCSV
│   ├── ReportService.php       # submit report
│   └── MemberService.php       # approve/reject (có kiểm tra quyền)
│
├── modules/                    # Slim Controllers (10-25 dòng mỗi file)
│   ├── auth/                   # Login, Register, Logout, Change Password
│   ├── class/                  # Create, Delete, Join, Leave, Manage
│   ├── lecture/                # Upload, Edit, Delete, Manage
│   ├── assignment/             # Create, Delete, View
│   ├── quiz/                   # API Generate, Save, Submit, Take
│   ├── member/                 # Approve Action, Manage Requests
│   ├── grading/                # Export CSV
│   └── report/                 # Report Process
│
├── dashboard/
│   ├── admin_dashboard.php     # Dashboard admin
│   └── user_dashboard.php      # Dashboard user/teacher
│
├── views/
│   ├── quiz_result.php         # Trang kết quả sau khi nộp quiz
│   └── view_reports.php        # Admin xem báo cáo
│
├── assets/
│   ├── css/
│   │   ├── style.css           # Style chung
│   │   ├── admin_style.css     # Style admin dashboard
│   │   ├── user_style.css      # Style user dashboard + manage class
│   │   └── create_class_style.css
│   └── js/
│       └── xu_ly.js            # Frontend logic (AJAX, anti-cheat, quiz UI)
│
├── database/
│   └── e_learning.sql          # Schema + dữ liệu mẫu
│
├── vendor/                     # Composer packages (smalot/pdfparser)
├── diagram/                    # Tài liệu phân tích nghiệp vụ
└── test_case/                  # Test cases
```

---

## 🚀 Cài Đặt

### Yêu Cầu
- **XAMPP** (PHP 8.0+, MySQL 5.7+, Apache)
- **Composer** (để cài smalot/pdfparser)
- Tài khoản **Cloudinary** (miễn phí)
- API key **Groq** (miễn phí tại [console.groq.com](https://console.groq.com))

### Các Bước

**1. Clone dự án vào thư mục XAMPP:**
```bash
git clone <repo-url> d:/phanmem/xampp/htdocs/E_learning
```

**2. Import database:**
```bash
# Mở phpMyAdmin hoặc dùng MySQL CLI:
mysql -u root -p < database/e_learning.sql
```
Hoặc vào `http://localhost/phpmyadmin` → Import → chọn `database/e_learning.sql`

**3. Cài Composer dependencies:**
```bash
cd d:/phanmem/xampp/htdocs/E_learning
composer install
```

**4. Cấu hình kết nối database:**

Mở `config/db_config.php` và chỉnh thông tin kết nối:
```php
$host     = 'localhost';
$dbname   = 'e_learning';
$username = 'root';
$password = '';          // Mật khẩu MySQL của bạn
```

**5. Cấu hình API Keys:**
```bash
# Copy file mẫu:
cp config/api_keys.example.php config/api_keys.php
```
Sau đó chỉnh `config/api_keys.php` với thông tin thật (xem phần [Cấu Hình](#-cấu-hình)).

**6. Truy cập ứng dụng:**
```
http://localhost/E_learning/
```

---

## ⚙️ Cấu Hình

### `config/api_keys.php`

```php
<?php
return [
    // Cloudinary — lưu trữ file bài giảng
    'cloudinary' => [
        'cloud_name'    => 'your_cloud_name',
        'upload_preset' => 'your_upload_preset',  // Unsigned preset
        'api_key'       => 'your_api_key',
        'api_secret'    => 'your_api_secret',
    ],

    // Groq AI — tạo câu hỏi trắc nghiệm
    'groq' => [
        'api_key'      => 'gsk_xxxxxxxxxxxxxxxxxxxx',
        'endpoint'     => 'https://api.groq.com/openai/v1/chat/completions',
        'text_model'   => 'llama-3.3-70b-versatile',    // Đọc PDF
        'vision_model' => 'llama-3.2-90b-vision-preview', // Đọc ảnh
    ],
];
```

> ⚠️ **Lưu ý:** File `api_keys.php` đã được thêm vào `.gitignore`. Không bao giờ commit file này lên Git.

### Tạo Tài Khoản Admin

Chạy SQL sau trong phpMyAdmin:
```sql
INSERT INTO users (username, email, password, full_name, role, created_at)
VALUES (
    'admin',
    'admin@example.com',
    '$2y$10$<hash_bcrypt_của_password>',
    'Quản trị viên',
    'admin',
    NOW()
);
```

Hoặc đăng ký bình thường rồi update role:
```sql
UPDATE users SET role = 'admin' WHERE username = 'your_username';
```

---

## 📖 Hướng Dẫn Sử Dụng

### Luồng Giáo Viên (Teacher)
```
Đăng ký → Tạo lớp học → Chia sẻ mã mời
→ Upload bài giảng (PDF/ảnh lên Cloudinary)
→ Tạo bài tập trắc nghiệm (AI tự soạn đề từ bài giảng)
→ Duyệt yêu cầu tham gia (lớp private)
→ Xem kết quả → Xuất điểm CSV
```

### Luồng Học Viên (Student)
```
Đăng ký → Tham gia lớp (nhập mã mời)
→ Xem bài giảng
→ Làm bài trắc nghiệm (fullscreen, anti-cheat)
→ Xem điểm ngay sau khi nộp
```

### Luồng Admin
```
Đăng nhập → Admin Dashboard
→ Xem thống kê tổng quan
→ Xem/xóa lớp học bất kỳ
→ Xem và xử lý báo cáo vi phạm
```

---

## 🔌 API Tích Hợp

### Groq AI (Quiz Generation)

| Endpoint | Method | Mô tả |
|----------|--------|-------|
| `/modules/quiz/api_generate_quiz.php` | GET | Tạo quiz từ bài giảng có sẵn (`?lecture_id=X&num=5&difficulty=medium`) |
| `/modules/quiz/api_generate_quiz.php` | POST | Tạo quiz từ file upload (multipart `upload_file`) |

**Response mẫu:**
```json
{
  "questions": [
    {
      "question": "Câu hỏi số 1?",
      "options": ["A. Đáp án A", "B. Đáp án B", "C. Đáp án C", "D. Đáp án D"],
      "correct_index": 2,
      "explanation": "Giải thích tại sao C đúng..."
    }
  ]
}
```

### Cloudinary Upload
File được upload trực tiếp từ server PHP bằng `CURLFile` → nhận về `secure_url` để lưu vào database.

---

## 🔒 Bảo Mật

| Biện pháp | Chi tiết |
|-----------|----------|
| **Password Hashing** | `password_hash()` bcrypt — không lưu plain text |
| **Prepared Statements** | Tất cả query qua `Database::query()` — chống SQL Injection |
| **Session Auth** | `Auth::requireLogin()` ở đầu mọi trang cần đăng nhập |
| **Permission Check** | `Auth::isOwnerOrAdmin()` kiểm tra quyền trước mọi action nhạy cảm |
| **CSRF** | Form sử dụng POST cho mọi action thay đổi dữ liệu |
| **Anti-cheat** | Phát hiện tab switch, fullscreen exit trong khi làm bài |
| **Transaction** | Xóa lớp học dùng Transaction — không để dữ liệu bị xóa một phần |

---

## 🗄️ Database Schema

### Các Bảng Chính

```
users            — Tài khoản người dùng (id, username, email, password, full_name, role)
classes          — Lớp học (id, class_name, invite_code, owner_id, type, requirements)
class_members    — Thành viên lớp học (class_id, user_id)
class_requests   — Yêu cầu tham gia (id, class_id, user_id, answers, status)
lectures         — Bài giảng (id, class_id, title, file_url, file_type)
assignments      — Bài tập (id, class_id, title, type, difficulty, duration, deadline)
quiz_questions   — Câu hỏi trắc nghiệm (id, assignment_id, question_text, options, correct_option)
quiz_submissions — Bài làm trắc nghiệm (id, assignment_id, user_id, answers, score)
submissions      — Bài nộp chung (id, assignment_id, user_id, student_file, score, ai_score)
reports          — Báo cáo vi phạm (id, user_id, class_id, report_type, target_id, reason)
```

### Quan Hệ Chính

```
users ──< classes (owner_id)
users ──< class_members >── classes
users ──< class_requests >── classes
classes ──< lectures
classes ──< assignments ──< quiz_questions
assignments ──< quiz_submissions >── users
assignments ──< submissions >── users
classes ──< reports >── users
```

---

## 🧑‍💻 Phát Triển

### Thêm Module Mới
1. Tạo `repositories/XxxRepository.php` — chỉ chứa query
2. Tạo `services/XxxService.php` — business logic
3. Tạo `modules/xxx/xxx_process.php` — controller mỏng (15-25 dòng)
4. Đăng ký class trong `core/autoload.php`
5. Thêm route vào `config/routes.php` nếu cần

### Convention

```php
// Controller (slim — chỉ nhận input, gọi service, redirect)
Auth::requireLogin();
$result = (new XxxService())->doSomething(...input...);
$result['success']
    ? Redirect::withSuccess(Routes::SOMEWHERE, $result['message'])
    : Redirect::withError(Routes::SOMEWHERE, $result['message']);

// Repository (chỉ SQL)
return $this->db->fetchOne('SELECT * FROM xxx WHERE id = ?', [$id], 'i');

// Service (business logic thuần)
public function doSomething(...): array {
    // validate → repository → return ['success' => bool, 'message' => string]
}
```

---

## 📄 License

Dự án học tập — MIT License.

---

*Được xây dựng với PHP thuần (No Framework) + kiến trúc SOLID.*
