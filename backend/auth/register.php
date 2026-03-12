<?php
// register.php - Xử lý đăng ký tài khoản

require_once '../config/db.php';

// Tạo database nếu chưa có
Database::createDatabase();

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate
    if (empty($fullname) || empty($email) || empty($password)) {
        echo json_encode(array(
            "success" => false,
            "message" => "Vui lòng điền đầy đủ thông tin!"
        ));
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(array(
            "success" => false,
            "message" => "Email không hợp lệ!"
        ));
        exit();
    }
    
    if (strlen($password) < 6) {
        echo json_encode(array(
            "success" => false,
            "message" => "Mật khẩu phải có ít nhất 6 ký tự!"
        ));
        exit();
    }
    
    // Kiểm tra email đã tồn tại
    $query = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(array(
            "success" => false,
            "message" => "Email đã được sử dụng!"
        ));
        exit();
    }
    
    // Mã hóa mật khẩu
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    // Thêm user mới
    $query = "INSERT INTO users (fullname, email, password, role, created_at) 
              VALUES (:fullname, :email, :password, 'user', NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":fullname", $fullname);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password", $password_hash);
    
    if ($stmt->execute()) {
        $user_id = $db->lastInsertId();
        
        echo json_encode(array(
            "success" => true,
            "message" => "Đăng ký thành công!",
            "user_id" => $user_id
        ));
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "Đăng ký thất bại. Vui lòng thử lại!"
        ));
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "Invalid request method"
    ));
}
?>