<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Lấy dữ liệu (Hỗ trợ cả JSON và Form Post)
$data = getInputData();

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    sendJSON(['success' => false, 'message' => 'Vui lòng nhập Email và Mật khẩu']);
}

try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Kiểm tra user tồn tại
    if (!$user) {
        sendJSON(['success' => false, 'message' => 'Email này chưa được đăng ký']);
    }
    
    // Kiểm tra nếu là tài khoản Google
    if (!empty($user['google_id']) && empty($user['password'])) {
        sendJSON(['success' => false, 'message' => 'Tài khoản này dùng Google Login. Vui lòng chọn "Đăng nhập bằng Google".']);
    }
    
    // Kiểm tra mật khẩu
    if (!password_verify($password, $user['password'])) {
        sendJSON(['success' => false, 'message' => 'Mật khẩu không đúng']);
    }
    
    // Lưu session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['avatar'] = $user['avatar'];
    
    sendJSON([
        'success' => true,
        'message' => 'Đăng nhập thành công',
        'user' => [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
            'avatar' => $user['avatar']
        ],
        'redirect' => $user['role'] === 'admin' ? 'admin-dashboard.html' : 'dashboard.html'
    ]);
    
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()], 500);
}
?>