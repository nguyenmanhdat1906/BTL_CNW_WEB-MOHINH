<?php session_start(); ?>
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

    <!-- THANH ĐIỀU HƯỚNG KÍNH MỜ (HEADER) -->
    <header class="glass-header">
        <a href="trangchu.php" class="logo">GUNPLA<span>HUB</span></a>
        
        <nav class="nav-links">
            <a href="trangchu.php" class="active">Trang chủ</a>
            <a href="#products">Mô hình Mới</a>
            <a href="#grades">Phân cấp (Grade)</a>
            <a href="#tools">Dụng cụ Gunpla</a>
        </nav>

        <div class="header-actions">
            <a href="#" class="cart-btn">🛒 Giỏ hàng (0)</a>
            <?php if (isset($_SESSION['username'])): ?>
    <span style="color: #00d2ff; font-family: 'Orbitron', sans-serif; font-size: 13px; font-weight: 700;">
        👨‍🚀 <?php echo htmlspecialchars($_SESSION['username']); ?>
    </span>
    <a href="logout.php" class="login-btn" style="background: rgba(255,255,255,0.15); box-shadow: none;">ĐĂNG XUẤT</a>
<?php else: ?>
    <a href="login.php" class="login-btn">LOGIN</a>
<?php endif; ?>

        </div>
    </header>

    <!-- KHU VỰC BANNER CHÍNH (HERO SECTION) -->
    <section class="hero-section">
        <div class="hero-glass-box">
            <span class="badge">NEW ARRIVAL 2026</span>
            <h1>BỘ SƯU TẬP GUNPLA THẾ HỆ MỚI</h1>
            <p>Khám phá các mẫu mô hình lắp ráp Bandai chính hãng từ tỷ lệ 1/144 đến 1/60. Trải nghiệm công nghệ khung xương chi tiết và hiệu ứng phát sáng đỉnh cao.</p>
            <div class="hero-buttons">
                <a href="#products" class="btn-primary">KHÁM PHÁ NGAY</a>
                <a href="login.php" class="btn-secondary">ĐĂNG KÝ THÀNH VIÊN</a>
            </div>
        </div>
    </section>

    <!-- KHU VỰC TRƯNG BÀY SẢN PHẨM -->
    <main id="products" class="container">
        
        <!-- Thanh tiêu đề & Bộ lọc phân loại -->
        <div class="section-header">
            <h2>KHO MÔ HÌNH TRƯNG BÀY</h2>
            <div class="filter-tabs" id="grades">
                <button class="filter-btn active" onclick="filterProduct('all', this)">TẤT CẢ</button>
                <button class="filter-btn" onclick="filterProduct('HG', this)">HG (1/144)</button>
                <button class="filter-btn" onclick="filterProduct('RG', this)">RG (1/144)</button>
                <button class="filter-btn" onclick="filterProduct('MG', this)">MG (1/100)</button>
                <button class="filter-btn" onclick="filterProduct('PG', this)">PG (1/60)</button>
            </div>
        </div>

        <!-- Lưới sản phẩm (Product Grid) -->
        <div class="product-grid">

            <!-- Sản phẩm 1 -->
            <div class="product-card" data-grade="MG">
                <div class="card-badge mg">MG 1/100</div>
                <div class="img-box">
                    <img src="images/wallpaperflare.com_wallpaper.jpg" alt="Strike Freedom Gundam">
                </div>
                <div class="card-info">
                    <h3>MGEX Strike Freedom Gundam</h3>
                    <p class="series">Mobile Suit Gundam SEED Destiny</p>
                    <div class="price-row">
                        <span class="price">3.450.000₫</span>
                        <button class="add-cart-btn">+ Thêm</button>
                    </div>
                </div>
            </div>

            <!-- Sản phẩm 2 -->
            <div class="product-card" data-grade="RG">
                <div class="card-badge rg">RG 1/144</div>
                <div class="img-box">
                    <img src="images/wallpaperflare.com_wallpaper.jpg" alt="RX-93 Nu Gundam">
                </div>
                <div class="card-info">
                    <h3>RG RX-93ff Nu Gundam</h3>
                    <p class="series">Char's Counterattack</p>
                    <div class="price-row">
                        <span class="price">1.150.000₫</span>
                        <button class="add-cart-btn">+ Thêm</button>
                    </div>
                </div>
            </div>

            <!-- Sản phẩm 3 -->
            <div class="product-card" data-grade="PG">
                <div class="card-badge pg">PG 1/60</div>
                <div class="img-box">
                    <img src="images/wallpaperflare.com_wallpaper.jpg" alt="Unicorn Gundam">
                </div>
                <div class="card-info">
                    <h3>PG RX-0 Unicorn Gundam</h3>
                    <p class="series">Mobile Suit Gundam Unicorn</p>
                    <div class="price-row">
                        <span class="price">4.800.000₫</span>
                        <button class="add-cart-btn">+ Thêm</button>
                    </div>
                </div>
            </div>

            <!-- Sản phẩm 4 -->
            <div class="product-card" data-grade="HG">
                <div class="card-badge hg">HG 1/144</div>
                <div class="img-box">
                    <img src="images/wallpaperflare.com_wallpaper.jpg" alt="Gundam Aerial">
                </div>
                <div class="card-info">
                    <h3>HG Gundam Aerial Rebuild</h3>
                    <p class="series">The Witch from Mercury</p>
                    <div class="price-row">
                        <span class="price">480.000₫</span>
                        <button class="add-cart-btn">+ Thêm</button>
                    </div>
                </div>
            </div>

            <!-- Sản phẩm 5 -->
            <div class="product-card" data-grade="MG">
                <div class="card-badge mg">MG 1/100</div>
                <div class="img-box">
                    <img src="images/wallpaperflare.com_wallpaper.jpg" alt="Barbatos Gundam">
                </div>
                <div class="card-info">
                    <h3>MG ASW-G-08 Gundam Barbatos</h3>
                    <p class="series">Iron-Blooded Orphans</p>
                    <div class="price-row">
                        <span class="price">1.250.000₫</span>
                        <button class="add-cart-btn">+ Thêm</button>
                    </div>
                </div>
            </div>

            <!-- Sản phẩm 6 -->
            <div class="product-card" data-grade="RG">
                <div class="card-badge rg">RG 1/144</div>
                <div class="img-box">
                    <img src="images/wallpaperflare.com_wallpaper.jpg" alt="Sazabi">
                </div>
                <div class="card-info">
                    <h3>RG MSN-04 Sazabi</h3>
                    <p class="series">Char's Counterattack</p>
                    <div class="price-row">
                        <span class="price">1.100.000₫</span>
                        <button class="add-cart-btn">+ Thêm</button>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- CHÂN TRANG (FOOTER) -->
    <footer class="glass-footer">
        <p>© 2026 GUNPLA HUB - Hệ thống phân phối mô hình Gundam chính hãng.</p>
    </footer>

    <!-- Script lọc phân loại sản phẩm nhanh -->
    <script>
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
    </script>

</body>
</html>
