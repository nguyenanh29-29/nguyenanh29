<?php
// listening.php - API xử lý luyện nghe

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

// GET - Lấy danh sách bài nghe
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (isset($_GET['id'])) {
        // Lấy chi tiết một bài
        $id = $_GET['id'];
        
        $query = "SELECT * FROM listening WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $listening = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(array(
                "success" => true,
                "listening" => $listening
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Không tìm thấy bài nghe"
            ));
        }
    } 
    else {
        // Lấy danh sách bài nghe
        $level = isset($_GET['level']) ? $_GET['level'] : '';
        $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
        
        $query = "SELECT * FROM listening WHERE 1=1";
        
        if (!empty($level)) {
            $query .= " AND level = :level";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 50";
        
        $stmt = $db->prepare($query);
        
        if (!empty($level)) {
            $stmt->bindParam(":level", $level);
        }
        
        $stmt->execute();
        $listening = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Thống kê
        $stats_query = "SELECT 
            (SELECT COUNT(*) FROM listening) as total,
            (SELECT COUNT(DISTINCT content_id) FROM user_progress WHERE user_id = :user_id AND content_type = 'listening' AND completed = 1) as completed,
            (SELECT SUM(time_spent) / 60 FROM user_progress WHERE user_id = :user_id AND content_type = 'listening') as totalMinutes";
        
        $stats_stmt = $db->prepare($stats_query);
        $stats_stmt->bindParam(":user_id", $userId);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        $stats['totalMinutes'] = ceil($stats['totalMinutes'] ?? 0);
        
        echo json_encode(array(
            "success" => true,
            "listening" => $listening,
            "stats" => $stats
        ));
    }
}

// POST - Cập nhật tiến độ
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'mark_completed') {
        $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $listeningId = isset($_POST['listeningId']) ? intval($_POST['listeningId']) : 0;
        
        if ($userId && $listeningId) {
            $query = "INSERT INTO user_progress (user_id, content_type, content_id, completed, time_spent, completed_at, created_at) 
                      VALUES (:user_id, 'listening', :content_id, 1, 180, NOW(), NOW())
                      ON DUPLICATE KEY UPDATE 
                      completed = 1,
                      time_spent = time_spent + 180,
                      completed_at = NOW()";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":content_id", $listeningId);
            
            if ($stmt->execute()) {
                echo json_encode(array(
                    "success" => true,
                    "message" => "Đã đánh dấu hoàn thành!"
                ));
            } else {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Không thể cập nhật!"
                ));
            }
        }
    }
    elseif ($action === 'add_listening') {
        // Thêm bài mới (admin)
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $audioUrl = isset($_POST['audio_url']) ? trim($_POST['audio_url']) : '';
        $transcript = isset($_POST['transcript']) ? trim($_POST['transcript']) : '';
        $level = isset($_POST['level']) ? $_POST['level'] : 'A1';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 3;
        
        if (empty($title)) {
            echo json_encode(array(
                "success" => false,
                "message" => "Thiếu thông tin bắt buộc!"
            ));
            exit();
        }
        
        $query = "INSERT INTO listening (title, content, audio_url, transcript, level, duration, created_at) 
                  VALUES (:title, :content, :audio_url, :transcript, :level, :duration, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":content", $content);
        $stmt->bindParam(":audio_url", $audioUrl);
        $stmt->bindParam(":transcript", $transcript);
        $stmt->bindParam(":level", $level);
        $stmt->bindParam(":duration", $duration);
        
        if ($stmt->execute()) {
            echo json_encode(array(
                "success" => true,
                "message" => "Thêm bài nghe thành công!",
                "listening_id" => $db->lastInsertId()
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Không thể thêm bài nghe!"
            ));
        }
    }
}

else {
    echo json_encode(array(
        "success" => false,
        "message" => "Invalid request method"
    ));
}
?>