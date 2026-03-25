<?php
$host = "localhost";
$db_user = "root"; // Mặc định của XAMPP
$db_pass = "";     // Mặc định của XAMPP là trống
$db_name = "e_learning"; // tên Database

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}
// Thiết lập font tiếng Việt
mysqli_set_charset($conn, "utf8mb4");
?>