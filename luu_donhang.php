<?php
header('Content-Type: application/json');
require_once 'database.php';

// Đọc dữ liệu JSON gửi từ JavaScript
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['items'])) {
    echo json_encode(['status' => 'error', 'message' => 'Giỏ hàng trống hoặc dữ liệu không hợp lệ!']);
    exit();
}

$customer_name = trim($data['customer_name']);
$phone = trim($data['phone']);
$address = trim($data['address']);
$total_price = (int)$data['total_price'];
$items = $data['items'];

// 1. Lưu thông tin chung vào bảng orders
$stmt = $conn->prepare("INSERT INTO orders (customer_name, phone, address, total_price) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $customer_name, $phone, $address, $total_price);

if ($stmt->execute()) {
    $order_id = $stmt->insert_id; // Lấy ID đơn hàng vừa tạo

    // 2. Lưu từng sản phẩm vào bảng order_items
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_name, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt_item->bind_param("isii", $order_id, $item['name'], $item['qty'], $item['price']);
        $stmt_item->execute();
    }
    $stmt_item->close();

    echo json_encode(['status' => 'success', 'message' => "Đặt hàng thành công! Mã đơn hàng của Pilot là #$order_id"]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi lưu đơn hàng: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
