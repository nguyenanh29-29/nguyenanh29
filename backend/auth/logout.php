<?php
// logout.php - Xử lý đăng xuất

require_once '../config/db.php';

// Xóa tất cả session
session_start();
session_unset();
session_destroy();

// Xóa cookies nếu có
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

echo json_encode(array(
    "success" => true,
    "message" => "Đã đăng xuất thành công!"
));
?>