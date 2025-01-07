<?php 
session_start();
include('db.php'); // Kết nối cơ sở dữ liệu

// Kiểm tra quyền Admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Lấy danh sách người dùng từ bảng 'user'
$sql_users = "SELECT user_id, username, email, phone, address, role, status FROM user";
$result_users = $conn->query($sql_users);
if (!$result_users) {
    die("Lỗi truy vấn người dùng: " . $conn->error);
}

// Xử lý xóa người dùng
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: manage_users.php");
        exit();
    } else {
        echo "Có lỗi xảy ra khi xóa người dùng.";
    }
}

// Xử lý cập nhật người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE user SET username = ?, email = ?, phone = ?, address = ?, role = ?, status = ? WHERE user_id = ?");
    $stmt->bind_param("ssssssi", $username, $email, $phone, $address, $role, $status, $user_id);
    if ($stmt->execute()) {
        header("Location: manage_users.php");
        exit();
    } else {
        echo "Có lỗi xảy ra khi cập nhật người dùng.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng</title>
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
    <h1>Quản lý người dùng</h1>
    <table>
            <thead>
            <tr>
                <th class="col-transaction-id">ID</th>
                <th class="col-username">Tên đăng nhập</th>
                <th class="col-name">Email</th>
                <th class="col-phone">Phone</th>
                <th class="col-address">Địa chỉ</th>
                <th>Vai trò</th>
                <th class="col-status">Trạng thái</th>
                <th>Hành động</th>
            </tr>
            </thead>
        <tbody>
            <?php while ($row = $result_users->fetch_assoc()) { ?>
                <tr>
                    <form method="POST">
                        <td><?php echo $row['user_id']; ?></td>
                        <td><input type="text" name="username" value="<?php echo $row['username']; ?>"></td>
                        <td><input type="email" name="email" value="<?php echo $row['email']; ?>"></td>
                        <td><input type="text" name="phone" value="<?php echo $row['phone']; ?>"></td>
                        <td><input type="text" name="address" value="<?php echo $row['address']; ?>"></td>
                        <td>
                            <select name="role">
                                <option value="customer" <?php if ($row['role'] == 'customer') echo 'selected'; ?>>Customer</option>
                                <option value="admin" <?php if ($row['role'] == 'admin') echo 'selected'; ?>>Admin</option>
                            </select>
                        </td>
                        <td>
                            <select name="status">
                                <option value="active" <?php if ($row['status'] == 'active') echo 'selected'; ?>>Active</option>
                                <option value="inactive" <?php if ($row['status'] == 'inactive') echo 'selected'; ?>>Inactive</option>
                            </select>
                        </td>
                        <td>
                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                            <button type="submit">Cập nhật</button>
                            <a href="manage_users.php?delete=<?php echo $row['user_id']; ?>" onclick="return confirm('Bạn chắc chắn muốn xóa người dùng này?')">Xóa</a>
                        </td>
                    </form>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
    </div>
</body>
</html>
