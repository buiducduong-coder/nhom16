<?php
session_start();
include('db.php');

// Kiểm tra quyền Admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Lấy thông tin danh mục cần chỉnh sửa
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_catalog.php');
    exit();
}

$catalog_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM catalog WHERE catalog_id = ?");
$stmt->bind_param("i", $catalog_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: manage_catalog.php');
    exit();
}
$catalog = $result->fetch_assoc();

// Xử lý cập nhật danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

    if (empty($name)) {
        $error = "Tên danh mục không được để trống.";
    } elseif ($parent_id === $catalog_id) {
        $error = "Danh mục cha không được trùng với danh mục hiện tại.";
    } else {
        $stmtUpdate = $conn->prepare("UPDATE catalog SET name = ?, parent_id = ? WHERE catalog_id = ?");
        $stmtUpdate->bind_param("sii", $name, $parent_id, $catalog_id);

        if ($stmtUpdate->execute()) {
            $success = "Danh mục đã được cập nhật thành công.";
            // Cập nhật thông tin hiển thị sau khi chỉnh sửa
            $catalog['name'] = $name;
            $catalog['parent_id'] = $parent_id;
        } else {
            $error = "Lỗi khi cập nhật danh mục.";
        }
    }
}

// Lấy danh sách danh mục (để hiển thị trong dropdown)
$sql_catalogs = "SELECT * FROM catalog WHERE catalog_id != $catalog_id ORDER BY parent_id ASC, name ASC";
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
    <title>Chỉnh sửa danh mục</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Trang chủ Admin</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="manage_users.php">Quản lý người dùng</a></li>
            <li><a href="admin_dashboard.php">Quản lý sản phẩm</a></li>
            <li><a href="manage_orders.php">Quản lý đơn hàng</a></li>
            <li><a href="manage_catalog.php" class="active">Quản lý danh mục</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
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

        <!-- Chỉnh sửa danh mục -->
        <div class="container">
            <h1>Chỉnh sửa danh mục</h1>
            <form method="POST" class="edit-catalog-form">
                <?php if (isset($error)): ?>
                    <p class="error-msg"><?php echo $error; ?></p>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <p class="success-msg"><?php echo $success; ?></p>
                <?php endif; ?>
                
                <label for="name">Tên danh mục:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($catalog['name']); ?>" required>

                <label for="parent_id">Danh mục cha:</label>
                <select id="parent_id" name="parent_id">
                    <option value="">Không có</option>
                    <?php while ($row = $result_catalogs->fetch_assoc()): ?>
                        <option value="<?php echo $row['catalog_id']; ?>" <?php echo ($catalog['parent_id'] == $row['catalog_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($row['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <button type="submit" class="btn-save">Lưu thay đổi</button>
                <a href="manage_catalog.php" class="btn-cancel">Hủy</a>
            </form>
        </div>
    </div>
</body>
</html>
