<?php
session_start();

// CHẶN TRUY CẬP TRÁI PHÉP: Chỉ Admin mới được truy cập
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: trangchu.php");
    exit();
}

require_once 'database.php';

$alert = "";
$edit_product = null;

// --- 1. CRUD SẢN PHẨM (THÊM, SỬA, XÓA) ---
// Lấy thông tin sản phẩm nếu đang bấm nút "Sửa"
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM products WHERE id = $eid");
    $edit_product = $res->fetch_assoc();
}

// Xử lý Lưu (Thêm mới hoặc Cập nhật)
if (isset($_POST['btn_save_product'])) {
    $prod_id = (int)$_POST['prod_id'];
    $name = trim($_POST['name']);
    $grade = $_POST['grade'];
    $series = trim($_POST['series']);
    $price = (int)$_POST['price'];
    $image_path = $_POST['old_image'];

    $target_dir = "images/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if (!empty($_FILES['image']['name'])) {
        $file_name = time() . "_" . basename($_FILES['image']['name']);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        }
    }

    if ($prod_id > 0) {
        // UPDATE (Sửa sản phẩm)
        $stmt = $conn->prepare("UPDATE products SET name=?, grade=?, series=?, price=?, image=? WHERE id=?");
        $stmt->bind_param("sssisi", $name, $grade, $series, $price, $image_path, $prod_id);
        $stmt->execute();
        $stmt->close();
        $alert = "Đã cập nhật mô hình '$name' thành công!";
    } else {
        // CREATE (Thêm mới sản phẩm)
        if (empty($image_path)) $image_path = "images/wallpaperflare.com_wallpaper.jpg";
        $stmt = $conn->prepare("INSERT INTO products (name, grade, series, price, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $name, $grade, $series, $price, $image_path);
        $stmt->execute();
        $stmt->close();
        $alert = "Đã thêm mô hình '$name' vào kho!";
    }
}

// DELETE (Xóa sản phẩm)
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM products WHERE id = $del_id");
    header("Location: admin.php");
    exit();
}

// --- 2. QUẢN LÝ DANH MỤC / MENU ---
if (isset($_POST['btn_add_cat'])) {
    $code = strtoupper(trim($_POST['cat_code']));
    $cname = trim($_POST['cat_name']);
    $stmt = $conn->prepare("INSERT INTO categories (code, name) VALUES (?, ?)");
    $stmt->bind_param("ss", $code, $cname);
    if ($stmt->execute()) {
        $alert = "Đã thêm danh mục '$cname' lên Menu!";
    }
    $stmt->close();
}

if (isset($_GET['del_cat'])) {
    $cid = (int)$_GET['del_cat'];
    $conn->query("DELETE FROM categories WHERE id = $cid");
    header("Location: admin.php");
    exit();
}

// --- 3. QUẢN LÝ NGƯỜI DÙNG & PHÂN QUYỀN (ÁP DỤNG TX05) ---
$edit_user = null;
if (isset($_GET['edit_user_id'])) {
    $euid = (int)$_GET['edit_user_id'];
    $stmtU = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmtU->bind_param("i", $euid);
    $stmtU->execute();
    $edit_user = $stmtU->get_result()->fetch_assoc();
    $stmtU->close();
}

