<?php 
session_start();
include('db.php');

// Kiểm tra quyền Admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Lấy danh sách sản phẩm từ cơ sở dữ liệu
$sql_products = "SELECT * FROM product";
$result_products = $conn->query($sql_products);
if (!$result_products) {
    die("Lỗi truy vấn sản phẩm: " . $conn->error);
}

// Lấy danh sách đơn hàng từ cơ sở dữ liệu
$sql_orders = "SELECT * FROM `order`";
$result_orders = $conn->query($sql_orders);
if (!$result_orders) {
    die("Lỗi truy vấn đơn hàng: " . $conn->error);
}

// Xử lý xóa sản phẩm
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];

    // Xóa sản phẩm từ cơ sở dữ liệu
    $stmt = $conn->prepare("DELETE FROM product WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "Có lỗi xảy ra khi xóa sản phẩm.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm và đơn hàng - Admin Dashboard</title>
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

        <!-- Nội dung trang chính: Quản lý sản phẩm -->
        <div class="container">
            <h1>Quản lý sản phẩm</h1>
            <a href="add_product.php" class="add-product-button">Thêm sản phẩm</a>
            <!-- Hiển thị danh sách sản phẩm với kích thước -->
<table>
    <thead>
        <tr>
            <th class="col-transaction-id">STT</th>
            <th>Tên sản phẩm</th>
            <th>Giá</th>
            <th>Kích thước</th> <!-- Thêm cột kích thước -->
            <th>Số lượng</th>
            <th>Ảnh</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $stt = 1;
        while ($row = $result_products->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $stt++; ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo number_format($row['price'], 0, ',', '.') . ' VND'; ?></td>
            <td><?php echo htmlspecialchars($row['available_sizes']); ?></td> <!-- Hiển thị kích thước -->
            <td><?php echo $row['quantity']; ?></td>
            <td>
                <?php if (!empty($row['image']) && file_exists($row['image'])) { ?>
                    <img src="<?php echo $row['image']; ?>" alt="Ảnh sản phẩm" width="100">
                <?php } else { ?>
                    Chưa có ảnh
                <?php } ?>
            </td>
            <td>
                <a href="edit_product.php?id=<?php echo $row['product_id']; ?>">Sửa</a> |
                <a href="admin_dashboard.php?delete=<?php echo $row['product_id']; ?>" onclick="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?')">Xóa</a>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
        </div>
    </div>
</body>
</html>
