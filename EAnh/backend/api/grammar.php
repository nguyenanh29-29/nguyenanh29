<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            // Get specific grammar
            $stmt = $conn->prepare("SELECT * FROM grammar WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $grammar = $stmt->fetch();
            
            sendJSON([
                'success' => true,
                'grammar' => $grammar
            ]);
        } else {
            // Get grammar list
            $level = $_GET['level'] ?? null;
            
            $sql = "SELECT * FROM grammar WHERE 1=1";
            $params = [];
            
            if ($level) {
                $sql .= " AND level = ?";
                $params[] = $level;
            }
            
            $sql .= " ORDER BY id ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $grammar = $stmt->fetchAll();
            
            sendJSON([
                'success' => true,
                'grammar' => $grammar
            ]);
        }
        
    } elseif ($method === 'POST') {
        // Save progress
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("
            INSERT INTO user_progress (user_id, activity_type, activity_id, completed)
            VALUES (?, 'grammar', ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $data['grammar_id'],
            $data['completed'] ? 1 : 0
        ]);
        
        sendJSON(['success' => true, 'message' => 'Progress saved']);
    }
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error'], 500);
}
?>