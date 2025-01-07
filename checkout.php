<?php
session_start();
include('db.php');

// Kiểm tra nếu chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy transaction_id và thông tin tổng tiền
$stmt = $conn->prepare("SELECT transaction_id, total_amount FROM transaction WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Tạo transaction mới nếu không tồn tại
    $stmtNewTransaction = $conn->prepare("INSERT INTO transaction (user_id, status, total_amount, created_at) VALUES (?, 'pending', 0, NOW())");
    $stmtNewTransaction->bind_param("i", $user_id);
    $stmtNewTransaction->execute();
    $transaction_id = $stmtNewTransaction->insert_id;
    $total_amount = 0;
} else {
    $transaction = $result->fetch_assoc();
    $transaction_id = $transaction['transaction_id'];
    $total_amount = $transaction['total_amount'];
}

// Lấy thông tin người dùng
$queryUser = "SELECT phone, address FROM user WHERE user_id = ?";
$stmtUser = $conn->prepare($queryUser);
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();

if ($resultUser->num_rows > 0) {
    $userInfo = $resultUser->fetch_assoc();
} else {
    $userInfo = ['phone' => '', 'address' => ''];
}

// Lấy sản phẩm trong giỏ hàng
$queryCart = "
    SELECT o.order_id, o.quantity, p.name, p.image, p.price, p.product_id 
    FROM `order` o 
    JOIN product p ON o.product_id = p.product_id 
    WHERE o.transaction_id = ?
";
$stmtCart = $conn->prepare($queryCart);
$stmtCart->bind_param("i", $transaction_id);
$stmtCart->execute();
$resultCart = $stmtCart->get_result();

if ($resultCart->num_rows === 0) {
    echo "<script>alert('Giỏ hàng của bạn hiện đang trống. Vui lòng thêm sản phẩm!'); window.location.href = 'cart.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy thông tin từ form
    $shipping_address = trim($_POST['address']);
    $shipping_fee = (isset($_POST['shipping']) && $_POST['shipping'] === 'express') ? 25000 : 0;

    if (empty($shipping_address)) {
        echo "<script>alert('Vui lòng cung cấp địa chỉ giao hàng!'); window.history.back();</script>";
        exit();
    }

    // Cập nhật thông tin giao hàng và tổng tiền trong `transaction`
    $stmt = $conn->prepare("UPDATE transaction SET shipping_fee = ?, total_amount = total_amount + ?, status = 'pending' WHERE transaction_id = ?");
    $stmt->bind_param("dii", $shipping_fee, $shipping_fee, $transaction_id);
    if (!$stmt->execute()) {
        die("Lỗi khi cập nhật giao dịch: " . $stmt->error);
    }

    // Cập nhật địa chỉ giao hàng trong bảng `order`
    $stmt = $conn->prepare("UPDATE `order` SET shipping_address = ? WHERE transaction_id = ?");
    $stmt->bind_param("si", $shipping_address, $transaction_id);
    if (!$stmt->execute()) {
        die("Lỗi khi cập nhật địa chỉ giao hàng: " . $stmt->error);
    }

    // Chuyển hướng đến trang đơn hàng
    echo "<script>alert('Đặt hàng thành công!'); window.location.href = 'order.php';</script>";
    exit();
}

