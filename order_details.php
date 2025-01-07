<?php
session_start();
include('db.php');

// Kiểm tra nếu chưa đăng nhập thì chuyển về trang đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Lấy transaction_id từ URL
if (!isset($_GET['transaction_id'])) {
    echo "Không tìm thấy mã đơn hàng.";
    exit();
}
$transaction_id = $_GET['transaction_id'];

// Lấy chi tiết đơn hàng
$query = "SELECT o.quantity, o.size, p.name, p.image, p.price
          FROM `order` o
          JOIN product p ON o.product_id = p.product_id
          WHERE o.transaction_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();

// Lấy tổng tiền và thông tin đơn hàng
$transaction_query = "SELECT total_amount, status, 
                      IFNULL(shipping_fee, 0) AS shipping_fee 
                      FROM transaction WHERE transaction_id = ?";
$transaction_stmt = $conn->prepare($transaction_query);
$transaction_stmt->bind_param("i", $transaction_id);
$transaction_stmt->execute();
$transaction_result = $transaction_stmt->get_result();
$transaction = $transaction_result->fetch_assoc();

// Lấy phí giao hàng (nếu có) hoặc mặc định là 0
$shipping_fee = isset($transaction['shipping_fee']) ? $transaction['shipping_fee'] : 0;

// Xử lý hủy đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    // Cập nhật trạng thái đơn hàng
    $update_query = "UPDATE transaction SET status = 'cancelled' WHERE transaction_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $transaction_id);
    if ($update_stmt->execute()) {
        $transaction['status'] = 'cancelled';
        $message = "Đơn hàng đã được hủy thành công.";
    } else {
        $message = "Có lỗi xảy ra khi hủy đơn hàng.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Đơn Hàng</title>
    <link rel="stylesheet" href="css/order_detail.css">
</head>
<body>
    <div class="container">
        <h1>Chi Tiết Đơn Hàng</h1>
        <!-- Hiển thị thông báo nếu có -->
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        <table class="details-table">
            <thead>
                <tr>
                    <th>Tên sản phẩm</th>
                    <th>Hình ảnh</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php $total = 0; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                    $subtotal = $row['quantity'] * $row['price'];
                    $total += $subtotal;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?> (Size: <?php echo htmlspecialchars($row['size']); ?>)</td>
                        <td><img src="<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" width="50"></td>
                        <td><?php echo number_format($row['price'], 0, ',', '.'); ?>₫</td>
                        <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                        <td><?php echo number_format($subtotal, 0, ',', '.'); ?>₫</td>
                    </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="4"><strong>Phí giao hàng</strong></td>
                    <td><strong><?php echo number_format($shipping_fee, 0, ',', '.'); ?>₫</strong></td>
                </tr>
                <tr class="total-row">
                    <td colspan="4"><strong>Tổng tiền</strong></td>
                    <td><strong><?php echo number_format($total + $shipping_fee, 0, ',', '.'); ?>₫</strong></td>
                </tr>
            </tbody>
        </table>

        <?php if ($transaction['status'] !== 'cancelled'): ?>
            <form method="POST">
                <button type="submit" name="cancel_order" class="cancel-button">Hủy đơn</button>
                <a href="order.php" class="back-button">Quay lại</a>
            </form>
        <?php else: ?>
            <p class="status-message">Đơn hàng đã bị hủy.</p>
            <a href="order.php" class="back-button">Quay lại</a>
        <?php endif; ?>
    </div>
</body>
</html>
