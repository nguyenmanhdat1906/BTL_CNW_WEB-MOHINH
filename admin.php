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

// --- 3. QUẢN LÝ NGƯỜI DÙNG & PHÂN QUYỀN ---
if (isset($_POST['btn_change_role'])) {
    $uid = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $uid);
    $stmt->execute();
    $stmt->close();
    $alert = "Đã cập nhật quyền cho người dùng #$uid!";
}

if (isset($_GET['del_user'])) {
    $uid = (int)$_GET['del_user'];
    if ($uid != $_SESSION['user_id']) { // Không cho tự xóa chính mình
        $conn->query("DELETE FROM users WHERE id = $uid");
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
