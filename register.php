<?php
include('db.php');

$error_message = ""; // Biến lưu trữ thông báo lỗi

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Kiểm tra nếu mật khẩu và xác nhận mật khẩu trùng khớp
    if ($password !== $confirm_password) {
        $error_message = "Mật khẩu không khớp!";
    } else {
        // Kiểm tra nếu tên đăng nhập đã tồn tại trong cơ sở dữ liệu
        $stmt = $conn->prepare("SELECT * FROM user WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Nếu tên đăng nhập hoặc email đã tồn tại
        if ($result->num_rows > 0) {
            $error_message = "Tên đăng nhập hoặc email đã tồn tại!";
        } else {
            // Mã hóa mật khẩu trước khi lưu vào cơ sở dữ liệu
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

            // Sử dụng prepared statement để bảo mật SQL
            $stmt = $conn->prepare("INSERT INTO user (username, email, phone, address, password, role, status, created_at, updated_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $role = 'customer'; // Mặc định là customer
            $status = 'active'; // Mặc định tài khoản kích hoạt
            $stmt->bind_param("sssssss", $username, $email, $phone, $address, $password_hashed, $role, $status);

            // Thực thi câu lệnh
            if ($stmt->execute()) {
                header('Location: login.php');
                exit();
            } else {
                $error_message = "Lỗi: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body background="img/br.jpg">
    <div class="register-container">
        <h2>Đăng ký</h2>
        
        <!-- Hiển thị thông báo lỗi nếu có -->
        <?php if ($error_message != ""): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="username">Tên đăng nhập:</label>
            <input type="text" id="username" name="username" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="phone">Số điện thoại:</label>
            <input type="text" id="phone" name="phone" required>

            <label for="address">Địa chỉ:</label>
            <input type="text" id="address" name="address" required>

            <label for="password">Mật khẩu:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Xác nhận mật khẩu:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit" name="register">Đăng ký</button>
        </form>
        <p><a href="login.php">Đã có tài khoản? Đăng nhập</a></p>
    </div>
</body>
</html>