?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Thanh Toán</title>
        <link rel="stylesheet" href="css/checkout.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    </head>
    <body>
        <!-- Header -->
        <header>
            <div class="container">
                <nav>
                    <div class="left-nav">
                        <a href="index.php"><img src="img/logo1.png" alt="Logo" class="logo"></a>
                        <a href="index.php">🏠 Trang chủ</a>
                        <a href="cart.php">🛒 Giỏ hàng</a>
                        <a href="catalog.php">📂 Danh mục sản phẩm</a>
                    </div>
                    <form class="search-bar" action="index.php" method="GET">
                        <input type="text" name="search" placeholder="Tìm kiếm sản phẩm...">
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
    <div class="container">
            <h1>Thanh Toán</h1>
            <form method="POST">
        <div class="checkout-container">
            <!-- Nhập thông tin khách hàng -->
            <div class="checkout-section">
                <h3>Nhập thông tin khách hàng</h3>
                <input type="text" name="phone" placeholder="Số điện thoại" value="<?= htmlspecialchars($userInfo['phone'] ?: '') ?>" required>
                <input type="text" name="address" placeholder="Địa chỉ giao hàng" value="<?= htmlspecialchars($userInfo['address'] ?: '') ?>" required>
            </div>

            <!-- Phương thức giao hàng -->
            <div class="checkout-section">
                <h3>Phương thức giao hàng</h3>
                <div class="method-options">
                    <label>
                        <input type="radio" name="shipping" value="free" checked> Miễn phí giao hàng - 0₫
                    </label>
                    <label>
                        <input type="radio" name="shipping" value="express"> Ship nhanh - 25,000₫
                    </label>
                </div>
            </div>

            <!-- Bảng sản phẩm -->
            <table class="product-table">
                <thead>
                    <tr>
                        <th>Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Giá</th>
                        <th>Tổng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    while ($row = $resultCart->fetch_assoc()) {
                        $subtotal = $row['quantity'] * $row['price'];
                        $total += $subtotal;
                        echo "<tr>
                                <td><img src='{$row['image']}' alt='{$row['name']}' width='50'></td>
                                <td>{$row['name']}</td>
                                <td>{$row['quantity']}</td>
                                <td>" . number_format($row['price'], 0, ',', '.') . "₫</td>
                                <td>" . number_format($subtotal, 0, ',', '.') . "₫</td>
                            </tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- Tổng kết -->
            <div class="checkout-section">
                <div class="summary">
                    <div class="summary-row">
                        <span>Thành tiền:</span>
                        <span><?= number_format($total, 0, ',', '.') ?>₫</span>
                    </div>
                    <div class="summary-row">
                        <span>Phí giao hàng:</span>
                        <span id="shipping-fee">0₫</span>
                    </div>
                    <div class="summary-row">
                        <span>Tổng cộng:</span>
                        <span id="total-amount"><?= number_format($total, 0, ',', '.') ?>₫</span>
                    </div>
                </div>
                <button type="submit" class="checkout-btn">MUA HÀNG</button>
            </div>
        </div>
    </form>

        </div>
    <script>
        // Cập nhật phí giao hàng và tổng tiền
        const shippingRadios = document.querySelectorAll('input[name="shipping"]');
        const shippingFeeElement = document.getElementById('shipping-fee');
        const totalAmountElement = document.getElementById('total-amount');
        let total = <?= $total ?>;

        shippingRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                let shippingFee = 0;
                if (radio.value === 'express') shippingFee = 25000;

                shippingFeeElement.textContent = ${shippingFee.toLocaleString()}₫;
                totalAmountElement.textContent = ${(total + shippingFee).toLocaleString()}₫;
            });
        });

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
    <script>
    // Cập nhật phí giao hàng và tổng tiền
    const shippingRadios = document.querySelectorAll('input[name="shipping"]');
    const shippingFeeElement = document.getElementById('shipping-fee');
    const totalAmountElement = document.getElementById('total-amount');
    const baseAmountElement = document.getElementById('base-amount');

    let baseAmount = parseInt(<?= $total ?>);

    shippingRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            let shippingFee = radio.value === 'express' ? 25000 : 0;

            shippingFeeElement.textContent = shippingFee.toLocaleString('vi-VN') + '₫';
            totalAmountElement.textContent = (baseAmount + shippingFee).toLocaleString('vi-VN') + '₫';
        });
    });
</script>
    <!-- Footer -->
    <footer>
            <div class="footer-container">
                <a href="#"><img src="img/logo1.png" alt="Logo" class="logo"></a>
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