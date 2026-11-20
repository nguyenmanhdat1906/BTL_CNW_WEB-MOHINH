<?php
session_start();
require_once 'database.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GUNPLA HUB - Xưởng Mô Hình Gundam Chính Hãng</title>
    <link href="https://googleapis.com" rel="stylesheet">
    <link rel="stylesheet" href="css/trangchu.css">
</head>
<body>

    <!-- 1. Nhúng Header -->
    <?php include 'components/header.html'; ?>

    <!-- Banner chào mừng -->
    <section class="hero-section">
        <div class="hero-glass-box">
            <span class="badge">NEW ARRIVAL 2026</span>
            <h1>BỘ SƯU TẬP GUNPLA THẾ HỆ MỚI</h1>
            <p>Khám phá các mẫu mô hình lắp ráp Bandai chính hãng từ tỷ lệ 1/144 đến 1/60.</p>
        </div>
    </section>

    <!-- Bố cục 3 cột: Left - Content - Right -->
    <div class="layout-wrapper">
        <?php include 'components/left.html'; ?>
        <?php include 'components/content.html'; ?>
        <?php include 'components/right.html'; ?>
    </div>

    <!-- 5. Nhúng Footer & Giỏ hàng -->
    <?php include 'components/footer.html'; ?>

    <!-- Script Lọc sản phẩm & Giỏ hàng -->
    <script>
        // Lọc theo Grade
        function filterProduct(grade, btnElement) {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            btnElement.classList.add('active');

            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                if (grade === 'all' || card.getAttribute('data-grade') === grade) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        let cart = JSON.parse(localStorage.getItem('gunpla_cart')) || [];

        // Mở / Đóng giỏ hàng
        function toggleCart() {
            document.getElementById('cart-drawer').classList.toggle('open');
            document.getElementById('cart-overlay').classList.toggle('show');
        }

        // Hàm nhận dữ liệu từ nút bấm có thuộc tính data-*
        function addFromBtn(btn) {
            const name = btn.getAttribute('data-name');
            const price = Number(btn.getAttribute('data-price'));
            const image = btn.getAttribute('data-image');
            addToCart(name, price, image);
        }

        // Thêm sản phẩm vào giỏ
        function addToCart(name, price, image) {
            const existingItem = cart.find(item => item.name === name);
            if (existingItem) {
                existingItem.qty += 1;
            } else {
                cart.push({ name: name, price: price, image: image, qty: 1 });
            }
            saveAndRenderCart();
            showToast(`Đã thêm "${name}" vào giỏ!`);
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

        // Gửi đơn hàng về database
        async function checkout() {
            if (cart.length === 0) {
                alert('Giỏ hàng của bạn đang trống!');
                return;
            }

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
                alert('Không thể kết nối tới máy chủ để lưu đơn hàng!');
            }
        }

        saveAndRenderCart();
    </script>
</body>
</html>
