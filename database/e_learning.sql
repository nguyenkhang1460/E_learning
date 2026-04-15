-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th3 25, 2026 lúc 08:36 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `e_learning`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'quiz',
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `file_url` varchar(255) DEFAULT NULL,
  `test_type` enum('quiz','essay') DEFAULT 'quiz',
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `duration` int(11) DEFAULT NULL COMMENT 'Thời gian làm bài (phút)',
  `deadline` datetime DEFAULT NULL COMMENT 'Thời hạn nộp bài'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `assignments`
--

INSERT INTO `assignments` (`id`, `class_id`, `title`, `content`, `type`, `difficulty`, `file_url`, `test_type`, `due_date`, `created_at`, `duration`, `deadline`) VALUES
(26, 6, 'a', NULL, 'quiz', 'medium', NULL, 'quiz', NULL, '2026-03-03 00:54:15', NULL, NULL),
(27, 6, 'test2', NULL, 'quiz', 'medium', NULL, 'quiz', NULL, '2026-03-03 02:37:01', NULL, NULL),
(28, 6, 'test123', NULL, 'quiz', 'medium', NULL, 'quiz', NULL, '2026-03-03 12:54:18', 3, '2026-03-03 20:00:00'),
(30, 9, '15 phút', NULL, 'quiz', 'medium', NULL, 'quiz', NULL, '2026-03-04 16:08:47', 5, NULL),
(31, 9, 'kiểm tra cuối môn', NULL, 'quiz', 'medium', NULL, 'quiz', NULL, '2026-03-04 16:28:16', 3, NULL),
(33, 6, 'aaa', NULL, 'quiz', 'easy', NULL, 'quiz', NULL, '2026-03-05 05:37:44', 3, NULL),
(34, 10, 'aaa', NULL, 'quiz', 'hard', NULL, 'quiz', NULL, '2026-03-07 09:53:11', NULL, NULL),
(35, 9, 'python 1', NULL, 'quiz', 'medium', NULL, 'quiz', NULL, '2026-03-11 02:30:43', NULL, NULL),
(36, 10, 'aaa', NULL, 'quiz', 'hard', NULL, 'quiz', NULL, '2026-03-17 19:00:50', NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `invite_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` enum('public','private') DEFAULT 'public',
  `requirements` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `classes`
--

INSERT INTO `classes` (`id`, `class_name`, `description`, `owner_id`, `invite_code`, `created_at`, `type`, `requirements`) VALUES
(6, 'test', 'a', 2, '90601A', '2026-02-08 12:13:45', 'private', '[\"Họ và Tên\",\"Mã số sinh viên\"]'),
(9, 'PYTHON', '', 2, '8D022B', '2026-03-04 16:04:56', 'private', '[\"Họ và Tên\",\"Mã số sinh viên\"]'),
(10, 'aaa', 'aaa', 2, 'D34341', '2026-03-07 09:51:52', 'public', ''),
(11, 'Ngôn ngữ mô hình hóa UML', '', 4, '842AEB', '2026-03-11 02:03:17', 'public', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `class_members`
--

CREATE TABLE `class_members` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `class_members`
--

INSERT INTO `class_members` (`id`, `user_id`, `class_id`) VALUES
(17, 2, 11),
(6, 3, 6),
(7, 4, 6),
(15, 4, 9),
(16, 4, 10),
(10, 5, 6);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `class_requests`
--

CREATE TABLE `class_requests` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`answers`)),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `class_requests`
--

