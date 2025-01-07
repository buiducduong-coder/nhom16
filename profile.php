<?php
session_start();
include('db.php'); // Kết nối CSDL
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];

    // Sử dụng prepared statement để bảo mật SQL
    $stmt = $conn->prepare("SELECT * FROM product WHERE name LIKE ?");
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("s", $searchTerm); // Tránh SQL Injection
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
$query = $conn->prepare("SELECT user_id, username, email, phone, address, created_at, updated_at, profile_image FROM user WHERE username = ?");
if (!$query) {
    die("Lỗi SQL: " . $conn->error); // Hiển thị lỗi nếu câu truy vấn không hợp lệ
}
$query->bind_param("s", $username);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Nếu không tìm thấy người dùng
if (!$user) {
    echo "Người dùng không tồn tại.";
    exit();
}

// Xử lý khi nhấn "Lưu thay đổi"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Cập nhật thông tin người dùng
    $updateQuery = $conn->prepare("UPDATE user SET email = ?, phone = ?, address = ?, updated_at = NOW() WHERE username = ?");
    if (!$updateQuery) {
        die("Lỗi SQL: " . $conn->error); // Hiển thị lỗi nếu câu truy vấn không hợp lệ
    }
    $updateQuery->bind_param("ssss", $email, $phone, $address, $username);
    if ($updateQuery->execute()) {
        $_SESSION['success_message'] = "Thông tin đã được cập nhật thành công!";
        header('Location: profile.php');
        exit();
    } else {
        echo "Có lỗi xảy ra khi cập nhật thông tin.";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

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
                        $profileImg = isset($_SESSION['profile_img']) && !empty($_SESSION['profile_img']) ? $_SESSION['profile_img'] : 'img/default-avatars.jpg';
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
        <img src="<?php echo htmlspecialchars($user['profile_image'] ?: 'img/default-avatars.jpg'); ?>" alt="Avatar" class="profile-img">
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
            <h2>Thông Tin Cá Nhân</h2>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Địa chỉ Email (*)</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="address">Địa chỉ</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">
                </div>
                <div class="form-group">
                    <label>Thời gian đăng ký</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['created_at']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Đăng nhập gần đây</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['updated_at']); ?>" disabled>
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
