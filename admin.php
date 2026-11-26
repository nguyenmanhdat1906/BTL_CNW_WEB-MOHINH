<?php
session_start();

// CHẶN TRUY CẬP TRÁI PHÉP: Chỉ tài khoản có role = 'admin' mới được ở lại
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: trangchu.php");
    exit();
}

require_once 'database.php';


$alert = "";

// 1. XỬ LÝ THÊM SẢN PHẨM MỚI (KÈM UPLOAD ẢNH)
if (isset($_POST['btn_add_product'])) {
    $name = trim($_POST['name']);
    $grade = $_POST['grade'];
    $series = trim($_POST['series']);
    $price = (int)$_POST['price'];

    $target_dir = "images/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $image_path = "images/wallpaperflare.com_wallpaper.jpg";
    if (!empty($_FILES['image']['name'])) {
        $file_name = time() . "_" . basename($_FILES['image']['name']);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        }
    }

    $stmt = $conn->prepare("INSERT INTO products (name, grade, series, price, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $name, $grade, $series, $price, $image_path);
    if ($stmt->execute()) {
        $alert = "Đã thêm mô hình '$name' vào kho thành công!";
    }
    $stmt->close();
}

// 2. XỬ LÝ XÓA SẢN PHẨM
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM products WHERE id = $del_id");
    header("Location: admin.php");
    exit();
}

// 3. XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
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

        <div class="admin-grid">
            <!-- Form thêm mô hình -->
            <div class="glass-panel">
                <h3>+ THÊM MÔ HÌNH MỚI</h3>
                <form action="admin.php" method="POST" enctype="multipart/form-data">
                    <input type="text" name="name" class="form-control" placeholder="Tên mô hình (VD: MG Freedom 2.0)" required>
                    
                    <select name="grade" class="form-control" required>
                        <option value="HG">HG (High Grade 1/144)</option>
                        <option value="RG">RG (Real Grade 1/144)</option>
                        <option value="MG" selected>MG (Master Grade 1/100)</option>
                        <option value="PG">PG (Perfect Grade 1/60)</option>
                    </select>

                    <input type="text" name="series" class="form-control" placeholder="Dòng phim (VD: Gundam SEED)" required>
                    <input type="number" name="price" class="form-control" placeholder="Giá bán (VNĐ)" required>
                    
                    <label class="file-label">Chọn ảnh mô hình:</label>
                    <input type="file" name="image" class="form-control" accept="image/*">

                    <button type="submit" name="btn_add_product" class="checkout-btn">LƯU VÀO KHO HÀNG</button>
                </form>
            </div>

            <!-- Bảng danh sách sản phẩm -->
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
                                <a href="admin.php?delete_id=<?php echo $p['id']; ?>" class="btn-del" onclick="return confirm('Xóa mô hình này khỏi kho?')">Xóa</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quản lý đơn hàng -->
        <div class="glass-panel table-responsive">
            <h3>QUẢN LÝ ĐƠN HÀNG PILOT</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã Đơn</th>
                        <th>Khách hàng</th>
                        <th>Chi tiết mô hình đặt</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $orders = $conn->query("SELECT * FROM orders ORDER BY id DESC");
                    if ($orders->num_rows > 0):
                        while ($o = $orders->fetch_assoc()):
                            $oid = $o['id'];
                            $items_res = $conn->query("SELECT * FROM order_items WHERE order_id = $oid");
                    ?>
                    <tr>
                        <td><strong>#<?php echo $oid; ?></strong><br><span class="series-sub"><?php echo $o['created_at']; ?></span></td>
                        <td>
                            <strong><?php echo htmlspecialchars($o['customer_name']); ?></strong> - <?php echo htmlspecialchars($o['phone']); ?><br>
                            <span class="series-sub">📍 <?php echo htmlspecialchars($o['address']); ?></span>
                        </td>
                        <td>
                            <?php while ($it = $items_res->fetch_assoc()): ?>
                                <div style="font-size: 12px; margin-bottom: 3px;">
                                    • <?php echo htmlspecialchars($it['product_name']); ?> <strong class="price-text">(x<?php echo $it['quantity']; ?>)</strong>
                                </div>
                            <?php endwhile; ?>
                        </td>
                        <td class="total-order-text">
                            <?php echo number_format($o['total_price'], 0, ',', '.'); ?>₫
                        </td>
                        <td>
                            <form action="admin.php" method="POST" class="status-form">
                                <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                <select name="status" class="form-control status-select">
                                    <option value="Chờ xác nhận" <?php if($o['status']=='Chờ xác nhận') echo 'selected'; ?>>Chờ xác nhận</option>
                                    <option value="Đang giao hàng" <?php if($o['status']=='Đang giao hàng') echo 'selected'; ?>>Đang giao hàng</option>
                                    <option value="Hoàn thành" <?php if($o['status']=='Hoàn thành') echo 'selected'; ?>>Hoàn thành</option>
                                </select>
                                <button type="submit" name="btn_update_status" class="mini-add-btn btn-save-status">Lưu</button>
                            </form>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                        echo "<tr><td colspan='5' style='text-align:center; color:#94a3b8;'>Chưa có đơn hàng nào.</td></tr>";
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
