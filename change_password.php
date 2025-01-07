<?php
session_start();
include('db.php'); // Kết nối CSDL
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];

    // Sử dụng prepared statement để bảo mật SQL
    $stmt = $conn->prepare("SELECT * FROM product WHERE name LIKE ?");
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Lấy dữ liệu người dùng từ bảng `user`
$username = $_SESSION['username'];
$query = $conn->prepare("SELECT user_id, username, email, phone, address, created_at, updated_at, profile_image, password FROM user WHERE username = ?");
if (!$query) {
    die("Lỗi SQL: " . $conn->error);
}
$query->bind_param("s", $username);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Người dùng không tồn tại.";
    exit();
}

$message = '';

// Xử lý khi nhấn "Lưu Thay Đổi"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Kiểm tra mật khẩu hiện tại
    if ($user && password_verify($current_password, $user['password'])) {
        // Kiểm tra mật khẩu mới và xác nhận mật khẩu
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Cập nhật mật khẩu
            $updateQuery = $conn->prepare("UPDATE user SET password = ? WHERE username = ?");
            $updateQuery->bind_param("ss", $hashed_password, $username);
            if ($updateQuery->execute()) {
                $message = "Mật khẩu đã được thay đổi thành công!";
            } else {
                $message = "Có lỗi xảy ra khi thay đổi mật khẩu.";
            }
        } else {
            $message = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
        }
    } else {
        $message = "Mật khẩu hiện tại không đúng.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Cá Nhân</title>
    <link rel="stylesheet" href="css/profile.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
        }
        .message {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        .message.success {
            background-color: #2ecc71;
            color: white;
        }
        .message.error {
            background-color: #e74c3c;
            color: white;
        }
    </style>
</head>
<body>
<header>
    <div class="container">
        <nav>
            <div class="left-nav">
                <a href=""><img src="img/logo1.png" alt="" class="logo"></a>
                <a href="index.php">🏠 Trang chủ</a>
                <a href="cart.php">🛒 Giỏ hàng</a>
                <a href="catalog.php">📂 Danh mục sản phẩm</a>
            </div>
            <!-- Thanh tìm kiếm ở giữa -->
            <form class="search-bar" action="index.php" method="GET">
                <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Tìm kiếm</button>
            </form>
            <!-- Tên người dùng và ảnh đại diện -->
            <div class="right-nav">
                <?php if (isset($_SESSION['username'])): ?>
                    <!-- Khi đã đăng nhập -->
                    <?php 
                        // Lấy ảnh đại diện từ session hoặc ảnh mặc định
                        $profileImg = isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img']) ? $_SESSION['profile_img'] : 'img/default-avatar.jpg';
                    ?>
                    <div class="dropdown">
                        <button class="dropdown-toggle">
                            <img src="<?php echo htmlspecialchars($profileImg); ?>" alt="Avatar" class="profile-img">
                            <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        </button>
                        <div class="dropdown-menu">
                            <a href="profile.php"><i class="fas fa-user"></i> Trang cá nhân</a>
                            <a href="order.php"><i class="fa-solid fa-cart-shopping"></i>Đơn hàng</a>
                            <a href="change_password.php"><i class="fas fa-key"></i> Thay đổi mật khẩu</a>
                            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Khi chưa đăng nhập -->
                    <a href="login.php">🔑 Đăng nhập</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
    <div class="profile-container">
        <div class="profile-left">
            <div class="profile-header">
                <img src="<?php echo htmlspecialchars($user['profile_image'] ?: 'img/default-avatar.jpg'); ?>" alt="Avatar" class="profile-img">
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                <p>ID: <?php echo htmlspecialchars($user['user_id']); ?></p>
            </div>
            <div class="profile-info">
                <div class="profile-item">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="profile-item">
                    <i class="fas fa-phone"></i>
                    <span><?php echo htmlspecialchars($user['phone'] ?: 'Chưa cập nhật'); ?></span>
                </div>
            </div>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
                <a href="#"><i class="fab fa-pinterest"></i></a>
            </div>
        </div>

        <div class="profile-right">
            <h2>Thay đổi mật khẩu</h2>
            <!-- Hiển thị thông báo -->
            <?php if (!empty($message)): ?>
                <div class="message <?php echo strpos($message, 'thành công') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Mật khẩu hiện tại</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Vui lòng nhập mật khẩu hiện tại" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Mật khẩu mới</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Vui lòng nhập mật khẩu mới" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Nhập lại mật khẩu mới</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Vui lòng nhập lại mật khẩu mới" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-save">Lưu Thay Đổi</button>
                    <button type="button" class="btn-cancel" onclick="window.location.href='index.php'">Đóng</button>
                </div>
            </form>
        </div>
    </div>
    <footer>
        <div class="footer-container">
        <a href="#"><img src="img/logo1.png" alt="" class="logo"></a>
            <div class="footer-links">
                <ul>
                    <li><a href="#">Giới thiệu</a></li>
                    <li><a href="#">Điều khoản</a></li>
                    <li><a href="#">Chính sách bảo mật</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <p>Liên hệ: sdt: 0912345678 | email: dumishop@gmail.com</p>
            </div>
        </div>
    </footer>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
    const dropdown = document.querySelector(".dropdown");
    const toggleButton = document.querySelector(".dropdown-toggle");

    toggleButton.addEventListener("click", function (e) {
        e.stopPropagation();
        dropdown.classList.toggle("active");
    });

    document.addEventListener("click", function () {
        dropdown.classList.remove("active");
    });
});

    </script>
</body>
</html>
