<?php
$conn = new mysqli("localhost", "root", "");
if ($conn->connect_error) {
    die("Kết nối MySQL thất bại: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS web_mohinh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db("web_mohinh");
$conn->set_charset("utf8mb4");

// 1. Tạo bảng sản phẩm (products)
$conn->query("CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    grade VARCHAR(20) NOT NULL,
    series VARCHAR(150) NOT NULL,
    price INT NOT NULL,
    image VARCHAR(255) NOT NULL
)");

// 2. Tạo bảng đơn hàng (orders)
$conn->query("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    total_price INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Chờ xác nhận',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 3. Tạo bảng chi tiết từng món trong đơn hàng (order_items)
$conn->query("CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL,
    price INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
)");

// 4. Tạo bảng tài khoản người dùng (users)
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Chèn sẵn dữ liệu mẫu nếu bảng products đang trống
$check_prod = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc();
if ($check_prod['total'] == 0) {
    $conn->query("INSERT INTO products (name, grade, series, price, image) VALUES 
        ('MGEX Strike Freedom Gundam', 'MG', 'Mobile Suit Gundam SEED Destiny', 3450000, 'images/wallpaperflare.com_wallpaper.jpg'),
        ('RG RX-93ff Nu Gundam', 'RG', 'Char\'s Counterattack', 1150000, 'images/wallpaperflare.com_wallpaper.jpg'),
        ('PG RX-0 Unicorn Gundam', 'PG', 'Mobile Suit Gundam Unicorn', 4800000, 'images/wallpaperflare.com_wallpaper.jpg'),
        ('HG Gundam Aerial Rebuild', 'HG', 'The Witch from Mercury', 480000, 'images/wallpaperflare.com_wallpaper.jpg'),
        ('MG ASW-G-08 Gundam Barbatos', 'MG', 'Iron-Blooded Orphans', 1250000, 'images/wallpaperflare.com_wallpaper.jpg'),
        ('RG MSN-04 Sazabi', 'RG', 'Char\'s Counterattack', 1100000, 'images/wallpaperflare.com_wallpaper.jpg')
    ");
}
?>
