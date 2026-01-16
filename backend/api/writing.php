<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            // Get specific writing
            $stmt = $conn->prepare("
                SELECT * FROM user_progress 
                WHERE id = ? AND user_id = ? AND activity_type = 'writing'
            ");
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            
            $writing = $stmt->fetch();
            if ($writing && $writing['answers']) {
                $data = json_decode($writing['answers'], true);
                $writing['topic'] = $data['topic'] ?? '';
                $writing['content'] = $data['content'] ?? '';
                $writing['word_count'] = $data['word_count'] ?? 0;
            }
            
            sendJSON([
                'success' => true,
                'writing' => $writing
            ]);
        } else {
            // Get user's writings
            $stmt = $conn->prepare("
                SELECT * FROM user_progress 
                WHERE user_id = ? AND activity_type = 'writing'
                ORDER BY created_at DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $writings = $stmt->fetchAll();
            
            // Decode answers
            foreach ($writings as &$w) {
                if ($w['answers']) {
                    $data = json_decode($w['answers'], true);
                    $w['topic'] = $data['topic'] ?? '';
                    $w['word_count'] = $data['word_count'] ?? 0;
                }
            }
            
            sendJSON([
                'success' => true,
                'writings' => $writings
            ]);
        }
        
    } elseif ($method === 'POST') {
        // Save writing
        $data = json_decode(file_get_contents('php://input'), true);
        
        $writingData = json_encode([
            'topic' => $data['topic'],
            'content' => $data['content'],
            'word_count' => $data['word_count']
        ]);
        
        $stmt = $conn->prepare("
            INSERT INTO user_progress (user_id, activity_type, answers, completed)
            VALUES (?, 'writing', ?, 1)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $writingData
        ]);
        
        sendJSON(['success' => true, 'message' => 'Writing saved']);
    }
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error'], 500);
}
?>