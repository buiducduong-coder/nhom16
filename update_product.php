<?php
session_start();
include('db.php'); // Kết nối cơ sở dữ liệu

// Kiểm tra quyền Admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Kiểm tra và lấy dữ liệu cập nhật từ Ajax
if (isset($_POST['product_id'], $_POST['name'], $_POST['description'], $_POST['price'], $_POST['quantity'], $_POST['catalog_id'], $_POST['status'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $catalog_id = $_POST['catalog_id'];
    $status = $_POST['status'];

    // Cập nhật thông tin sản phẩm vào cơ sở dữ liệu
    $stmt = $conn->prepare("UPDATE product SET catalog_id = ?, name = ?, description = ?, price = ?, quantity = ?, status = ? WHERE product_id = ?");
    $stmt->bind_param("issdiss", $catalog_id, $name, $description, $price, $quantity, $status, $product_id);

    if ($stmt->execute()) {
        echo "Sản phẩm đã được cập nhật.";
    } else {
        echo "Có lỗi xảy ra khi cập nhật sản phẩm.";
    }
}
?>
