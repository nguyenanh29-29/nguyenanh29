<?php
// login.php - Xử lý đăng nhập

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $isAdmin = isset($_POST['isAdmin']) ? $_POST['isAdmin'] : false;
    
    // Validate
    if (empty($email) || empty($password)) {
        echo json_encode(array(
            "success" => false,
            "message" => "Vui lòng điền đầy đủ thông tin!"
        ));
        exit();
    }
    
    // Tìm user
    $query = "SELECT id, fullname, email, password, avatar, role, created_at 
              FROM users WHERE email = :email LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Kiểm tra mật khẩu
        if (password_verify($password, $row['password'])) {
            
            // Nếu là admin login, kiểm tra role
            if ($isAdmin && $row['role'] !== 'admin') {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Bạn không có quyền truy cập trang quản trị!"
                ));
                exit();
            }
            
            // Tạo token (đơn giản)
            $token = bin2hex(random_bytes(32));
            
            // Cập nhật last_login
            $update_query = "UPDATE users SET last_login = NOW() WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":id", $row['id']);
            $update_stmt->execute();
            
            echo json_encode(array(
                "success" => true,
                "message" => "Đăng nhập thành công!",
                "token" => $token,
                "user" => array(
                    "id" => $row['id'],
                    "fullname" => $row['fullname'],
                    "email" => $row['email'],
                    "avatar" => $row['avatar'],
                    "role" => $row['role'],
                    "created_at" => $row['created_at']
                )
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Mật khẩu không chính xác!"
            ));
        }
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "Email không tồn tại!"
        ));
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "Invalid request method"
    ));
}
?>