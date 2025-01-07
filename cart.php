<?php
session_start();
include('db.php');

// Kiểm tra nếu chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
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
$user_id = $_SESSION['user_id'];

// Lấy transaction_id
$stmt = $conn->prepare("SELECT transaction_id FROM transaction WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Giỏ hàng của bạn trống.");
}
$transaction = $result->fetch_assoc();
$transaction_id = $transaction['transaction_id'];

// Lấy sản phẩm trong giỏ hàng
$stmt = $conn->prepare("
    SELECT o.order_id, o.product_id, o.quantity, o.size, o.total_amount, 
           p.name, p.image, p.price 
    FROM `order` o
    JOIN product p ON o.product_id = p.product_id
    WHERE o.transaction_id = ?
");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng của bạn</title>
    <link rel="stylesheet" href="css/cart.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
<div class="cart-container">
    <h1>GIỎ HÀNG CỦA BẠN</h1>
    <table class="cart-table">
        <thead>
        <tr>
            <th>Hình ảnh</th>
            <th>Tên sản phẩm</th>
            <th>Mã hàng</th>
            <th>Số lượng</th>
            <th>Đơn giá</th>
            <th>Tổng cộng</th>
            <th>Hành động</th>
        </tr>
        </thead>
        <tbody id="cart-body">
        <?php
        if ($result && $result->num_rows > 0) {
            $total = 0;
            while ($row = $result->fetch_assoc()) {
                $subtotal = $row['quantity'] * $row['price'];
                $total += $subtotal;
                echo "<tr data-product-id='{$row['product_id']}'>
                        <td><img src='{$row['image']}' alt='{$row['name']}' width='100'></td>
                        <td>{$row['name']}</td>
                        <td>MSN{$row['product_id']}</td>
                        <td>
                            <div class='quantity-control'>
                                <button onclick='changeQuantity({$row['product_id']}, -1)'>-</button>
                                <input type='number' min='1' value='{$row['quantity']}' id='quantity-{$row['product_id']}' readonly>
                                <button onclick='changeQuantity({$row['product_id']}, 1)'>+</button>
                            </div>
                        </td>
                        <td>" . number_format($row['price'], 0, ',', '.') . "₫</td>
                        <td id='subtotal-{$row['product_id']}'>" . number_format($subtotal, 0, ',', '.') . "₫</td>
                        <td>
                            <div class='action-buttons'>
                                <button class='update-btn' onclick='updateQuantity({$row['product_id']})'>Cập nhật</button>
                                <button class='remove-btn' onclick='removeItem({$row['product_id']})'>Xóa</button>
                            </div>
    </td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>Giỏ hàng của bạn đang trống.</td></tr>";
        }
        ?>
        </tbody>
    </table>
    <div class="cart-summary">
        <h2>Tổng: <?= isset($total) ? number_format($total, 0, ',', '.') : '0' ?>₫</h2>
        <a href="checkout.php"><button class="checkout-btn">THANH TOÁN</button></a>
    </div>
</div>
<script>
    // Thay đổi số lượng sản phẩm
    function changeQuantity(productId, change) {
        const quantityInput = document.getElementById(`quantity-${productId}`);
        let newQuantity = parseInt(quantityInput.value) + change;
        if (newQuantity < 1) newQuantity = 1;
        quantityInput.value = newQuantity;
    }

    // Gửi yêu cầu AJAX để cập nhật số lượng sản phẩm trong cơ sở dữ liệu
    function updateQuantity(productId) {
        const quantityInput = document.getElementById(`quantity-${productId}`);
        const quantity = quantityInput.value;

        fetch(`update_cart.php?product_id=${productId}&quantity=${quantity}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload(); // Reload lại giỏ hàng
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi. Vui lòng thử lại!');
            });
    }

    // Gửi yêu cầu AJAX để xóa sản phẩm khỏi giỏ hàng
    function removeItem(productId) {
        if (confirm("Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng không?")) {
            fetch(`remove_cart.php?product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Đã xảy ra lỗi. Vui lòng thử lại!');
                });
        }
    }

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
</body>
</html>
