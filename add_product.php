<?php
session_start();
include('db.php'); // Kết nối cơ sở dữ liệu

// Kiểm tra quyền Admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Lấy danh sách danh mục từ bảng catalog
$sql_catalogs = "SELECT catalog_id, name FROM catalog";
$result_catalogs = $conn->query($sql_catalogs);
if (!$result_catalogs) {
    die("Lỗi truy vấn danh mục: " . $conn->error);
}

// Xử lý khi form được gửi
if (isset($_POST['submit'])) {
    // Lấy dữ liệu từ form
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];  // Kiểm tra xem giá trị này có đúng không
    $catalog_id = $_POST['catalog_id']; // Lấy catalog_id từ form

    // Kiểm tra dữ liệu đầu vào (nếu cần)
    if (empty($name) || empty($description) || empty($price) || empty($quantity) || empty($catalog_id)) {
        echo "<p class='error'>Vui lòng điền đầy đủ thông tin sản phẩm.</p>";
    } else {
        // Kiểm tra và xử lý ảnh sản phẩm
        $image = "";
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $imageTmpName = $_FILES['image']['tmp_name'];
            $imageName = basename($_FILES['image']['name']);
            $imagePath = "uploads/" . $imageName; // Thư mục lưu ảnh sản phẩm

            // Kiểm tra nếu thư mục "uploads" chưa tồn tại thì tạo
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }

            // Kiểm tra loại ảnh và kích thước (ví dụ: jpg, png, jpeg, max 2MB)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $fileType = mime_content_type($imageTmpName);

            if (!in_array($fileType, $allowedTypes)) {
                echo "<p class='error'>Vui lòng chọn tệp ảnh hợp lệ (JPEG, PNG).</p>";
                exit();
            }

            if ($_FILES['image']['size'] > 2 * 1024 * 1024) { // 2MB
                echo "<p class='error'>Ảnh quá lớn. Vui lòng chọn ảnh dưới 2MB.</p>";
                exit();
            }

            // Di chuyển ảnh từ tạm thời đến thư mục "uploads"
            if (move_uploaded_file($imageTmpName, $imagePath)) {
                $image = $imagePath; // Lưu đường dẫn ảnh vào cơ sở dữ liệu
            }
        }

        // Thêm sản phẩm vào cơ sở dữ liệu
        $stmt = $conn->prepare("INSERT INTO product (catalog_id, name, description, price, quantity, image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $status = 'available'; // Mặc định trạng thái là "available"
        $stmt->bind_param("issdiss", $catalog_id, $name, $description, $price, $quantity, $image, $status); // "issdiss" là kiểu dữ liệu (int, string, string, decimal, int, string, string)

        if ($stmt->execute()) {
            echo "<p class='success'>Sản phẩm đã được thêm thành công!</p>";
        } else {
            echo "<p class='error'>Có lỗi xảy ra khi thêm sản phẩm: " . $stmt->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sản phẩm</title>
    <link rel="stylesheet" href="css/add_product.css">
</head>
<body>
    <div class="container">
        <h1>Thêm sản phẩm</h1>
        <form method="POST" enctype="multipart/form-data">
            <label for="catalog_id">Danh mục:</label>
            <select id="catalog_id" name="catalog_id" required>
                <option value="">Chọn danh mục</option>
                <?php while ($row = $result_catalogs->fetch_assoc()) { ?>
                    <option value="<?php echo $row['catalog_id']; ?>"><?php echo $row['name']; ?></option>
                <?php } ?>
            </select>

            <label for="name">Tên sản phẩm:</label>
            <input type="text" id="name" name="name" required>

            <label for="description">Mô tả sản phẩm:</label>
            <textarea id="description" name="description" required></textarea>

            <label for="price">Giá sản phẩm (VND):</label>
            <input type="number" id="price" name="price" step="0.01" required>

            <label for="quantity">Số lượng:</label>
            <input type="number" id="quantity" name="quantity" required>

            <label for="image">Ảnh sản phẩm:</label>
            <input type="file" id="image" name="image" accept="image/*">

            <input type="submit" name="submit" value="Thêm sản phẩm">
        </form>

        <a href="admin_dashboard.php" class="back-button">Quay lại</a>
    </div>
</body>
</html>
