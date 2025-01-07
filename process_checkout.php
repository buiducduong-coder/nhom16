<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $phone = $_POST['phone'];
    $shipping = $_POST['shipping'];
    $payment = $_POST['payment'];

    // Tính phí giao hàng
    $shipping_fee = ($shipping === 'express') ? 25000 : 0;

    // Lấy tổng tiền từ giỏ hàng
    $query = "SELECT SUM(o.quantity * p.price) AS total FROM `order` o 
              JOIN product p ON o.product_id = p.product_id 
              WHERE o.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total = $row['total'] + $shipping_fee;

    // Lưu vào bảng transaction
    $query = "INSERT INTO transaction (user_id, total_amount, status) VALUES (?, ?, 'pending')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("id", $user_id, $total);
    if ($stmt->execute()) {
        $transaction_id = $stmt->insert_id;

        // Cập nhật trạng thái đơn hàng
        $query = "UPDATE `order` SET transaction_id = ?, status = 'processing' WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $transaction_id, $user_id);
        $stmt->execute();

        echo "Thanh toán thành công!";
        header('Location: success.php');
        exit();
    } else {
        echo "Có lỗi xảy ra!";
    }
}
?>
