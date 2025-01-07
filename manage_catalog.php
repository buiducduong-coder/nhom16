<?php
session_start();
include('db.php');

// Kiểm tra quyền Admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Xử lý thêm danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_catalog'])) {
    $name = trim($_POST['name']);

    if (empty($name)) {
        $error = "Tên danh mục không được để trống.";
    } else {
        $stmt = $conn->prepare("INSERT INTO catalog (name, parent_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $parent_id);

        if ($stmt->execute()) {
            $success = "Danh mục mới đã được thêm thành công.";
        } else {
            $error = "Lỗi khi thêm danh mục.";
        }
    }
}

// Xử lý xóa danh mục
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $catalog_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM catalog WHERE catalog_id = ?");
    $stmt->bind_param("i", $catalog_id);

    if ($stmt->execute()) {
        header("Location: manage_catalog.php");
        exit();
    } else {
        $error = "Lỗi khi xóa danh mục.";
    }
}

// Lấy danh sách danh mục
$sql_catalogs = "SELECT * FROM catalog ORDER BY parent_id ASC, name ASC";
$result_catalogs = $conn->query($sql_catalogs);
if (!$result_catalogs) {
    die("Lỗi truy vấn danh mục: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục</title>
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

        <!-- Nội dung quản lý danh mục -->
        <div class="container">
            <h1>Quản lý danh mục</h1>

            <!-- Thêm danh mục -->
            <form method="POST" class="add-catalog-form">
                <h2>Thêm danh mục mới</h2>
                <?php if (isset($error)): ?>
                    <p class="error-msg"><?php echo $error; ?></p>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <p class="success-msg"><?php echo $success; ?></p>
                <?php endif; ?>
                <label for="name">Tên danh mục:</label>
                <input type="text" id="name" name="name" required>

                
                

                <button type="submit" name="add_catalog">Thêm</button>
            </form>

            <!-- Danh sách danh mục -->
            <table>
                <thead>
                    <tr>
                        <th class="col-transaction-id">ID</th>
                        <th>Tên danh mục</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $result_catalogs->data_seek(0);
                    while ($row = $result_catalogs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['catalog_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>

                            <td><?php echo $row['created_at']; ?></td>
                            <td>
                                <a href="edit_catalog.php?id=<?php echo $row['catalog_id']; ?>">Sửa</a> |
                                <a href="manage_catalog.php?delete=<?php echo $row['catalog_id']; ?>" onclick="return confirm('Bạn chắc chắn muốn xóa danh mục này?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
