<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Lấy dữ liệu (Hỗ trợ cả JSON và Form Post)
$data = getInputData();

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';
$fullName = $data['full_name'] ?? '';

// Validate input
if (empty($email) || empty($password) || empty($confirmPassword) || empty($fullName)) {
    sendJSON(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin (Email, Mật khẩu, Tên)']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJSON(['success' => false, 'message' => 'Định dạng Email không hợp lệ']);
}

if (strlen($password) < 6) {
    sendJSON(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự']);
}

if ($password !== $confirmPassword) {
    sendJSON(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp']);
}

try {
    // Kiểm tra email tồn tại
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        sendJSON(['success' => false, 'message' => 'Email này đã được sử dụng. Vui lòng chọn email khác.']);
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, role) VALUES (?, ?, ?, 'user')");
    if ($stmt->execute([$email, $hashedPassword, $fullName])) {
        $userId = $conn->lastInsertId();
        
        // Auto login sau khi đăng ký
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['role'] = 'user';
        
        sendJSON([
            'success' => true,
            'message' => 'Đăng ký thành công!',
            'redirect' => 'dashboard.html'
        ]);
    } else {
        throw new Exception("Không thể thêm user vào CSDL");
    }
    
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
}
?>