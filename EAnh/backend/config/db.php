<?php
// backend/config/db.php

// 1. Cấu hình Database
define('DB_HOST', '127.0.0.1'); 
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'eanh');

// 2. QUAN TRỌNG: CẤU HÌNH CHO PHÉP TRUY CẬP (CORS)
header("Access-Control-Allow-Origin: *"); // Cho phép mọi nguồn
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Xử lý request kiểm tra (Preflight) của trình duyệt
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. Kết nối Database
try {
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối CSDL: ' . $e->getMessage()]);
    exit;
}

// Hàm nhận dữ liệu JSON
function getInputData() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?? $_POST;
}

// Hàm trả về JSON
function sendJSON($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
?>