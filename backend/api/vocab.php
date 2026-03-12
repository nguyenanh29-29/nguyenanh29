<?php
// vocab.php - API xử lý từ vựng

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

// GET - Lấy danh sách từ vựng hoặc chi tiết
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (isset($_GET['id'])) {
        // Lấy chi tiết một từ
        $id = $_GET['id'];
        
        $query = "SELECT * FROM vocabulary WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $vocab = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(array(
                "success" => true,
                "vocabulary" => $vocab
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Không tìm thấy từ vựng"
            ));
        }
    } else {
        // Lấy danh sách từ vựng với filter
        $level = isset($_GET['level']) ? $_GET['level'] : '';
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        $query = "SELECT * FROM vocabulary WHERE 1=1";
        
        if (!empty($level)) {
            $query .= " AND level = :level";
        }
        
        if (!empty($category)) {
            $query .= " AND category = :category";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        
        if (!empty($level)) {
            $stmt->bindParam(":level", $level);
        }
        
        if (!empty($category)) {
            $stmt->bindParam(":category", $category);
        }
        
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $vocabulary = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy thống kê
        $stats_query = "SELECT 
            (SELECT COUNT(*) FROM vocabulary) as total,
            (SELECT COUNT(*) FROM user_vocabulary WHERE user_id = :user_id) as learned,
            (SELECT COUNT(*) FROM user_vocabulary WHERE user_id = :user_id AND mastered = 1) as mastered";
        
        $user_id = isset($_GET['userId']) ? $_GET['userId'] : 0;
        $stats_stmt = $db->prepare($stats_query);
        $stats_stmt->bindParam(":user_id", $user_id);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(array(
            "success" => true,
            "vocabulary" => $vocabulary,
            "stats" => $stats,
            "total" => count($vocabulary)
        ));
    }
}

// POST - Thêm từ mới hoặc đánh dấu đã học
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'mark_learned') {
        // Đánh dấu từ đã học
        $user_id = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $vocab_id = isset($_POST['vocabId']) ? intval($_POST['vocabId']) : 0;
        
        if ($user_id && $vocab_id) {
            $query = "INSERT INTO user_vocabulary (user_id, vocabulary_id, created_at, last_reviewed) 
                      VALUES (:user_id, :vocab_id, NOW(), NOW())
                      ON DUPLICATE KEY UPDATE 
                      review_count = review_count + 1,
                      last_reviewed = NOW()";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":vocab_id", $vocab_id);
            
            if ($stmt->execute()) {
                echo json_encode(array(
                    "success" => true,
                    "message" => "Đã đánh dấu từ này!"
                ));
            } else {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Không thể đánh dấu từ này!"
                ));
            }
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Thiếu thông tin!"
            ));
        }
    } 
    elseif ($action === 'add_vocab') {
        // Thêm từ mới (chỉ admin)
        $word = isset($_POST['word']) ? trim($_POST['word']) : '';
        $pronunciation = isset($_POST['pronunciation']) ? trim($_POST['pronunciation']) : '';
        $meaning = isset($_POST['meaning']) ? trim($_POST['meaning']) : '';
        $example = isset($_POST['example']) ? trim($_POST['example']) : '';
        $level = isset($_POST['level']) ? $_POST['level'] : 'A1';
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        
        if (empty($word) || empty($meaning)) {
            echo json_encode(array(
                "success" => false,
                "message" => "Thiếu thông tin bắt buộc!"
            ));
            exit();
        }
        
        $query = "INSERT INTO vocabulary (word, pronunciation, meaning, example, level, category, created_at) 
                  VALUES (:word, :pronunciation, :meaning, :example, :level, :category, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":word", $word);
        $stmt->bindParam(":pronunciation", $pronunciation);
        $stmt->bindParam(":meaning", $meaning);
        $stmt->bindParam(":example", $example);
        $stmt->bindParam(":level", $level);
        $stmt->bindParam(":category", $category);
        
        if ($stmt->execute()) {
            echo json_encode(array(
                "success" => true,
                "message" => "Thêm từ vựng thành công!",
                "vocab_id" => $db->lastInsertId()
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Không thể thêm từ vựng!"
            ));
        }
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "Action không hợp lệ!"
        ));
    }
}

// PUT - Cập nhật từ vựng
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    parse_str(file_get_contents("php://input"), $_PUT);
    
    $id = isset($_PUT['id']) ? intval($_PUT['id']) : 0;
    $word = isset($_PUT['word']) ? trim($_PUT['word']) : '';
    $pronunciation = isset($_PUT['pronunciation']) ? trim($_PUT['pronunciation']) : '';
    $meaning = isset($_PUT['meaning']) ? trim($_PUT['meaning']) : '';
    $example = isset($_PUT['example']) ? trim($_PUT['example']) : '';
    $level = isset($_PUT['level']) ? $_PUT['level'] : 'A1';
    $category = isset($_PUT['category']) ? trim($_PUT['category']) : '';
    
    if ($id && !empty($word) && !empty($meaning)) {
        $query = "UPDATE vocabulary SET 
                  word = :word,
                  pronunciation = :pronunciation,
                  meaning = :meaning,
                  example = :example,
                  level = :level,
                  category = :category
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":word", $word);
        $stmt->bindParam(":pronunciation", $pronunciation);
        $stmt->bindParam(":meaning", $meaning);
        $stmt->bindParam(":example", $example);
        $stmt->bindParam(":level", $level);
        $stmt->bindParam(":category", $category);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute()) {
            echo json_encode(array(
                "success" => true,
                "message" => "Cập nhật thành công!"
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Cập nhật thất bại!"
            ));
        }
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "Thiếu thông tin!"
        ));
    }
}

// DELETE - Xóa từ vựng
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    
    $id = isset($_DELETE['id']) ? intval($_DELETE['id']) : 0;
    
    if ($id) {
        $query = "DELETE FROM vocabulary WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute()) {
            echo json_encode(array(
                "success" => true,
                "message" => "Xóa thành công!"
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Xóa thất bại!"
            ));
        }
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "ID không hợp lệ!"
        ));
    }
}

else {
    echo json_encode(array(
        "success" => false,
        "message" => "Invalid request method"
    ));
}
?>