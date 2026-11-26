<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đơn hàng Pilot - GUNPLA HUB</title>
    <link href="https://googleapis.com" rel="stylesheet">
    <link rel="stylesheet" href="css/trangchu.css">
    <link rel="stylesheet" href="css/donhang.css">
</head>
<body>

    <?php include 'components/header.html'; ?>

    <main class="order-history-container">
        <div class="glass-panel">
            <h2 class="history-title">
                LỊCH SỬ ĐƠN HÀNG CỦA PILOT: <?php echo htmlspecialchars(strtoupper($username)); ?>
            </h2>

            <?php
            $stmt = $conn->prepare("SELECT * FROM orders WHERE customer_name = ? ORDER BY id DESC");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $orders = $stmt->get_result();

            if ($orders->num_rows > 0):
                while ($o = $orders->fetch_assoc()):
                    $oid = $o['id'];
                    $items = $conn->query("SELECT * FROM order_items WHERE order_id = $oid");
            ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <div>
                            <strong class="order-code">MÃ ĐƠN #<?php echo $oid; ?></strong>
                            <span class="order-date">Ngày đặt: <?php echo $o['created_at']; ?></span>
                        </div>
                        <span class="badge" style="margin: 0;"><?php echo htmlspecialchars($o['status']); ?></span>
                    </div>

                    <?php while ($it = $items->fetch_assoc()): ?>
                        <div class="order-item-row">
                            <span>• <?php echo htmlspecialchars($it['product_name']); ?> <strong>(x<?php echo $it['quantity']; ?>)</strong></span>
                            <span><?php echo number_format($it['price'] * $it['quantity'], 0, ',', '.'); ?>₫</span>
                        </div>
                    <?php endwhile; ?>

                    <div class="order-card-footer">
                        <span class="order-address">📍 Giao tới: <?php echo htmlspecialchars($o['address']); ?> (<?php echo htmlspecialchars($o['phone']); ?>)</span>
                        <strong class="order-total-amount">
                            Tổng: <?php echo number_format($o['total_price'], 0, ',', '.'); ?>₫
                        </strong>
                    </div>
                </div>
            <?php
                endwhile;
            else:
                echo "<p class='empty-order-msg'>Bạn chưa có đơn hàng nào. Hãy quay lại <a href='trangchu.php'>Kho mô hình</a> để chọn sản phẩm nhé!</p>";
            endif;
            $stmt->close();
            ?>
        </div>
    </main>

    <?php include 'components/footer.html'; ?>
</body>
</html>
