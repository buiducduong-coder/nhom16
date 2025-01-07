<?php 
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dumishop";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
} else {
    // Kết nối thành công, có thể xóa dòng này trong môi trường thực tế
    // echo "Kết nối thành công!";
}
?>
