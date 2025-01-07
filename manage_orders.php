<?php
session_start();
include('db.php');

// Kiểm tra quyền Admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Xử lý duyệt đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $transaction_id = intval($_POST['approve']);
        $stmt = $conn->prepare("UPDATE transaction SET status = 'completed' WHERE transaction_id = ?");
        $stmt->bind_param("i", $transaction_id);
        if ($stmt->execute()) {
            echo "<script>alert('Đơn hàng đã được duyệt.'); window.location.href = 'manage_orders.php';</script>";
        } else {
            echo "<script>alert('Lỗi khi duyệt đơn hàng.');</script>";
        }
    }

    if (isset($_POST['delete'])) {
        $transaction_id = intval($_POST['delete']);
        
        // Xóa các mục trong bảng `order` liên quan đến transaction
        $stmtDeleteOrders = $conn->prepare("DELETE FROM `order` WHERE transaction_id = ?");
        $stmtDeleteOrders->bind_param("i", $transaction_id);
        $stmtDeleteOrders->execute();

        // Sau đó xóa transaction
        $stmtDeleteTransaction = $conn->prepare("DELETE FROM transaction WHERE transaction_id = ?");
        $stmtDeleteTransaction->bind_param("i", $transaction_id);
        if ($stmtDeleteTransaction->execute()) {
            echo "<script>alert('Đơn hàng đã được xóa.'); window.location.href = 'manage_orders.php';</script>";
        } else {
            echo "<script>alert('Lỗi khi xóa đơn hàng.');</script>";
        }
    }
}

// Lấy danh sách đơn hàng
$sql = "SELECT t.transaction_id, t.created_at, t.status, u.username, u.phone, u.address 
        FROM transaction t 
        JOIN user u ON t.user_id = u.user_id 
        ORDER BY t.created_at DESC";
$result = $conn->query($sql);
if (!$result) {
    die("Lỗi truy vấn: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <!-- Thanh menu dọc bên trái -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Trang chủ Admin</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="manage_users.php">Quản lý người dùng</a></li>
            <li><a href="admin_dashboard.php">Quản lý sản phẩm</a></li>
            <li><a href="manage_orders.php">Quản lý đơn hàng</a></li>
            <li><a href="manage_catalog.php">Quản lý danh mục</a></li>
        </ul>
    </div>

    <!-- Nội dung chính -->
    <div class="main-content">
        <!-- Thanh menu ngang -->
        <div class="topbar">
            <div class="topbar-left">
                <a href="index.php" class="home-icon">🏠 Trang chủ</a>
            </div>
            <div class="topbar-right">
                <span class="notification-icon">🔔</span>
                <span class="admin-name">Tài khoản: <?php echo $_SESSION['username']; ?>!</span>
                <a href="logout.php" class="logout-button">Đăng xuất</a>
            </div>
        </div>

        <!-- Nội dung quản lý đơn hàng -->
        <div class="container">
            <h1>Quản lý đơn hàng</h1>
            <table>
                <thead>
                    <tr>
                        <th class="col-transaction-id">ID</th>
                        <th class="col-name">Tên người nhận</th>
                        <th class="col-phone">Số điện thoại</th>
                        <th class="col-address">Địa chỉ</th>
                        <th class="col-status">Trạng thái</th>
                        <th class="col-approve">Duyệt</th>
                        <th class="col-details">Chi tiết</th>
                        <th class="col-delete">Xóa</th>
                        <th class="col-date">Ngày đặt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td>
                                <?php if ($row['status'] == 'pending'): ?>
                                    <span class="status-pending">Chưa duyệt</span>
                                <?php elseif ($row['status'] == 'completed'): ?>
                                    <span class="status-completed">Đã duyệt</span>
                                <?php else: ?>
                                    <span class="status-cancelled">Đã hủy</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] == 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <button type="submit" name="approve" value="<?php echo $row['transaction_id']; ?>" class="approve-btn">✔</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="order_detail_admin.php?transaction_id=<?php echo $row['transaction_id']; ?>" class="details-btn">👁</a>
                            </td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="delete" value="<?php echo $row['transaction_id']; ?>" class="delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?');">🗑</button>
                                </form>
                            </td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
