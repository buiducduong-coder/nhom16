<?php
session_start();
include('db.php');

// Kiểm tra nếu chưa đăng nhập thì chuyển về trang đăng nhập
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Lọc theo từ khóa tìm kiếm
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

// Lấy user_id từ session
$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn hàng
$query = "SELECT t.transaction_id, t.created_at, t.status, t.total_amount, u.phone, u.address, u.username
          FROM transaction t
          JOIN user u ON t.user_id = u.user_id
          WHERE t.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $size = $_POST['size'];

    $check_query = "SELECT quantity FROM `order` WHERE transaction_id = ? AND product_id = ? AND size = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("iis", $transaction_id, $product_id, $size);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $row = $check_result->fetch_assoc();
        $new_quantity = $row['quantity'] + $quantity;

        $update_query = "UPDATE `order` SET quantity = ? WHERE transaction_id = ? AND product_id = ? AND size = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("iiis", $new_quantity, $transaction_id, $product_id, $size);
        $update_stmt->execute();
    } else {
        $insert_query = "INSERT INTO `order` (transaction_id, product_id, quantity, size) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iiis", $transaction_id, $product_id, $quantity, $size);
        $insert_stmt->execute();
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Hàng</title>
    <link rel="stylesheet" href="css/order.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
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

    <div class="container">
        <h1>Danh sách đơn hàng</h1>
        <table class="order-table">
            <thead>
                <tr>
                    <th>Mã đơn hàng</th>
                    <th>Tên người nhận</th>
                    <th>Số điện thoại</th>
                    <th>Địa chỉ nhận hàng</th>
                    <th>Trạng thái</th>
                    <th>Ngày đặt</th>
                    <th>Xem chi tiết</th>
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
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <a href="order_details.php?transaction_id=<?php echo $row['transaction_id']; ?>" class="details-link">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
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
