<?php
session_start();
include('db.php'); // Kết nối với cơ sở dữ liệu

$error_message = ""; // Biến lưu trữ thông báo lỗi

// Kiểm tra khi người dùng gửi form đăng nhập
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Kiểm tra đăng nhập ở bảng user
    $stmt = $conn->prepare("SELECT * FROM user WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username); // "ss" là kiểu dữ liệu (string)
    $stmt->execute();
    $result = $stmt->get_result();

    // Nếu tìm thấy người dùng trong bảng user
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Kiểm tra trạng thái tài khoản
        if ($row['status'] != 'active') {
            $error_message = "Tài khoản chưa được kích hoạt!";
        } else {
            // Kiểm tra mật khẩu
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                // Chuyển hướng theo vai trò người dùng
                if ($_SESSION['role'] == 'admin') {
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    header('Location: index.php');
                    exit();
                }
            } else {
                $error_message = "Sai mật khẩu!";
            }
        }
    } else {
        // Kiểm tra đăng nhập ở bảng admin
        $stmt_admin = $conn->prepare("SELECT * FROM admin WHERE username = ? OR email = ?");
        $stmt_admin->bind_param("ss", $username, $username); // "ss" là kiểu dữ liệu (string)
        $stmt_admin->execute();
        $result_admin = $stmt_admin->get_result();

        // Nếu tìm thấy người dùng trong bảng admin
        if ($result_admin->num_rows > 0) {
            $row_admin = $result_admin->fetch_assoc();

            // Kiểm tra mật khẩu
            if (password_verify($password, $row_admin['password'])) {
                $_SESSION['admin_id'] = $row_admin['admin_id'];
                $_SESSION['username'] = $row_admin['username'];
                $_SESSION['role'] = $row_admin['role'];

                header('Location: admin_dashboard.php');
                exit();
            } else {
                $error_message = "Sai mật khẩu!";
            }
        } else {
            $error_message = "Sai tài khoản!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body background="img/br.jpg">
    <div class="login-container">
        <h2>Đăng nhập</h2>
        
        <!-- Hiển thị thông báo lỗi nếu có -->
        <?php if ($error_message != ""): ?>
            <div class="error-message" style="color: red; margin-bottom: 15px; font-weight: bold;">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label for="username">Tên đăng nhập hoặc email:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Mật khẩu:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" name="login">Đăng nhập</button>
        </form>
        <p><a href="register.php">Chưa có tài khoản? Đăng ký</a></p>
    </div>
</body>
</html>