<?php
session_start();
include('db.php'); // Kết nối CSDL

// Lọc theo từ khóa tìm kiếm
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
// Truy vấn danh mục từ bảng catalog
$sql_catalog = "SELECT * FROM catalog";
$result_catalog = $conn->query($sql_catalog);

// Nếu yêu cầu AJAX, trả về danh sách sản phẩm
if (isset($_GET['ajax']) && isset($_GET['catalog_id'])) {
    $catalog_id = intval($_GET['catalog_id']);
    $sql_products = $catalog_id > 0 ? "SELECT * FROM product WHERE catalog_id = ?" : "SELECT * FROM product";
    $stmt = $conn->prepare($sql_products);
    if ($catalog_id > 0) {
        $stmt->bind_param("i", $catalog_id);
    }
    $stmt->execute();
    $result_products = $stmt->get_result();

    $products = [];
    while ($row = $result_products->fetch_assoc()) {
        $products[] = $row;
    }
    echo json_encode($products); // Trả về dữ liệu JSON
    exit;
}
$items_per_page = 10; // Hiển thị 4 sản phẩm mỗi hàng ngang, 3 hàng ngang (4x3)

// Xác định danh mục hiện tại
$catalog_id = isset($_GET['catalog_id']) && is_numeric($_GET['catalog_id']) ? intval($_GET['catalog_id']) : 0;

// Xác định trang hiện tại
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) {
    $page = 1;
}

// Tính toán offset cho truy vấn SQL
$offset = ($page - 1) * $items_per_page;

// Lấy tổng số sản phẩm
$total_items_query = $catalog_id > 0 
    ? "SELECT COUNT(*) as total FROM product WHERE catalog_id = ?" 
    : "SELECT COUNT(*) as total FROM product";
$stmt = $conn->prepare($total_items_query);
if ($catalog_id > 0) {
    $stmt->bind_param("i", $catalog_id);
}
$stmt->execute();
$total_items_result = $stmt->get_result();
$total_items = $total_items_result->fetch_assoc()['total'];

// Tính tổng số trang
$total_pages = ceil($total_items / $items_per_page);

// Lấy sản phẩm của trang hiện tại
$product_query = $catalog_id > 0 
    ? "SELECT * FROM product WHERE catalog_id = ? LIMIT ? OFFSET ?"
    : "SELECT * FROM product LIMIT ? OFFSET ?";
$stmt = $conn->prepare($product_query);
if ($catalog_id > 0) {
    $stmt->bind_param("iii", $catalog_id, $items_per_page, $offset);
} else {
    $stmt->bind_param("ii", $items_per_page, $offset);
}
$stmt->execute();
$product_result = $stmt->get_result();

// Lấy danh mục sản phẩm
$sql_catalog = "SELECT * FROM catalog";
$result_catalog = $conn->query($sql_catalog);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh mục sản phẩm</title>
    <link rel="stylesheet" href="css/catalog.css">
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

    <!-- Sidebar Danh mục -->
    <div class="catalog">
        <div class="sidebar">
            <h2>Danh mục sản phẩm</h2>
            <ul>
                <li><a href="catalog.php?catalog_id=0">Tất cả sản phẩm</a></li>
                <?php while ($cat = $result_catalog->fetch_assoc()) { ?>
                    <li><a href="catalog.php?catalog_id=<?php echo $cat['catalog_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                <?php } ?>
            </ul>
        </div>

        <!-- Nội dung chính -->
        <div class="main-content">
            <h1>Danh sách sản phẩm</h1>
            <div class="product-list">
                <?php while ($row = $product_result->fetch_assoc()) { ?>
                    <div class="product">
                        <a href="product_detail.php?product_id=<?php echo $row['product_id']; ?>">
                            <img src="<?php echo $row['image'] ?: 'img/default-image.jpg'; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                        </a>
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p class="price"><?php echo number_format($row['price'], 0, ',', '.'); ?> VND</p>
                        <a href="product_detail.php?product_id=<?php echo $row['product_id']; ?>" class="order-button">Đặt hàng</a>
                    </div>
                <?php } ?>
            </div>

            <!-- Phân trang -->
            <div class="pagination">
                <?php if ($total_pages > 1): ?>
                    <span>Trang:</span>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="catalog.php?catalog_id=<?php echo $catalog_id; ?>&page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
        </div>
    </footer>
</body>
</html>

                        <script>
                            // AJAX để tải sản phẩm theo danh mục
                            function loadProducts(catalogId = 0) {
                                const productList = document.getElementById('product-list');
                                productList.innerHTML = '<p>Đang tải sản phẩm...</p>'; // Hiển thị thông báo đang tải
                    
                                fetch(`catalog.php?ajax=1&catalog_id=${catalogId}`)
                                    .then(response => response.json())
                                    .then(products => {
                                        let html = '';
                                        if (products.length > 0) {
                                            products.forEach(product => {
                                                html += `
                                                    <div class="product">
                                                        <a href="product_detail.php?product_id=${product.product_id}">
                                                            <img src="${product.image ? product.image : 'img/default-image.jpg'}" alt="${product.name}">
                                                        </a>
                                                        <h3>${product.name}</h3>
                                                        <p>${Number(product.price).toLocaleString()} VND</p>
                                                        <a href="product_detail.php?product_id=${product.product_id}" class="order-button">Đặt hàng</a>
                                                    </div>
                                                `;
                                            });
                                        } else {
                                            html = '<p>Không có sản phẩm nào trong danh mục này.</p>';
                                        }
                                        productList.innerHTML = html;
                                    })
                                    .catch(err => {
                                        productList.innerHTML = '<p>Đã xảy ra lỗi khi tải sản phẩm.</p>';
                                        console.error(err);
                                    });
                            }
                    
                            // Tải toàn bộ sản phẩm khi trang được load
                            window.onload = function() {
                                loadProducts(); // Tải tất cả sản phẩm
                            };
                    
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
