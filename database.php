<?php
session_start();

// Kết nối trực tiếp tới MySQL
$conn = new mysqli("localhost", "root", "", "web_mohinh");
if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$message = "";
$msg_type = ""; // 'success' hoặc 'error'
