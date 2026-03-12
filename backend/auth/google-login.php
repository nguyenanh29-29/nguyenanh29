<?php
// google-login.php - Xử lý đăng nhập Google qua Firebase

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Nhận dữ liệu từ Javascript (Firebase) gửi lên
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $fullname = isset($_POST['fullname']) ? $_POST['fullname'] : 'Người dùng Google';
    $avatar = isset($_POST['avatar']) ? $_POST['avatar'] : '';
    $google_id = isset($_POST['google_id']) ? $_POST['google_id'] : '';
    
    if (empty($email) || empty($google_id)) {
        echo json_encode(array(
            "success" => false,
            "message" => "Dữ liệu từ Google không hợp lệ!"
        ));
        exit();
    }
    
    // Kiểm tra user đã tồn tại trong database chưa
    $query = "SELECT id, fullname, email, avatar, role, created_at 
              FROM users WHERE email = :email LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // TRƯỜNG HỢP 1: User ĐÃ TỒN TẠI -> Thực hiện Đăng nhập
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Cập nhật avatar mới nhất và thời gian đăng nhập
        $update_query = "UPDATE users SET avatar = :avatar, last_login = NOW() WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":avatar", $avatar);
        $update_stmt->bindParam(":id", $row['id']);
        $update_stmt->execute();
        
        $token = bin2hex(random_bytes(32)); // Tạo token giả lập session
        
        echo json_encode(array(
            "success" => true,
            "message" => "Đăng nhập Google thành công!",
            "token" => $token,
            "user" => array(
                "id" => $row['id'],
                "fullname" => $row['fullname'],
                "email" => $row['email'],
                "avatar" => $avatar,
                "role" => $row['role'],
                "created_at" => $row['created_at']
            )
        ));
    } else {
        // TRƯỜNG HỢP 2: User CHƯA TỒN TẠI -> Tự động Đăng ký mới
        $query = "INSERT INTO users (fullname, email, avatar, google_id, role, created_at) 
                  VALUES (:fullname, :email, :avatar, :google_id, 'user', NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":fullname", $fullname);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":avatar", $avatar);
        $stmt->bindParam(":google_id", $google_id);
        
        if ($stmt->execute()) {
            $user_id = $db->lastInsertId();
            $token = bin2hex(random_bytes(32));
            
            echo json_encode(array(
                "success" => true,
                "message" => "Tạo tài khoản qua Google thành công!",
                "token" => $token,
                "user" => array(
                    "id" => $user_id,
                    "fullname" => $fullname,
                    "email" => $email,
                    "avatar" => $avatar,
                    "role" => "user",
                    "created_at" => date('Y-m-d H:i:s')
                )
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Lỗi CSDL: Không thể tạo tài khoản!"
            ));
        }
    }
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "Invalid request method"
    ));
}
?>