// 3.1. Thêm mới hoặc Cập nhật tài khoản người dùng
if (isset($_POST['btn_save_user'])) {
    $uid = (int)($_POST['user_id'] ?? 0);
    $uname = trim($_POST['username'] ?? '');
    $uemail = trim($_POST['email'] ?? '');
    $urole = $_POST['role'] ?? 'user';
    $upass = $_POST['password'] ?? '';
    $uconfirm = $_POST['confirm_password'] ?? '';

    if ($uname === '' || !filter_var($uemail, FILTER_VALIDATE_EMAIL)) {
        $alert = "Lỗi: Tên đăng nhập không được trống và Email phải đúng định dạng!";
    } elseif (!in_array($urole, ['user', 'staff', 'admin'], true)) {
        $alert = "Lỗi: Vai trò không hợp lệ!";
    } else {
        if ($uid > 0) {
            // CẬP NHẬT TÀI KHOẢN (UPDATE)
            // Chặn Admin tự đổi vai trò tài khoản đang đăng nhập (Quy định TX05)
            if (isset($_SESSION['user_id']) && $uid === (int)$_SESSION['user_id']) {
                $urole = 'admin';
            }

            // Kiểm tra trùng username/email với tài khoản KHÁC
            $stmtDup = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ?");
            $stmtDup->bind_param("ssi", $uname, $uemail, $uid);
            $stmtDup->execute();
            if ($stmtDup->get_result()->fetch_assoc()) {
                $alert = "Lỗi: Tên đăng nhập hoặc Email đã thuộc về tài khoản khác!";
            } else {
                if ($upass !== '' || $uconfirm !== '') {
                    if (strlen($upass) < 6) {
                        $alert = "Lỗi: Mật khẩu mới phải có tối thiểu 6 ký tự!";
                    } elseif ($upass !== $uconfirm) {
                        $alert = "Lỗi: Mật khẩu nhập lại không khớp!";
                    } else {
                        $hash = password_hash($upass, PASSWORD_DEFAULT);
                        $stmtUpd = $conn->prepare("UPDATE users SET username=?, email=?, role=?, password=? WHERE id=?");
                        $stmtUpd->bind_param("ssssi", $uname, $uemail, $urole, $hash, $uid);
                        $stmtUpd->execute();
                        $stmtUpd->close();
                        $alert = "Đã cập nhật thông tin và mật khẩu mới cho '$uname'!";
                        $edit_user = null;
                    }
                } else {
                    // Để trống mật khẩu -> giữ nguyên mật khẩu cũ (Quy định TX05)
                    $stmtUpd = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
                    $stmtUpd->bind_param("sssi", $uname, $uemail, $urole, $uid);
                    $stmtUpd->execute();
                    $stmtUpd->close();
                    $alert = "Đã cập nhật thông tin tài khoản '$uname'!";
                    $edit_user = null;
                }
            }
            $stmtDup->close();
        } else {
            // ĐĂNG KÝ TÀI KHOẢN MỚI (INSERT)
            if (strlen($upass) < 6) {
                $alert = "Lỗi: Mật khẩu phải có ít nhất 6 ký tự!";
            } elseif ($upass !== $uconfirm) {
                $alert = "Lỗi: Hai mật khẩu không giống nhau!";
            } else {
                $stmtDup = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmtDup->bind_param("ss", $uname, $uemail);
                $stmtDup->execute();
                if ($stmtDup->get_result()->fetch_assoc()) {
                    $alert = "Lỗi: Tên đăng nhập hoặc Email đã tồn tại!";
                } else {
                    $hash = password_hash($upass, PASSWORD_DEFAULT);
                    $stmtIns = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmtIns->bind_param("ssss", $uname, $uemail, $hash, $urole);
                    $stmtIns->execute();
                    $stmtIns->close();
                    $alert = "Đã đăng ký tài khoản mới '$uname' thành công!";
                }
                $stmtDup->close();
            }
        }
    }
}

// 3.2. Đổi nhanh quyền trực tiếp trên bảng (giữ tương thích nút cũ của nhóm)
if (isset($_POST['btn_change_role'])) {
    $uid = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    if (isset($_SESSION['user_id']) && $uid === (int)$_SESSION['user_id']) {
        $alert = "Không thể tự thay đổi quyền của chính mình!";
    } else {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $uid);
        $stmt->execute();
        $stmt->close();
        $alert = "Đã cập nhật quyền cho người dùng #$uid!";
    }
}

if (isset($_GET['del_user'])) {
    $uid = (int)$_GET['del_user'];
    if (!isset($_SESSION['user_id']) || $uid !== (int)$_SESSION['user_id']) {
        $stmtDel = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmtDel->bind_param("i", $uid);
        $stmtDel->execute();
        $stmtDel->close();
    }
    header("Location: admin.php");
    exit();
}


