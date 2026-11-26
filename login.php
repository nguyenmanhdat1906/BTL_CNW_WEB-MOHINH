<?php
session_start();

// Kết nối tới MySQL Server và tự động tạo Database nếu chưa có
$conn = new mysqli("localhost", "root", "");
if ($conn->connect_error) {
    die("Kết nối MySQL thất bại: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS web_mohinh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db("web_mohinh");
$conn->set_charset("utf8mb4");

// Tự động tạo bảng users nếu chưa có
$sql_table = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql_table);


$message = "";
$msg_type = ""; // 'success' hoặc 'error'

// XỬ LÝ ĐĂNG KÝ
if (isset($_POST['btn_register'])) {
    $username = trim($_POST['reg_username']);
    $email = trim($_POST['reg_email']);
    $password = $_POST['reg_password'];
    $confirm_password = $_POST['reg_confirm_password'];

    if ($password !== $confirm_password) {
        $message = "Mật khẩu nhập lại không khớp!";
        $msg_type = "error";
    } else {
        // Kiểm tra xem username hoặc email đã tồn tại chưa
        $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Tên tài khoản hoặc Email đã được sử dụng!";
            $msg_type = "error";
        } else {
            // Mã hóa mật khẩu an toàn và lưu vào DB
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $message = "Đăng ký Pilot thành công! Hãy đăng nhập ngay.";
                $msg_type = "success";
            } else {
                $message = "Có lỗi xảy ra, vui lòng thử lại!";
                $msg_type = "error";
            }
            $stmt->close();
        }
        $check->close();
    }
}

// XỬ LÝ ĐĂNG NHẬP
if (isset($_POST['btn_login'])) {
    $login_input = trim($_POST['username']);
    $password = $_POST['password'];

    // Lấy thêm cột role
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role']; // Lưu quyền vào Session

            // Nếu là admin thì chuyển thẳng vào trang quản trị, khách thì về trang chủ
            if ($row['role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: trangchu.php");
            }
            exit();
        } else {
            $message = "Sai mật khẩu! Vui lòng kiểm tra lại.";
            $msg_type = "error";
        }
    } else {
        $message = "Tài khoản không tồn tại!";
        $msg_type = "error";
    }
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập & Đăng ký - Gundam Store</title>
    <link href="https://googleapis.com" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="css/login.css">
    <style>
        /* CSS bổ sung cho hộp thông báo lỗi/thành công */
        .alert-box {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 13px;
            text-align: center;
            font-weight: 500;
        }
        .alert-error {
            background: rgba(230, 57, 70, 0.25);
            border: 1px solid #e63946;
            color: #ffccd5;
        }
        .alert-success {
            background: rgba(0, 210, 255, 0.25);
            border: 1px solid #00d2ff;
            color: #caf0f8;
        }
        /* Định dạng ô mật khẩu có icon con mắt */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 45px; /* Chừa khoảng trống bên phải cho con mắt */
        }

        .toggle-eye {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            user-select: none;
        }

        .toggle-eye:hover {
            color: #00d2ff;
        }

    </style>
</head>
<body>

    <div class="glass-container">
        <div class="tab-header">
            <button type="button" id="btn-tab-login" class="tab-btn active" onclick="switchTab('login')">ĐĂNG NHẬP</button>
            <button type="button" id="btn-tab-register" class="tab-btn" onclick="switchTab('register')">ĐĂNG KÝ</button>
        </div>

        <!-- Hiển thị thông báo từ PHP -->
        <?php if (!empty($message)): ?>
            <div class="alert-box alert-<?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- FORM ĐĂNG NHẬP -->
        <form id="login-form" class="auth-form active" action="login.php" method="POST">
            <h2>PILOT LOGIN</h2>
            <p class="subtitle">Chào mừng trở lại xưởng lắp ráp Gunpla</p>

            <div class="input-group">
                <input type="text" name="username" placeholder="Tên đăng nhập hoặc Email" required>
            </div>

            <div class="input-group password-wrapper">
                <input type="password" id="login-pass" name="password" placeholder="Mật khẩu" required>
                <span class="toggle-eye" onclick="togglePassword('login-pass', this)" title="Hiện/Ẩn mật khẩu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </span>
            </div>

            <button type="submit" name="btn_login" class="submit-btn">KHỞI ĐỘNG (LOGIN)</button>

            <div class="form-footer">
                <a href="#">Quên mật khẩu?</a>
                <a href="trangchu.php">← Về trang chủ</a>
            </div>
        </form>

        <!-- FORM ĐĂNG KÝ -->
        <form id="register-form" class="auth-form" action="login.php" method="POST">
            <h2>NEW PILOT</h2>
            <p class="subtitle">Tạo tài khoản để sưu tầm mô hình</p>

            <div class="input-group">
                <input type="text" name="reg_username" placeholder="Tên tài khoản" required>
            </div>

            <div class="input-group">
                <input type="email" name="reg_email" placeholder="Địa chỉ Email" required>
            </div>

            <div class="input-group password-wrapper">
                <input type="password" id="reg-pass" name="reg_password" placeholder="Mật khẩu" required>
                <span class="toggle-eye" onclick="togglePassword('reg-pass', this)" title="Hiện/Ẩn mật khẩu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </span>
            </div>

            <div class="input-group password-wrapper">
                <input type="password" id="reg-confirm-pass" name="reg_confirm_password" placeholder="Nhập lại mật khẩu" required>
                <span class="toggle-eye" onclick="togglePassword('reg-confirm-pass', this)" title="Hiện/Ẩn mật khẩu">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </span>
            </div>


            <button type="submit" name="btn_register" class="submit-btn">ĐĂNG KÝ NGAY</button>
            
            <div class="form-footer">
                <a href="trangchu.php">← Về trang chủ</a>
            </div>
        </form>
    </div>

    <script>
        function switchTab(tab) {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            const btnLogin = document.getElementById('btn-tab-login');
            const btnRegister = document.getElementById('btn-tab-register');

            if (tab === 'login') {
                loginForm.classList.add('active');
                registerForm.classList.remove('active');
                btnLogin.classList.add('active');
                btnRegister.classList.remove('active');
            } else {
                registerForm.classList.add('active');
                loginForm.classList.remove('active');
                btnRegister.classList.add('active');
                btnLogin.classList.remove('active');
            }
        }
        function togglePassword(inputId, iconSpan) {
            const input = document.getElementById(inputId);
            const eyeOpen = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
            const eyeSlash = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

            if (input.type === "password") {
                input.type = "text";
                iconSpan.innerHTML = eyeSlash;
                iconSpan.style.color = "#00d2ff";
            } else {
                input.type = "password";
                iconSpan.innerHTML = eyeOpen;
                iconSpan.style.color = "#6c757d";
            }
        }

    </script>
</body>
</html>
