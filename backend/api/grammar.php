<?php
// grammar.php - API xử lý ngữ pháp

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

// GET - Lấy danh sách ngữ pháp
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action === 'getQuiz') {
        // Lấy câu hỏi quiz cho bài ngữ pháp
        $grammarId = isset($_GET['grammarId']) ? intval($_GET['grammarId']) : 0;
        
        if ($grammarId) {
            // Tạo câu hỏi mẫu dựa trên grammar_id
            $query = "SELECT * FROM grammar WHERE id = :id LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $grammarId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $grammar = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Tạo câu hỏi mẫu
                $questions = generateQuizQuestions($grammar);
                
                echo json_encode(array(
                    "success" => true,
                    "questions" => $questions
                ));
            } else {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Không tìm thấy bài học"
                ));
            }
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Grammar ID không hợp lệ"
            ));
        }
    }
    elseif (isset($_GET['id'])) {
        // Lấy chi tiết một bài
        $id = $_GET['id'];
        
        $query = "SELECT * FROM grammar WHERE id = :id LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $grammar = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(array(
                "success" => true,
                "grammar" => $grammar
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Không tìm thấy bài học"
            ));
        }
    } 
    else {
        // Lấy danh sách bài học
        $level = isset($_GET['level']) ? $_GET['level'] : '';
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        $userId = isset($_GET['userId']) ? intval($_GET['userId']) : 0;
        
        $query = "SELECT * FROM grammar WHERE 1=1";
        
        if (!empty($level)) {
            $query .= " AND level = :level";
        }
        
        if (!empty($category)) {
            $query .= " AND category = :category";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 50";
        
        $stmt = $db->prepare($query);
        
        if (!empty($level)) {
            $stmt->bindParam(":level", $level);
        }
        
        if (!empty($category)) {
            $stmt->bindParam(":category", $category);
        }
        
        $stmt->execute();
        $grammar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Lấy thống kê
        $stats_query = "SELECT 
            (SELECT COUNT(*) FROM grammar) as total,
            (SELECT COUNT(DISTINCT content_id) FROM user_progress WHERE user_id = :user_id AND content_type = 'grammar' AND completed = 1) as completed";
        
        $stats_stmt = $db->prepare($stats_query);
        $stats_stmt->bindParam(":user_id", $userId);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(array(
            "success" => true,
            "grammar" => $grammar,
            "stats" => $stats
        ));
    }
}

// POST - Thêm hoặc cập nhật
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'mark_completed') {
        // Đánh dấu đã hoàn thành
        $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $grammarId = isset($_POST['grammarId']) ? intval($_POST['grammarId']) : 0;
        
        if ($userId && $grammarId) {
            $query = "INSERT INTO user_progress (user_id, content_type, content_id, completed, completed_at, created_at) 
                      VALUES (:user_id, 'grammar', :content_id, 1, NOW(), NOW())
                      ON DUPLICATE KEY UPDATE 
                      completed = 1,
                      completed_at = NOW()";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":content_id", $grammarId);
            
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
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Thiếu thông tin!"
            ));
        }
    }
    elseif ($action === 'save_quiz_result') {
        // Lưu kết quả quiz
        $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;
        $grammarId = isset($_POST['grammarId']) ? intval($_POST['grammarId']) : 0;
        $score = isset($_POST['score']) ? floatval($_POST['score']) : 0;
        
        if ($userId && $grammarId) {
            $query = "INSERT INTO user_progress (user_id, content_type, content_id, score, completed, completed_at, created_at) 
                      VALUES (:user_id, 'grammar', :content_id, :score, 1, NOW(), NOW())
                      ON DUPLICATE KEY UPDATE 
                      score = :score,
                      completed = 1,
                      completed_at = NOW()";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->bindParam(":content_id", $grammarId);
            $stmt->bindParam(":score", $score);
            
            if ($stmt->execute()) {
                echo json_encode(array(
                    "success" => true,
                    "message" => "Đã lưu kết quả!"
                ));
            } else {
                echo json_encode(array(
                    "success" => false,
                    "message" => "Không thể lưu kết quả!"
                ));
            }
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Thiếu thông tin!"
            ));
        }
    }
    elseif ($action === 'add_grammar') {
        // Thêm bài mới (admin)
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $level = isset($_POST['level']) ? $_POST['level'] : 'A1';
        $category = isset($_POST['category']) ? trim($_POST['category']) : '';
        $examples = isset($_POST['examples']) ? trim($_POST['examples']) : '';
        
        if (empty($title) || empty($content)) {
            echo json_encode(array(
                "success" => false,
                "message" => "Thiếu thông tin bắt buộc!"
            ));
            exit();
        }
        
        $query = "INSERT INTO grammar (title, content, level, category, examples, created_at) 
                  VALUES (:title, :content, :level, :category, :examples, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":content", $content);
        $stmt->bindParam(":level", $level);
        $stmt->bindParam(":category", $category);
        $stmt->bindParam(":examples", $examples);
        
        if ($stmt->execute()) {
            echo json_encode(array(
                "success" => true,
                "message" => "Thêm bài học thành công!",
                "grammar_id" => $db->lastInsertId()
            ));
        } else {
            echo json_encode(array(
                "success" => false,
                "message" => "Không thể thêm bài học!"
            ));
        }
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "Action không hợp lệ!"
        ));
    }
}

