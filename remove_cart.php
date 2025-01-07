<?php
session_start();
include('db.php');

// Kiểm tra nếu người dùng chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập!']);
    exit();
}

// Lấy user_id từ session
$user_id = $_SESSION['user_id'];

// Lấy product_id từ yêu cầu AJAX
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id > 0) {
    $stmt = $conn->prepare("DELETE FROM `order` WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Xóa sản phẩm thất bại.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
}
?>
