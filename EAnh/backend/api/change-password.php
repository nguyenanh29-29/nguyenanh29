<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];

$currentPassword = $data['current_password'] ?? '';
$newPassword = $data['new_password'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
    sendJSON(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
}

if (strlen($newPassword) < 6) {
    sendJSON(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự']);
}

try {
    // Get current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || empty($user['password'])) {
        sendJSON(['success' => false, 'message' => 'Tài khoản đăng nhập qua Google không có mật khẩu']);
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        sendJSON(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $userId]);
    
    sendJSON(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Lỗi hệ thống'], 500);
}
?>