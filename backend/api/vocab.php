<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get vocabulary list
        $level = $_GET['level'] ?? null;
        $category = $_GET['category'] ?? null;
        
        $sql = "SELECT * FROM vocabulary WHERE 1=1";
        $params = [];
        
        if ($level) {
            $sql .= " AND level = ?";
            $params[] = $level;
        }
        
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        
        $sql .= " ORDER BY RAND() LIMIT 20";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $vocabulary = $stmt->fetchAll();
        
        sendJSON([
            'success' => true,
            'vocabulary' => $vocabulary
        ]);
        
    } elseif ($method === 'POST') {
        // Save progress
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("
            INSERT INTO user_progress (user_id, activity_type, activity_id, score, completed)
            VALUES (?, 'vocabulary', ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $data['vocab_id'],
            $data['score'] ?? 0,
            $data['completed'] ? 1 : 0
        ]);
        
        sendJSON(['success' => true, 'message' => 'Progress saved']);
    }
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error'], 500);
}
?>