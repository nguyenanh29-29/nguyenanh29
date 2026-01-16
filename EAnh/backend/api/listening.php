<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            // Get specific listening
            $stmt = $conn->prepare("SELECT * FROM listening WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $listening = $stmt->fetch();
            
            sendJSON([
                'success' => true,
                'listening' => $listening
            ]);
        } else {
            // Get listening list
            $level = $_GET['level'] ?? null;
            
            $sql = "SELECT * FROM listening WHERE 1=1";
            $params = [];
            
            if ($level) {
                $sql .= " AND level = ?";
                $params[] = $level;
            }
            
            $sql .= " ORDER BY RAND() LIMIT 10";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $listening = $stmt->fetchAll();
            
            sendJSON([
                'success' => true,
                'listening' => $listening
            ]);
        }
        
    } elseif ($method === 'POST') {
        // Save progress
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("
            INSERT INTO user_progress (user_id, activity_type, activity_id, score, completed)
            VALUES (?, 'listening', ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $data['listening_id'],
            $data['score'] ?? 0,
            $data['completed'] ? 1 : 0
        ]);
        
        sendJSON(['success' => true, 'message' => 'Progress saved']);
    }
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error'], 500);
}
?>