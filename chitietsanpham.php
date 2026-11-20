<?php
session_start();
require_once 'database.php';

// Lấy ID sản phẩm từ URL (?id=...)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

// Nếu không tìm thấy sản phẩm, quay về trang chủ
if (!$product) {
    header("Location: trangchu.php");
    exit();
}

// Thông số kỹ thuật tự động theo Grade
$specs = [
    'HG' => ['scale' => '1/144 (~13cm)', 'runners' => '8 - 12 vỉ nhựa', 'difficulty' => 'Dễ (Phù hợp người mới)'],
    'RG' => ['scale' => '1/144 (~13cm)', 'runners' => '12 - 16 vỉ nhựa', 'difficulty' => 'Trung bình - Khó (Khung xương chi tiết)'],
    'MG' => ['scale' => '1/100 (~18cm)', 'runners' => '18 - 25 vỉ nhựa', 'difficulty' => 'Trung bình - Khó (Có buồng lái Pilot)'],
    'PG' => ['scale' => '1/60 (~30cm)', 'runners' => '35 - 50 vỉ nhựa', 'difficulty' => 'Siêu cấp (Tích hợp đèn LED & khớp cơ khí)']
];
$grade = strtoupper($product['grade']);
$current_spec = isset($specs[$grade]) ? $specs[$grade] : $specs['MG'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - GUNPLA HUB</title>
    <link href="https://googleapis.com" rel="stylesheet">
    <!-- Nhúng CSS chung và CSS riêng của trang chi tiết -->
    <link rel="stylesheet" href="css/trangchu.css">
    <link rel="stylesheet" href="css/chitietsanpham.css">
</head>
<body>

    <!-- Nhúng Header dùng chung -->
    <?php include 'components/header.html'; ?>

    <main class="detail-container">
        <div class="breadcrumb">
            <a href="trangchu.php">← Quay lại Kho mô hình</a> / <span><?php echo htmlspecialchars($product['grade']); ?></span> / <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <!-- Khung chi tiết sản phẩm kính mờ -->
        <div class="detail-glass-box">
            <div class="detail-img-box">
                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>

            <div class="detail-info">
                <span class="badge"><?php echo htmlspecialchars($product['grade']); ?> SERIES</span>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="detail-series">Xuất xứ Anime: <strong><?php echo htmlspecialchars($product['series']); ?></strong></p>

                <div class="detail-price"><?php echo number_format($product['price'], 0, ',', '.'); ?>₫</div>

                <table class="spec-table">
                    <tr>
                        <td>Thương hiệu</td>
                        <td><strong>Bandai Spirits (Nhật Bản)</strong></td>
                    </tr>
                    <tr>
                        <td>Tỷ lệ & Chiều cao</td>
                        <td><?php echo $current_spec['scale']; ?></td>
                    </tr>
                    <tr>
                        <td>Số lượng vỉ nhựa</td>
                        <td><?php echo $current_spec['runners']; ?></td>
                    </tr>
                    <tr>
                        <td>Độ khó lắp ráp</td>
                        <td><?php echo $current_spec['difficulty']; ?></td>
                    </tr>
                    <tr>
                        <td>Tình trạng kho</td>
                        <td class="stock-status">✓ Sẵn hàng tại Xưởng</td>
                    </tr>
                </table>

                <div class="action-row">
                    <div class="qty-selector">
                        <button type="button" onclick="updateDetailQty(-1)">-</button>
                        <input type="text" id="detail-qty" value="1" readonly>
                        <button type="button" onclick="updateDetailQty(1)">+</button>
                    </div>

                    <button type="button" class="btn-buy-big"
                        data-name="<?php echo htmlspecialchars($product['name']); ?>"
                        data-price="<?php echo (int)$product['price']; ?>"
                        data-image="<?php echo htmlspecialchars($product['image']); ?>"
                        onclick="addDetailToCart(this)">
                        🛒 THÊM VÀO KHO ĐỒ PILOT
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Nhúng Footer & Ngăn kéo Giỏ hàng dùng chung -->
    <?php include 'components/footer.html'; ?>

    <script>
        let cart = JSON.parse(localStorage.getItem('gunpla_cart')) || [];

        function updateDetailQty(delta) {
            const qtyInput = document.getElementById('detail-qty');
            let current = parseInt(qtyInput.value) + delta;
            if (current < 1) current = 1;
            qtyInput.value = current;
        }

        function addDetailToCart(btn) {
            const name = btn.getAttribute('data-name');
            const price = Number(btn.getAttribute('data-price'));
            const image = btn.getAttribute('data-image');
            const qty = parseInt(document.getElementById('detail-qty').value);

            const existingItem = cart.find(item => item.name === name);
            if (existingItem) {
                existingItem.qty += qty;
            } else {
                cart.push({ name: name, price: price, image: image, qty: qty });
            }

            saveAndRenderCart();
            showToast(`Đã thêm ${qty}x "${name}" vào giỏ!`);
        }

        function toggleCart() {
            document.getElementById('cart-drawer').classList.toggle('open');
            document.getElementById('cart-overlay').classList.toggle('show');
        }

        function changeQty(index, delta) {
            cart[index].qty += delta;
            if (cart[index].qty <= 0) cart.splice(index, 1);
            saveAndRenderCart();
        }

        function removeItem(index) {
            cart.splice(index, 1);
            saveAndRenderCart();
        }

        function saveAndRenderCart() {
            localStorage.setItem('gunpla_cart', JSON.stringify(cart));
            const cartCountEl = document.getElementById('cart-count');
            const cartItemsEl = document.getElementById('cart-items');
            const cartTotalEl = document.getElementById('cart-total-price');

            if (cartCountEl) {
                cartCountEl.innerText = cart.reduce((sum, item) => sum + item.qty, 0);
            }
            if (!cartItemsEl || !cartTotalEl) return;

            if (cart.length === 0) {
                cartItemsEl.innerHTML = '<p class="empty-cart">Chưa có mô hình nào trong kho của bạn.</p>';
                cartTotalEl.innerText = '0₫';
                return;
            }

            let html = '';
            let totalPrice = 0;
            cart.forEach((item, index) => {
                totalPrice += item.price * item.qty;
                html += `
                    <div class="cart-item">
                        <img src="${item.image}" alt="${item.name}">
                        <div class="item-details">
                            <h4>${item.name}</h4>
                            <span class="item-price">${item.price.toLocaleString('vi-VN')}₫</span>
                            <div class="qty-controls">
                                <button onclick="changeQty(${index}, -1)">-</button>
                                <span>${item.qty}</span>
                                <button onclick="changeQty(${index}, 1)">+</button>
                            </div>
                        </div>
                        <button class="remove-btn" onclick="removeItem(${index})">🗑</button>
                    </div>
                `;
            });
            cartItemsEl.innerHTML = html;
            cartTotalEl.innerText = totalPrice.toLocaleString('vi-VN') + '₫';
        }

        function showToast(message) {
            const toast = document.getElementById('toast-msg');
            if (!toast) return;
            toast.innerText = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }

        async function checkout() {
            if (cart.length === 0) return alert('Giỏ hàng của bạn đang trống!');
            const cusName = document.getElementById('cus-name').value.trim();
            const cusPhone = document.getElementById('cus-phone').value.trim();
            const cusAddress = document.getElementById('cus-address').value.trim();

            if (!cusName || !cusPhone || !cusAddress) {
                alert('Vui lòng điền đầy đủ Tên, Số điện thoại và Địa chỉ giao hàng!');
                return;
            }

            const totalPrice = cart.reduce((sum, item) => sum + item.price * item.qty, 0);

            try {
                const response = await fetch('luu_donhang.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_name: cusName,
                        phone: cusPhone,
                        address: cusAddress,
                        total_price: totalPrice,
                        items: cart
                    })
                });
                const result = await response.json();
                if (result.status === 'success') {
                    alert(result.message);
                    cart = [];
                    saveAndRenderCart();
                    toggleCart();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Không thể kết nối tới máy chủ!');
            }
        }