else {
    echo json_encode(array(
        "success" => false,
        "message" => "Invalid request method"
    ));
}

// Hàm tạo câu hỏi quiz mẫu
function generateQuizQuestions($grammar) {
    $questions = array();
    
    // Tạo 5 câu hỏi mẫu dựa trên category
    $category = $grammar['category'];
    $level = $grammar['level'];
    
    if ($category === 'Tenses') {
        $questions = array(
            array(
                'question_text' => 'I _____ to school every day.',
                'option_a' => 'go',
                'option_b' => 'goes',
                'option_c' => 'going',
                'option_d' => 'went',
                'correct_answer' => 'A'
            ),
            array(
                'question_text' => 'She _____ her homework yesterday.',
                'option_a' => 'do',
                'option_b' => 'does',
                'option_c' => 'did',
                'option_d' => 'doing',
                'correct_answer' => 'C'
            ),
            array(
                'question_text' => 'They _____ playing football now.',
                'option_a' => 'is',
                'option_b' => 'are',
                'option_c' => 'was',
                'option_d' => 'were',
                'correct_answer' => 'B'
            ),
            array(
                'question_text' => 'We _____ to Paris last summer.',
                'option_a' => 'go',
                'option_b' => 'went',
                'option_c' => 'gone',
                'option_d' => 'going',
                'correct_answer' => 'B'
            ),
            array(
                'question_text' => 'He _____ English for 5 years.',
                'option_a' => 'study',
                'option_b' => 'studies',
                'option_c' => 'has studied',
                'option_d' => 'studied',
                'correct_answer' => 'C'
            )
        );
    } else {
        // Câu hỏi chung
        $questions = array(
            array(
                'question_text' => 'Choose the correct answer.',
                'option_a' => 'Option A',
                'option_b' => 'Option B',
                'option_c' => 'Option C',
                'option_d' => 'Option D',
                'correct_answer' => 'A'
            ),
            array(
                'question_text' => 'Which sentence is correct?',
                'option_a' => 'I am student',
                'option_b' => 'I am a student',
                'option_c' => 'I am an student',
                'option_d' => 'I student',
                'correct_answer' => 'B'
            ),
            array(
                'question_text' => 'Complete the sentence: He _____ very tall.',
                'option_a' => 'am',
                'option_b' => 'are',
                'option_c' => 'is',
                'option_d' => 'be',
                'correct_answer' => 'C'
            )
        );
    }
    
    return $questions;
}
?>