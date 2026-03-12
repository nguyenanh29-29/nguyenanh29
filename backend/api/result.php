<?php
// result.php - API xử lý kết quả và thống kê

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

// GET - Lấy thống kê
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    switch ($action) {
        case 'stats':
            // Thống kê của user
            $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
            
            if ($userId) {
                // Tổng từ đã học
                $vocab_query = "SELECT COUNT(*) as totalWords FROM user_vocabulary WHERE user_id = :user_id";
                $vocab_stmt = $db->prepare($vocab_query);
                $vocab_stmt->bindParam(":user_id", $userId);
                $vocab_stmt->execute();
                $vocab_result = $vocab_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Số bài test đã hoàn thành
                $test_query = "SELECT COUNT(*) as completedTests FROM test_results WHERE user_id = :user_id";
                $test_stmt = $db->prepare($test_query);
                $test_stmt->bindParam(":user_id", $userId);
                $test_stmt->execute();
                $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Thời gian học (giờ)
                $time_query = "SELECT SUM(time_spent) as totalTime FROM user_progress WHERE user_id = :user_id";
                $time_stmt = $db->prepare($time_query);
                $time_stmt->bindParam(":user_id", $userId);
                $time_stmt->execute();
                $time_result = $time_stmt->fetch(PDO::FETCH_ASSOC);
                $studyTime = ceil(($time_result['totalTime'] ?? 0) / 3600);
                
                // Tính trình độ dựa trên điểm trung bình
                $level_query = "SELECT AVG(score) as avgScore FROM test_results WHERE user_id = :user_id";
                $level_stmt = $db->prepare($level_query);
                $level_stmt->bindParam(":user_id", $userId);
                $level_stmt->execute();
                $level_result = $level_stmt->fetch(PDO::FETCH_ASSOC);
                $avgScore = $level_result['avgScore'] ?? 0;
                
                $level = 'A1';
                if ($avgScore >= 90) $level = 'C2';
                elseif ($avgScore >= 80) $level = 'C1';
                elseif ($avgScore >= 70) $level = 'B2';
                elseif ($avgScore >= 60) $level = 'B1';
                elseif ($avgScore >= 50) $level = 'A2';
                
                // Tính phần trăm hoàn thành (dựa trên số bài đã hoàn thành)
                $progress_query = "SELECT 
                    COUNT(*) as completed,
                    (SELECT COUNT(*) FROM tests) as total
                    FROM test_results 
                    WHERE user_id = :user_id";
                $progress_stmt = $db->prepare($progress_query);
                $progress_stmt->bindParam(":user_id", $userId);
                $progress_stmt->execute();
                $progress_result = $progress_stmt->fetch(PDO::FETCH_ASSOC);
                
                $progress = 0;
                if ($progress_result['total'] > 0) {
                    $progress = round(($progress_result['completed'] / $progress_result['total']) * 100);
                }
                
                echo json_encode(array(
                    "success" => true,
                    "stats" => array(
                        "totalWords" => $vocab_result['totalWords'] ?? 0,
                        "completedTests" => $test_result['completedTests'] ?? 0,
                        "studyTime" => $studyTime,
                        "level" => $level,
                        "progress" => $progress
                    )
                ));
            } else {
                echo json_encode(array(
                    "success" => false,
                    "message" => "User ID không hợp lệ"
                ));
            }
            break;
            
        case 'adminStats':
            // Thống kê cho admin
            $stats = array();
            
            // Tổng số users
            $users_query = "SELECT COUNT(*) as total FROM users";
            $users_stmt = $db->query($users_query);
            $stats['totalUsers'] = $users_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Tổng số từ vựng
            $vocab_query = "SELECT COUNT(*) as total FROM vocabulary";
            $vocab_stmt = $db->query($vocab_query);
            $stats['totalVocab'] = $vocab_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Tổng số bài ngữ pháp
            $grammar_query = "SELECT COUNT(*) as total FROM grammar";
            $grammar_stmt = $db->query($grammar_query);
            $stats['totalGrammar'] = $grammar_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Tổng số bài test
            $test_query = "SELECT COUNT(*) as total FROM tests";
            $test_stmt = $db->query($test_query);
            $stats['totalTests'] = $test_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            echo json_encode(array(
                "success" => true,
                "stats" => $stats
            ));
            break;
            
        case 'getUsers':
            // Lấy danh sách users cho admin
            $query = "SELECT id, fullname, email, role, created_at, last_login 
                      FROM users 
                      ORDER BY created_at DESC 
                      LIMIT 100";
            $stmt = $db->query($query);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(array(
                "success" => true,
                "users" => $users
            ));
            break;
            
        case 'getProgress':
            // Lấy tiến độ học tập theo loại
            $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
            $type = isset($_GET['type']) ? $_GET['type'] : '';
            
            if ($userId && $type) {
                $query = "SELECT * FROM user_progress 
                          WHERE user_id = :user_id AND content_type = :type 
                          ORDER BY created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":user_id", $userId);
                $stmt->bindParam(":type", $type);
                $stmt->execute();
                $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(array(
                    "success" => true,
                    "progress" => $progress
                ));
            } else {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Thiếu thông tin"
                ));
            }
            break;
            
        case 'testResults':
            // Lấy kết quả thi
            $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
            
            if ($userId) {
                $query = "SELECT tr.*, t.title, t.type 
                          FROM test_results tr
                          JOIN tests t ON tr.test_id = t.id
                          WHERE tr.user_id = :user_id
                          ORDER BY tr.completed_at DESC
                          LIMIT 20";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":user_id", $userId);
                $stmt->execute();
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(array(
                    "success" => true,
                    "results" => $results
                ));
            } else {
                echo json_encode(array(
                    "success" => false,
                    "message" => "User ID không hợp lệ"
                ));
            }
            break;
            
        default:
            echo json_encode(array(
                "success" => false,
                "message" => "Action không hợp lệ"
            ));
    }
}