INSERT INTO `class_requests` (`id`, `class_id`, `user_id`, `answers`, `status`, `created_at`) VALUES
(1, 6, 3, '{\"Họ và Tên\":\"Khang\",\"Mã số sinh viên\":\"123456\"}', 'approved', '2026-02-08 12:22:15'),
(2, 6, 4, '{\"Họ và Tên\":\"test1\",\"Mã số sinh viên\":\"123456\"}', 'approved', '2026-02-25 07:12:20'),
(4, 6, 5, '{\"Họ và Tên\":\"vĩ khang\",\"Mã số sinh viên\":\"123456\"}', 'approved', '2026-03-04 07:37:26'),
(6, 6, 5, '{\"Họ và Tên\":\"vĩ khang\",\"Mã số sinh viên\":\"123456\"}', 'approved', '2026-03-04 08:16:23'),
(7, 6, 5, '{\"Họ và Tên\":\"f\",\"Mã số sinh viên\":\"1\"}', 'approved', '2026-03-04 08:20:39'),
(9, 9, 4, '{\"Họ và Tên\":\"khang\",\"Mã số sinh viên\":\"123123\"}', 'approved', '2026-03-04 16:05:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lectures`
--

CREATE TABLE `lectures` (
  `id` int(11) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `file_url` text NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lectures`
--

INSERT INTO `lectures` (`id`, `class_id`, `title`, `file_url`, `file_type`, `created_at`) VALUES
(12, 6, 'thương mại điện tử', 'https://res.cloudinary.com/dwvsbcegm/image/upload/v1772449729/ehw8hxqke8etqeriegcq.pdf', 'pdf', '2026-03-02 11:08:50'),
(14, 9, 'giáo trình python', 'https://res.cloudinary.com/dwvsbcegm/image/upload/v1772640395/mal3c1twedu6tazysyol.pdf', 'pdf', '2026-03-04 16:06:35'),
(15, 10, 'aaa', 'https://res.cloudinary.com/dwvsbcegm/image/upload/v1772877145/cnpw8etpodfwpomngdzt.pdf', 'pdf', '2026-03-07 09:52:25'),
(16, 10, 'blockchain', 'https://res.cloudinary.com/dwvsbcegm/image/upload/v1773773995/otz4x6umiiepfab6pvq4.pdf', 'pdf', '2026-03-17 18:59:54'),
(17, 9, 'báo cáo', 'https://res.cloudinary.com/dwvsbcegm/image/upload/v1774407736/zaahzvppkfppqo1pgjm3.pdf', 'pdf', '2026-03-25 03:02:15');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_questions`
--

CREATE TABLE `quiz_questions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `correct_option` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `quiz_questions`
--

INSERT INTO `quiz_questions` (`id`, `assignment_id`, `question_text`, `options`, `correct_option`, `created_at`) VALUES
(1, 26, 'Thương mại điện tử (TMĐT) là gì?', '[\"A. Hình thức mua bán hàng hóa qua mạng xã hội\",\"B. Hình thức kinh doanh sử dụng công nghệ thông tin và internet\",\"C. Hình thức trao đổi hàng hóa qua biên giới\",\"D. Hình thức quảng cáo sản phẩm qua truyền hình\"]', 1, '2026-03-03 00:54:15'),
(2, 26, 'Lợi ích của thương mại điện tử là gì?', '[\"A. Giảm chi phí vận chuyển và tăng thời gian giao hàng\",\"B. Tăng cơ hội kinh doanh và giảm chi phí quảng cáo\",\"C. Cải thiện mối quan hệ với khách hàng và tăng doanh thu\",\"D. Tất cả các đáp án trên\"]', 3, '2026-03-03 00:54:15'),
(3, 26, 'Phân loại thương mại điện tử theo đối tượng tham gia là gì?', '[\"A. B2B, B2C, C2C, C2B\",\"B. B2B, B2C, C2C\",\"C. B2B, C2C, C2B\",\"D. B2C, C2C, C2B\"]', 0, '2026-03-03 00:54:15'),
(4, 26, 'An ninh trong thương mại điện tử là gì?', '[\"A. Bảo vệ thông tin khách hàng và ngăn chặn tấn công mạng\",\"B. Cải thiện mối quan hệ với khách hàng và tăng doanh thu\",\"C. Tăng cơ hội kinh doanh và giảm chi phí quảng cáo\",\"D. Giảm chi phí vận chuyển và tăng thời gian giao hàng\"]', 0, '2026-03-03 00:54:15'),
(5, 26, 'Tiếp thị trực tuyến là gì?', '[\"A. Hình thức quảng cáo sản phẩm qua truyền hình\",\"B. Hình thức kinh doanh sử dụng công nghệ thông tin và internet\",\"C. Hình thức tiếp cận khách hàng qua mạng xã hội và email\",\"D. Hình thức trao đổi hàng hóa qua biên giới\"]', 2, '2026-03-03 00:54:15'),
(6, 27, 'Thương mại điện tử (TMĐT) là gì?', '[\"A. Hình thức mua bán hàng hóa trực tiếp tại cửa hàng\",\"B. Hình thức mua bán hàng hóa qua môi trường internet\",\"C. Hình thức mua bán hàng hóa qua điện thoại\",\"D. Hình thức mua bán hàng hóa qua thư từ\"]', 1, '2026-03-03 02:37:01'),
(7, 27, 'Lợi ích của thương mại điện tử là gì?', '[\"A. Giảm thiểu chi phí vận chuyển và thời gian giao hàng\",\"B. Tăng cường khả năng cạnh tranh và mở rộng thị trường\",\"C. Cả A và B\",\"D. Không có lợi ích\"]', 2, '2026-03-03 02:37:01'),
(8, 27, 'Phân loại thương mại điện tử theo đối tượng tham gia là gì?', '[\"A. B2B, B2C, C2C\",\"B. B2B, B2C, C2B\",\"C. B2C, C2C, B2B\",\"D. B2B, C2B, B2C\"]', 0, '2026-03-03 02:37:01'),
(9, 27, 'An ninh trong thương mại điện tử là gì?', '[\"A. Bảo vệ thông tin cá nhân và tài khoản ngân hàng\",\"B. Bảo vệ hệ thống và mạng lưới khỏi các cuộc tấn công\",\"C. Cả A và B\",\"D. Không có liên quan\"]', 2, '2026-03-03 02:37:01'),
(10, 27, 'Tiếp thị trực tuyến là gì?', '[\"A. Hình thức quảng cáo qua môi trường internet\",\"B. Hình thức quảng cáo qua điện thoại\",\"C. Hình thức quảng cáo qua thư từ\",\"D. Hình thức quảng cáo qua truyền hình\"]', 0, '2026-03-03 02:37:01'),
(11, 27, 'Xây dựng hệ thống thương mại điện tử bao gồm những bước nào?', '[\"A. Xác định mục tiêu và yêu cầu, nghiên cứu thị trường và đối thủ\",\"B. Lập kế hoạch dự án, triển khai hệ thống và bán hàng đa kênh\",\"C. Cả A và B\",\"D. Không có bước cụ thể\"]', 2, '2026-03-03 02:37:01'),
(12, 27, 'Bán hàng đa kênh là gì?', '[\"A. Hình thức bán hàng qua một kênh duy nhất\",\"B. Hình thức bán hàng qua nhiều kênh khác nhau\",\"C. Hình thức bán hàng qua điện thoại\",\"D. Hình thức bán hàng qua thư từ\"]', 1, '2026-03-03 02:37:01'),
(13, 28, 'Thương mại điện tử (TMĐT) là gì?', '[\"A. Mua bán hàng hóa qua mạng internet\",\"B. Dịch vụ tài chính qua mạng internet\",\"C. Hình thức kinh doanh sử dụng công nghệ thông tin để thực hiện giao dịch\",\"D. Tất cả các lựa chọn trên\"]', 3, '2026-03-03 12:54:18'),
(14, 28, 'Các loại hình thanh toán phổ biến trong TMĐT là gì?', '[\"A. Thanh toán bằng tiền mặt\",\"B. Thanh toán bằng thẻ tín dụng\",\"C. Thanh toán bằng chuyển khoản ngân hàng\",\"D. Tất cả các lựa chọn trên\"]', 3, '2026-03-03 12:54:18'),
(15, 28, 'An ninh trong TMĐT bao gồm những vấn đề gì?', '[\"A. Bảo mật thông tin\",\"B. Quản lý rủi ro\",\"C. Pháp lý\",\"D. Tất cả các lựa chọn trên\"]', 3, '2026-03-03 12:54:18'),
(16, 28, 'Tiếp thị trực tuyến là gì?', '[\"A. Hình thức quảng cáo qua mạng internet\",\"B. Hình thức tiếp thị sử dụng email\",\"C. Hình thức tiếp thị sử dụng mạng xã hội\",\"D. Tất cả các lựa chọn trên\"]', 3, '2026-03-03 12:54:18'),
(17, 28, 'Xây dựng hệ thống TMĐT bao gồm những bước nào?', '[\"A. Xác định mục tiêu và yêu cầu\",\"B. Nghiên cứu thị trường và đối thủ\",\"C. Lập kế hoạch dự án và triển khai hệ thống\",\"D. Tất cả các lựa chọn trên\"]', 3, '2026-03-03 12:54:18'),
(20, 30, 'Làm thế nào để chạy một chương trình Python từ dòng lệnh?', '[\"A. Sử dụng lệnh python3 filename.py\",\"B. Sử dụng lệnh python filename.py\",\"C. Sử dụng lệnh run filename.py\",\"D. Tất cả các câu trên đều đúng\"]', 3, '2026-03-04 16:08:47'),
(21, 30, 'Ở Python, làm thế nào để bạn tạo một biến và gán giá trị cho nó?', '[\"A. Sử dụng lệnh let biến = giá trị\",\"B. Sử dụng lệnh var biến = giá trị\",\"C. Sử dụng lệnh const biến = giá trị\",\"D. Sử dụng lệnh biến = giá trị\"]', 3, '2026-03-04 16:08:47'),
(22, 30, 'Làm thế nào để bạn in ra một chuỗi trong Python?', '[\"A. Sử dụng lệnh print(chuỗi)\",\"B. Sử dụng lệnh echo chuỗi\",\"C. Sử dụng lệnh cout << chuỗi\",\"D. Sử dụng lệnh printf(chuỗi)\"]', 0, '2026-03-04 16:08:47'),
(23, 30, 'Làm thế nào để bạn tạo một chuỗi trong Python?', '[\"A. Sử dụng dấu ngoặc kép (\",\"B. Sử dụng dấu ngoặc đơn (\'\')\",\"C. Sử dụng dấu ngoặc kép (\",\"D. Sử dụng lệnh string()\"]', 2, '2026-03-04 16:08:47'),
(24, 30, 'Làm thế nào để bạn chuyển đổi một chuỗi sang chữ hoa trong Python?', '[\"A. Sử dụng phương thức upper()\",\"B. Sử dụng phương thức lower()\",\"C. Sử dụng phương thức title()\",\"D. Sử dụng phương thức capitalize()\"]', 0, '2026-03-04 16:08:47'),
(25, 31, 'Để chạy một chương trình Python từ terminal, bạn cần sử dụng lệnh nào?', '[\"A. python3 filename.py\",\"B. python filename.py\",\"C. run filename.py\",\"D. execute filename.py\"]', 0, '2026-03-04 16:28:16'),
(26, 31, 'Để tạo một biến trong Python, bạn cần sử dụng từ khóa nào?', '[\"A. let\",\"B. var\",\"C. Không cần từ khóa\",\"D. const\"]', 2, '2026-03-04 16:28:16'),
(27, 31, 'Phương thức nào được sử dụng để chuyển đổi một chuỗi sang chữ hoa trong Python?', '[\"A. upper()\",\"B. lower()\",\"C. title()\",\"D. capitalize()\"]', 0, '2026-03-04 16:28:16'),
(28, 31, 'Để thêm một giá trị vào một biến đã được khai báo trước đó, bạn cần sử dụng toán tử nào?', '[\"A. +\",\"B. -\",\"C. *\",\"D. \\/\"]', 0, '2026-03-04 16:28:16'),
(29, 31, 'Để chạy chương trình Python trong môi trường REPL, bạn cần nhập lệnh nào?', '[\"A. python\",\"B. python3\",\"C. run\",\"D. execute\"]', 0, '2026-03-04 16:28:16'),
(35, 33, 'Sau khi hoàn thành một ứng dụng di động, bước tiếp theo là gì?', '[\"A. Phát hành ứng dụng lên các cửa hàng ứng dụng\",\"B. Tiếp tục phát triển ứng dụng\",\"C. Kiểm tra ứng dụng trên các thiết bị khác nhau\",\"D. Xóa ứng dụng\"]', 0, '2026-03-05 05:37:44'),
(36, 33, 'Mục đích của việc tạo tài khoản nhà phát triển trên Google Play Console là gì?', '[\"A. Để tải ứng dụng từ Google Play Store\",\"B. Để phát hành ứng dụng lên Google Play Store\",\"C. Để sử dụng ứng dụng trên thiết bị di động\",\"D. Để xóa ứng dụng\"]', 1, '2026-03-05 05:37:44'),
(37, 33, 'Loại ứng dụng nào thường sử dụng In-App Purchases (IAP)?', '[\"A. Ứng dụng giáo dục\",\"B. Ứng dụng trò chơi\",\"C. Ứng dụng sản xuất\",\"D. Ứng dụng tài chính\"]', 1, '2026-03-05 05:37:44'),
(38, 34, 'Nêu nguyên nhân dẫn đến sự hình thành các vùng khí hậu khác nhau trên Trái Đất?', '[\"A. Sự chênh lệch về nhiệt độ và áp suất không khí giữa các vùng\",\"B. Sự khác biệt về độ cao và địa hình giữa các vùng\",\"C. Sự phân bố không đều của các đại dương và lục địa trên Trái Đất\",\"D. Tất cả các yếu tố trên\"]', 3, '2026-03-07 09:53:11'),
(39, 34, 'Phương pháp nào sau đây được sử dụng để xác định tuổi của các mẫu đá cổ?', '[\"A. Phương pháp định tuổi bằng đồng vị phóng xạ\",\"B. Phương pháp định tuổi bằng phân tích địa hóa\",\"C. Phương pháp định tuổi bằng quan sát địa chất\",\"D. Phương pháp định tuổi bằng đo lường từ tính\"]', 0, '2026-03-07 09:53:11'),
(40, 34, 'Nêu hiện tượng xảy ra khi một tia sáng đi từ môi trường có chiết suất thấp sang môi trường có chiết suất cao?', '[\"A. Tia sáng bị khúc xạ ra xa pháp tuyến\",\"B. Tia sáng bị khúc xạ về phía pháp tuyến\",\"C. Tia sáng không bị khúc xạ\",\"D. Tia sáng bị phản xạ hoàn toàn\"]', 1, '2026-03-07 09:53:11'),
(41, 34, 'Nêu nguyên nhân dẫn đến sự hình thành các đám mây?', '[\"A. Sự ngưng tụ của hơi nước trong không khí\",\"B. Sự kết tinh của các hạt bụi trong không khí\",\"C. Sự trộn lẫn của không khí nóng và không khí lạnh\",\"D. Tất cả các yếu tố trên\"]', 0, '2026-03-07 09:53:11'),
(42, 34, 'Nêu phương pháp được sử dụng để tách các chất ra khỏi hỗn hợp?', '[\"A. Phương pháp kết tinh\",\"B. Phương pháp chưng cất\",\"C. Phương pháp lọc\",\"D. Tất cả các phương pháp trên\"]', 3, '2026-03-07 09:53:11'),
(43, 35, 'Tại sao việc thêm Python vào PATH lại quan trọng trong quá trình cài đặt?', '[\"A. Để có thể chạy Python từ bất kỳ thư mục nào trong command prompt\",\"B. Để có thể chạy Python từ thư mục chính của hệ thống\",\"C. Để có thể chạy Python từ thư mục người dùng\",\"D. Để có thể chạy Python từ thư mục chương trình\"]', 0, '2026-03-11 02:30:43'),
(44, 35, 'Làm thế nào để bạn có thể chạy một chương trình Python từ terminal?', '[\"A. Sử dụng lệnh python3 và tên file Python\",\"B. Sử dụng lệnh python và tên file Python\",\"C. Sử dụng lệnh run và tên file Python\",\"D. Sử dụng lệnh execute và tên file Python\"]', 0, '2026-03-11 02:30:43'),
(45, 35, 'F-Strings trong Python được sử dụng để làm gì?', '[\"A. Để tạo ra các chuỗi có thể thay đổi\",\"B. Để tạo ra các chuỗi không thể thay đổi\",\"C. Để tạo ra các chuỗi có thể chứa các biến\",\"D. Để tạo ra các chuỗi không thể chứa các biến\"]', 2, '2026-03-11 02:30:43'),
(46, 35, 'Làm thế nào để bạn có thể tạo ra một biến trong Python?', '[\"A. Sử dụng từ khóa let\",\"B. Sử dụng từ khóa var\",\"C. Sử dụng từ khóa def\",\"D. Gán giá trị cho một tên biến\"]', 3, '2026-03-11 02:30:43'),
(47, 35, 'Tại sao việc sử dụng các tên biến có ý nghĩa lại quan trọng trong Python?', '[\"A. Để giúp các biến có thể chứa nhiều giá trị\",\"B. Để giúp các biến có thể được sử dụng trong nhiều nơi\",\"C. Để giúp cho code trở nên dễ đọc và hiểu hơn\",\"D. Để giúp cho code trở nên khó đọc và hiểu hơn\"]', 2, '2026-03-11 02:30:43'),
(48, 36, 'Ứng dụng của công nghệ Blockchain trong lĩnh vực y tế bao gồm việc quản lý hồ sơ sức khỏe điện tử (EHR) như thế nào?', '[\"A. Cho phép bệnh viện sở hữu và quản lý toàn bộ dữ liệu sức khỏe của bệnh nhân\",\"B. Bệnh nhân là người sở hữu dữ liệu sức khỏe của mình, và dữ liệu có thể chia sẻ an toàn giữa các bệnh viện\",\"C. Dữ liệu sức khỏe được lưu trữ tập trung tại một cơ quan y tế quốc gia\",\"D. Dữ liệu sức khỏe được mã hóa và chỉ có thể được truy cập bởi các bác sĩ\"]', 1, '2026-03-17 19:00:50'),
(49, 36, 'Ứng dụng của công nghệ Blockchain trong lĩnh vực giáo dục bao gồm việc xác minh văn bằng và chứng chỉ như thế nào?', '[\"A. Trường đại học cấp văn bằng và chứng chỉ dưới dạng giấy và không cần xác minh\",\"B. Trường đại học ký số lên văn bằng và lưu mã băm (Hash) lên Blockchain để xác minh\",\"C. Nhà tuyển dụng phải gọi điện về trường để xác minh văn bằng và chứng chỉ\",\"D. Văn bằng và chứng chỉ được cấp dưới dạng điện tử nhưng không cần xác minh\"]', 1, '2026-03-17 19:00:50'),
(50, 36, 'Ứng dụng của công nghệ Blockchain trong lĩnh vực bảo hiểm bao gồm việc tạo ra loại bảo hiểm nào?', '[\"A. Bảo hiểm truyền thống với việc thẩm định thiệt hại thủ công\",\"B. Bảo hiểm tham số (Parametric Insurance) dựa trên các sự kiện kích hoạt định trước\",\"C. Bảo hiểm dựa trên dữ liệu lớn (Big Data) nhưng không sử dụng Blockchain\",\"D. Bảo hiểm dựa trên trí tuệ nhân tạo (AI) nhưng không sử dụng Blockchain\"]', 1, '2026-03-17 19:00:50'),
(51, 36, 'Ứng dụng của công nghệ Blockchain trong lĩnh vực năng lượng bao gồm việc tạo ra mô hình lưới điện thông minh (Smart Grid) như thế nào?', '[\"A. Mô hình lưới điện tập trung, nơi công ty điện lực quản lý và phân phối điện năng\",\"B. Mô hình lưới điện phân tán, nơi hộ dân có pin mặt trời dư thừa điện có thể bán trực tiếp cho hàng xóm thông qua Blockchain\",\"C. Mô hình lưới điện hỗn hợp, nơi công ty điện lực và hộ dân cùng tham gia phân phối điện năng\",\"D. Mô hình lưới điện truyền thống, nơi điện năng được phân phối từ nhà máy điện đến hộ dân\"]', 1, '2026-03-17 19:00:50'),
(52, 36, 'Ứng dụng của công nghệ Blockchain trong lĩnh vực truyền thông và giải trí bao gồm việc quản lý bản quyền như thế nào?', '[\"A. Quản lý bản quyền thông qua trung gian như Spotify và Youtube\",\"B. Loại bỏ trung gian, nghệ sĩ nhận tiền bản quyền trực tiếp từ người nghe thông qua SmartContract\",\"C. Quản lý bản quyền thông qua cơ quan chính phủ\",\"D. Quản lý bản quyền thông qua công ty quản lý bản quyền\"]', 1, '2026-03-17 19:00:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quiz_submissions`
--

CREATE TABLE `quiz_submissions` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option` int(11) NOT NULL,
  `is_correct` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `quiz_submissions`
--

INSERT INTO `quiz_submissions` (`id`, `submission_id`, `question_id`, `selected_option`, `is_correct`) VALUES
(33, 33, 13, 3, 1),
(34, 33, 14, 3, 1),
(35, 33, 15, 3, 1),
(36, 33, 16, 3, 1),
(37, 33, 17, 3, 1),
(38, 34, 6, 1, 1),
(39, 34, 7, 2, 1),
(40, 34, 8, 0, 1),
(41, 34, 9, 2, 1),
(42, 34, 10, 0, 1),
(43, 34, 11, 2, 1),
(44, 34, 12, 1, 1),
(45, 35, 6, -1, 0),
(46, 35, 7, -1, 0),
(47, 35, 8, -1, 0),
(48, 35, 9, -1, 0),
(49, 35, 10, -1, 0),
(50, 35, 11, -1, 0),
(51, 35, 12, -1, 0),
(52, 36, 6, 2, 0),
(53, 36, 7, -1, 0),
(54, 36, 8, -1, 0),
(55, 36, 9, -1, 0),
(56, 36, 10, -1, 0),
(57, 36, 11, -1, 0),
(58, 36, 12, -1, 0),
(59, 37, 6, 2, 0),
(60, 37, 7, -1, 0),
(61, 37, 8, -1, 0),
(62, 37, 9, -1, 0),
(63, 37, 10, -1, 0),
(64, 37, 11, -1, 0),
(65, 37, 12, -1, 0),
(66, 38, 1, 1, 1),
(67, 38, 2, 3, 1),
(68, 38, 3, 1, 0),
(69, 38, 4, 0, 1),
(70, 38, 5, 1, 0),
(71, 39, 1, 1, 1),
(72, 39, 2, 3, 1),
(73, 39, 3, 1, 0),
(74, 39, 4, 0, 1),
(75, 39, 5, 1, 0),
(76, 40, 6, 1, 1),
(77, 40, 7, 2, 1),
(78, 40, 8, 0, 1),
(79, 40, 9, 2, 1),
(80, 40, 10, 0, 1),
(81, 40, 11, 2, 1),
(82, 40, 12, 1, 1),
(83, 41, 6, 1, 1),
(84, 41, 7, 2, 1),
(85, 41, 8, 0, 1),
(86, 41, 9, 2, 1),
(87, 41, 10, 0, 1),
(88, 41, 11, 2, 1),
(89, 41, 12, 1, 1),
(94, 45, 20, 3, 1),
(95, 45, 21, 1, 0),
(96, 45, 22, 0, 1),
(97, 45, 23, 2, 1),
(98, 45, 24, 1, 0),
(99, 46, 25, 1, 0),
(100, 46, 26, 1, 0),
(101, 46, 27, 0, 1),
(102, 46, 28, 0, 1),
(103, 46, 29, 0, 1),
(104, 47, 35, 0, 1),
(105, 47, 36, 0, 0),
(106, 47, 37, 0, 0),
(107, 48, 35, 1, 0),
(108, 48, 36, 2, 0),
(109, 48, 37, 0, 0),
(110, 49, 35, 0, 1),
(111, 49, 36, 0, 0),
(112, 49, 37, 0, 0),
(113, 50, 38, 2, 0),
(114, 50, 39, 0, 1),
(115, 50, 40, 3, 0),
(116, 50, 41, 3, 0),
(117, 50, 42, 3, 1),
(118, 51, 43, 0, 1),
(119, 51, 44, 0, 1),
(120, 51, 45, 0, 0),
(121, 51, 46, 0, 0),
(122, 51, 47, 0, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `report_type` enum('lecture','assignment','class') NOT NULL,
  `target_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','resolved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `student_file_url` varchar(255) NOT NULL,
  `student_file_type` varchar(50) NOT NULL,
  `ai_score` decimal(5,2) DEFAULT 0.00,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `violation_count` int(11) DEFAULT 0,
  `violation_log` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `submissions`
--

INSERT INTO `submissions` (`id`, `assignment_id`, `user_id`, `student_file_url`, `student_file_type`, `ai_score`, `submitted_at`, `violation_count`, `violation_log`) VALUES
(33, 28, 4, '', '', 10.00, '2026-03-03 13:19:12', 0, '[]'),
(34, 27, 5, '', '', 10.00, '2026-03-04 09:01:54', 0, '[]'),
(35, 27, 3, '', '', 0.00, '2026-03-04 09:39:22', 4, '[{\"time\":\"2026-03-04T09:39:05.490Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T09:39:09.654Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T09:39:11.355Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T09:39:13.330Z\",\"type\":\"tab_switch\"}]'),
(36, 27, 2, '', '', 0.00, '2026-03-04 09:49:08', 4, '[{\"time\":\"2026-03-04T09:48:58.521Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T09:49:00.303Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T09:49:02.033Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T09:49:04.105Z\",\"type\":\"tab_switch\"}]'),
(37, 27, 2, '', '', 0.00, '2026-03-04 09:49:11', 5, '[{\"time\":\"2026-03-04T09:48:58.521Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T09:49:00.303Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T09:49:02.033Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T09:49:04.105Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T09:49:08.209Z\",\"type\":\"tab_switch\"}]'),
(38, 26, 3, '', '', 6.00, '2026-03-04 09:57:37', 0, '[]'),
(39, 26, 3, '', '', 6.00, '2026-03-04 09:58:24', 0, '[]'),
(40, 27, 4, '', '', 10.00, '2026-03-04 10:02:51', 0, '[]'),
(41, 27, 4, '', '', 10.00, '2026-03-04 10:05:35', 0, '[]'),
(44, 26, 4, '', '', 0.00, '2026-03-04 15:59:41', 3, '[{\"time\":\"2026-03-04T15:59:37.326Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T15:59:39.486Z\",\"type\":\"tab_switch\"},{\"time\":\"2026-03-04T15:59:41.286Z\",\"type\":\"tab_switch\"}]'),
(45, 30, 4, '', '', 5.00, '2026-03-04 16:10:46', 1, '[{\"time\":\"2026-03-04T16:10:14.433Z\",\"type\":\"tab_switch\"}]'),
(46, 31, 4, '', '', 6.00, '2026-03-04 16:29:15', 0, '[]'),
(47, 33, 3, '', '', 3.33, '2026-03-05 05:38:07', 0, '[]'),
(48, 33, 4, '', '', 0.00, '2026-03-05 05:52:41', 1, '[{\"time\":\"2026-03-05T05:52:28.829Z\",\"type\":\"tab_switch\"}]'),
(49, 33, 5, '', '', 3.33, '2026-03-05 05:57:43', 0, '[]'),
(50, 34, 4, '', '', 4.00, '2026-03-07 09:53:48', 0, '[]'),
(51, 35, 4, '', '', 3.00, '2026-03-11 02:34:37', 1, '[{\"time\":\"2026-03-11T02:34:26.273Z\",\"type\":\"tab_switch\"}]');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','locked') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `role`, `created_at`, `status`) VALUES
(1, 'admin_hethong', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@hocai.edu.vn', 'ADMIN', 'admin', '2026-01-10 06:44:59', 'active'),
(2, 'nguyenvana', '$2y$10$Em0kOGcyZUPj0m.wvHB.geYLjwhC.MLTtqr8Zr4XucJ234a0KqfX6', 'vana@gmail.com', 'Nguyễn Văn A', 'user', '2026-01-10 06:44:59', 'active'),
(3, 'lethib', '$2y$10$NKDoUNURcMEzAb95SFi/9OSNmWJoovVNMvfF8msY3hjysJCwULKI2', 'abc123@gmail.com', 'Lê Thị B', 'user', '2026-01-12 18:23:14', 'active'),
(4, 'test1', '$2y$10$dIcqRgp4l3nTVhdqOJ9fk.LEM7jj8uZVZbIUg6IbORWtOUE45kHL.', 'test1@gmail.com', 'test1', 'user', '2026-02-25 07:11:11', 'active'),
(5, 'test2', '$2y$10$cds8st3v3ko/clxL9XZN..OnfOlKVFKT4J/DspNFnB01OtHvPOfou', 'test2@gmail.com', 'test2', 'user', '2026-02-25 16:28:38', 'active');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Chỉ mục cho bảng `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invite_code` (`invite_code`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Chỉ mục cho bảng `class_members`
--
ALTER TABLE `class_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_class` (`user_id`,`class_id`),
  ADD KEY `idx_class_members_class` (`class_id`),
  ADD KEY `idx_class_members_user` (`user_id`);

--
-- Chỉ mục cho bảng `class_requests`
--
ALTER TABLE `class_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_class_requests_class` (`class_id`),
  ADD KEY `idx_class_requests_status` (`status`);

--
-- Chỉ mục cho bảng `lectures`
--
ALTER TABLE `lectures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Chỉ mục cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assignment_id` (`assignment_id`);

--
-- Chỉ mục cho bảng `quiz_submissions`
--
ALTER TABLE `quiz_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_quiz_submissions_submission` (`submission_id`),
  ADD KEY `idx_quiz_submissions_question` (`question_id`);

--
-- Chỉ mục cho bảng `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Chỉ mục cho bảng `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_submissions_assignment` (`assignment_id`),
  ADD KEY `idx_submissions_user` (`user_id`),
  ADD KEY `idx_submissions_assignment_user` (`assignment_id`,`user_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `class_members`
--
ALTER TABLE `class_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `class_requests`
--
ALTER TABLE `class_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT cho bảng `lectures`
--
ALTER TABLE `lectures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT cho bảng `quiz_submissions`
--
ALTER TABLE `quiz_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT cho bảng `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `class_members`
--
ALTER TABLE `class_members`
  ADD CONSTRAINT `class_members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_members_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `class_requests`
--
ALTER TABLE `class_requests`
  ADD CONSTRAINT `class_requests_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`),
  ADD CONSTRAINT `class_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Các ràng buộc cho bảng `lectures`
--
ALTER TABLE `lectures`
  ADD CONSTRAINT `lectures_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quiz_questions`
--
ALTER TABLE `quiz_questions`
  ADD CONSTRAINT `quiz_questions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `quiz_submissions`
--
ALTER TABLE `quiz_submissions`
  ADD CONSTRAINT `quiz_submissions_ibfk_1` FOREIGN KEY (`submission_id`) REFERENCES `submissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_submissions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`),
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
