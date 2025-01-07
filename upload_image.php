<?php
include('db.php'); // Kết nối cơ sở dữ liệu

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['product_id']) && isset($_FILES['image'])) {
        $productId = $_POST['product_id'];
        $image = $_FILES['image'];

        if ($image['error'] === 0) {
            $uploadDir = "uploads/";
            $fileName = time() . "_" . basename($image['name']);
            $targetFilePath = $uploadDir . $fileName;

            if (move_uploaded_file($image['tmp_name'], $targetFilePath)) {
                $stmt = $conn->prepare("UPDATE product SET image = ? WHERE product_id = ?");
                $stmt->bind_param("si", $targetFilePath, $productId);

                if ($stmt->execute()) {
                    echo "Cập nhật ảnh thành công.";
                } else {
                    echo "Lỗi khi cập nhật cơ sở dữ liệu.";
                }
            } else {
                echo "Lỗi khi upload ảnh.";
            }
        } else {
            echo "Có lỗi xảy ra khi upload file.";
        }
    } else {
        echo "Dữ liệu không hợp lệ.";
    }
} else {
    echo "Phương thức không được hỗ trợ.";
}
?>