// POST - Lưu kết quả
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if ($action === 'saveProgress') {
        // Lưu tiến độ học tập
        $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $contentType = isset($_POST['type']) ? $_POST['type'] : '';
        $data = isset($_POST['data']) ? $_POST['data'] : '';
        
        if ($userId && $contentType) {
            $query = "INSERT INTO user_progress (user_id, content_type, created_at) 
                      VALUES (:user_id, :content_type, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":content_type", $contentType);
            
            if ($stmt->execute()) {
                echo json_encode(array(
                    "success" => true,
                    "message" => "Đã lưu tiến độ"
                ));
            } else {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Không thể lưu tiến độ"
                ));
            }
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Thiếu thông tin"
            ));
        }
    }
    elseif ($action === 'saveTestResult') {
        // Lưu kết quả thi
        $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $testId = isset($_POST['testId']) ? intval($_POST['testId']) : 0;
        $score = isset($_POST['score']) ? floatval($_POST['score']) : 0;
        $totalQuestions = isset($_POST['totalQuestions']) ? intval($_POST['totalQuestions']) : 0;
        $correctAnswers = isset($_POST['correctAnswers']) ? intval($_POST['correctAnswers']) : 0;
        $timeSpent = isset($_POST['timeSpent']) ? intval($_POST['timeSpent']) : 0;
        $answers = isset($_POST['answers']) ? $_POST['answers'] : '';
        
        if ($userId && $testId) {
            $query = "INSERT INTO test_results 
                      (user_id, test_id, score, total_questions, correct_answers, time_spent, answers, completed_at) 
                      VALUES (:user_id, :test_id, :score, :total_questions, :correct_answers, :time_spent, :answers, NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":test_id", $testId);
            $stmt->bindParam(":score", $score);
            $stmt->bindParam(":total_questions", $totalQuestions);
            $stmt->bindParam(":correct_answers", $correctAnswers);
            $stmt->bindParam(":time_spent", $timeSpent);
            $stmt->bindParam(":answers", $answers);
            
            if ($stmt->execute()) {
                echo json_encode(array(
                    "success" => true,
                    "message" => "Đã lưu kết quả thi",
                    "result_id" => $db->lastInsertId()
                ));
            } else {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Không thể lưu kết quả"
                ));
            }
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Thiếu thông tin"
            ));
        }
    }
    else {
        echo json_encode(array(
            "success" => false,
            "message" => "Action không hợp lệ"
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