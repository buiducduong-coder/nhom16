<?php
session_start();
include('db.php');

// Số sản phẩm trên mỗi trang
$items_per_page = 9; // Hiển thị 3 dòng, mỗi dòng 3 sản phẩm

// Xác định trang hiện tại
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) {
    $page = 1;
}
$items_per_page = 8;
// Tính toán offset cho truy vấn SQL
$offset = ($page - 1) * $items_per_page;

// Lọc theo từ khóa tìm kiếm
$search = '';
$where_clause = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $where_clause = "WHERE name LIKE ?";
}

// Lấy tổng số sản phẩm
$total_items_query = "SELECT COUNT(*) as total FROM product $where_clause";
if ($where_clause) {
    $stmt = $conn->prepare($total_items_query);
    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($total_items_query);
}
$total_items = $result->fetch_assoc()['total'];

// Tính tổng số trang
$total_pages = ceil($total_items / $items_per_page);

// Lấy danh sách sản phẩm cho trang hiện tại
$product_query = "SELECT * FROM product $where_clause LIMIT ? OFFSET ?";
if ($where_clause) {
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("sii", $searchTerm, $items_per_page, $offset);
    $stmt->execute();
    $product_result = $stmt->get_result();
} else {
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("ii", $items_per_page, $offset);
    $stmt->execute();
    $product_result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DUMI Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header: Thanh menu và tìm kiếm -->
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
                        <div class="dropdown">
                            <button class="dropdown-toggle">
                                <img src="<?php echo htmlspecialchars($_SESSION['profile_img'] ?? 'img/default-avatars.jpg'); ?>" alt="Avatar" class="profile-img">
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

    <!-- Banner -->
    <div class="banner">
        <img src="img/banner2.png" alt="">
        <div class="banner-overlay">
            <h1>Chào mừng đến với DUMI Shop</h1>
            <p>Khám phá các sản phẩm mới nhất với giá hấp dẫn</p>
            <a href="#products" class="cta-button">Mua ngay</a>
        </div>
    </div>

    <!-- Danh sách sản phẩm -->
    <div class="products" id="products">
        <h2>Sản phẩm nổi bật</h2>
        <div class="product-list">
            <?php if ($product_result->num_rows > 0): ?>
                <?php while ($row = $product_result->fetch_assoc()) { ?>
                    <div class="product">
                        <?php 
                            // Kiểm tra ảnh tồn tại
                            $imagePath = $row['image']; 
                            if (!empty($imagePath) && file_exists($imagePath)) { 
                        ?>
                            <a href="product_detail.php?product_id=<?php echo $row['product_id']; ?>"><img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" width="100"></a>
                        <?php } else { ?>
                            <img src="img/default-image.jpg" alt="Ảnh mặc định" width="100">
                        <?php } ?>
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p class="price"><?php echo number_format($row['price'], 0, ',', '.') . ' VND'; ?></p>
                        <a href="product_detail.php?product_id=<?php echo $row['product_id']; ?>" class="order-button">Đặt hàng</a>
                    </div>
                <?php } ?>
            <?php else: ?>
                <p>Không có sản phẩm nào phù hợp với tìm kiếm của bạn.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Phân trang -->
    <div class="pagination">
        <?php if ($total_pages > 1): ?>
            <span>Trang: </span>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="index.php?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                   class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
        <?php endif; ?>
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