// --- 4. CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG ---
if (isset($_POST['btn_update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    $stmt->close();
    $alert = "Đã cập nhật trạng thái đơn hàng #$order_id!";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xưởng Quản Trị (Admin) - GUNPLA HUB</title>
    <link href="https://googleapis.com" rel="stylesheet">
    <link rel="stylesheet" href="css/trangchu.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

    <?php include 'components/header.html'; ?>

    <main class="admin-container">
        <?php if ($alert): ?>
            <div class="alert-banner">✓ <?php echo htmlspecialchars($alert); ?></div>
        <?php endif; ?>

        <!-- KHU VỰC 1: CRUD SẢN PHẨM -->
        <div class="admin-grid">
            <div class="glass-panel">
                <h3><?php echo $edit_product ? '✏️ SỬA MÔ HÌNH #' . $edit_product['id'] : '+ THÊM MÔ HÌNH MỚI'; ?></h3>
                <form action="admin.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="prod_id" value="<?php echo $edit_product ? $edit_product['id'] : 0; ?>">
                    <input type="hidden" name="old_image" value="<?php echo $edit_product ? htmlspecialchars($edit_product['image']) : ''; ?>">

                    <input type="text" name="name" class="form-control" placeholder="Tên mô hình" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" required>
                    
                    <select name="grade" class="form-control" required>
                        <?php
                        $cats = $conn->query("SELECT * FROM categories ORDER BY id ASC");
                        while ($c = $cats->fetch_assoc()):
                            $selected = ($edit_product && $edit_product['grade'] == $c['code']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($c['code']); ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <input type="text" name="series" class="form-control" placeholder="Dòng phim (Series)" value="<?php echo $edit_product ? htmlspecialchars($edit_product['series']) : ''; ?>" required>
                    <input type="number" name="price" class="form-control" placeholder="Giá bán (VNĐ)" value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required>
                    
                    <label class="file-label">Chọn ảnh mô hình <?php echo $edit_product ? '(Bỏ trống nếu giữ ảnh cũ)' : ''; ?>:</label>
                    <input type="file" name="image" class="form-control" accept="image/*">

                    <button type="submit" name="btn_save_product" class="checkout-btn">
                        <?php echo $edit_product ? 'CẬP NHẬT MÔ HÌNH' : 'LƯU VÀO KHO HÀNG'; ?>
                    </button>

                    <?php if ($edit_product): ?>
                        <a href="admin.php" class="btn-cancel">✕ Hủy sửa, quay lại thêm mới</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="glass-panel table-responsive">
                <h3>DANH SÁCH MÔ HÌNH TRONG KHO</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Ảnh</th>
                            <th>Tên mô hình</th>
                            <th>Grade</th>
                            <th>Giá bán</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $prods = $conn->query("SELECT * FROM products ORDER BY id DESC");
                        while ($p = $prods->fetch_assoc()):
                        ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($p['image']); ?>" class="thumb-img" alt="Gundam"></td>
                            <td>
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                                <span class="series-sub"><?php echo htmlspecialchars($p['series']); ?></span>
                            </td>
                            <td><span class="badge" style="margin:0;"><?php echo htmlspecialchars($p['grade']); ?></span></td>
                            <td class="price-text"><?php echo number_format($p['price'], 0, ',', '.'); ?>₫</td>
                            <td>
                                <a href="admin.php?edit_id=<?php echo $p['id']; ?>" class="btn-edit">Sửa</a>
                                <a href="admin.php?delete_id=<?php echo $p['id']; ?>" class="btn-del" onclick="return confirm('Xóa mô hình này khỏi kho?')">Xóa</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- KHU VỰC QUẢN LÝ TÀI KHOẢN NGƯỜI DÙNG (ÁP DỤNG TX05) -->
<div class="admin-grid" style="margin-top: 25px;">
    <div class="glass-panel">
        <h3><?php echo $edit_user ? '✏️ CẬP NHẬT TÀI KHOẢN #' . $edit_user['id'] : '+ ĐĂNG KÝ TÀI KHOẢN MỚI'; ?></h3>
        <form action="admin.php" method="POST">
            <input type="hidden" name="user_id" value="<?php echo $edit_user ? (int)$edit_user['id'] : 0; ?>">

            <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập (Username)"
                   value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" required>

            <input type="email" name="email" class="form-control" placeholder="Địa chỉ Email"
                   value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" required>

            <?php $isSelf = ($edit_user && isset($_SESSION['user_id']) && (int)$edit_user['id'] === (int)$_SESSION['user_id']); ?>
            <select name="role" class="form-control" <?php echo $isSelf ? 'disabled' : ''; ?>>
                <option value="user" <?php echo ($edit_user && $edit_user['role'] === 'user') ? 'selected' : ''; ?>>Khách hàng (User)</option>
                <option value="staff" <?php echo ($edit_user && $edit_user['role'] === 'staff') ? 'selected' : ''; ?>>Nhân viên (Staff)</option>
                <option value="admin" <?php echo ($edit_user && $edit_user['role'] === 'admin') ? 'selected' : ''; ?>>Quản trị viên (Admin)</option>
            </select>
            <?php if ($isSelf): ?>
                <input type="hidden" name="role" value="admin">
            <?php endif; ?>

            <input type="password" name="password" class="form-control"
                   placeholder="<?php echo $edit_user ? 'Mật khẩu mới (để trống nếu không đổi)' : 'Mật khẩu (tối thiểu 6 ký tự)'; ?>"
                   <?php echo $edit_user ? '' : 'required'; ?>>

            <input type="password" name="confirm_password" class="form-control"
                   placeholder="Nhập lại mật khẩu"
                   <?php echo $edit_user ? '' : 'required'; ?>>

            <button type="submit" name="btn_save_user" class="checkout-btn">
                <?php echo $edit_user ? 'LƯU THÔNG TIN' : 'ĐĂNG KÝ TÀI KHOẢN'; ?>
            </button>

            <?php if ($edit_user): ?>
                <a href="admin.php" class="btn-cancel">✕ Hủy sửa, quay lại đăng ký mới</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="glass-panel table-responsive">
        <h3>DANH SÁCH TÀI KHOẢN NGƯỜI DÙNG</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên đăng nhập</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users_rs = $conn->query("SELECT id, username, email, role FROM users ORDER BY id ASC");
                while ($u = $users_rs->fetch_assoc()):
                ?>
                <tr>
                    <td>#<?php echo (int)$u['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['role']); ?></td>
                    <td>
                        <a href="admin.php?edit_user_id=<?php echo (int)$u['id']; ?>">Sửa</a>
                        <?php if (!isset($_SESSION['user_id']) || (int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                            | <a href="admin.php?del_user=<?php echo (int)$u['id']; ?>" onclick="return confirm('Xóa tài khoản này?');">Xóa</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

        <!-- KHU VỰC 2: QUẢN LÝ MENU (DANH MỤC) & QUẢN LÝ NGƯỜI DÙNG -->
        <div class="admin-two-cols">
            <!-- Quản lý Menu / Danh mục -->
            <div class="glass-panel table-responsive">
                <h3>QUẢN LÝ MENU / PHÂN CẤP</h3>
                <form action="admin.php" method="POST" style="display: flex; gap: 8px; margin-bottom: 16px;">
                    <input type="text" name="cat_code" class="form-control" style="margin:0; width: 35%;" placeholder="Mã (VD: SD)" required>
                    <input type="text" name="cat_name" class="form-control" style="margin:0;" placeholder="Tên hiển thị (VD: Super Deformed)" required>
                    <button type="submit" name="btn_add_cat" class="mini-add-btn" style="width: auto; padding: 0 14px;">+ Thêm</button>
                </form>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã Menu</th>
