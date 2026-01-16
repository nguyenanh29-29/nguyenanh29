<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            // Get specific test
            $stmt = $conn->prepare("SELECT * FROM mock_tests WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $test = $stmt->fetch();
            
            sendJSON([
                'success' => true,
                'test' => $test
            ]);
        } else {
            // Get tests list
            $type = $_GET['type'] ?? null;
            
            $sql = "SELECT * FROM mock_tests WHERE 1=1";
            $params = [];
            
            if ($type) {
                $sql .= " AND test_type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $tests = $stmt->fetchAll();
            
            sendJSON([
                'success' => true,
                'tests' => $tests
            ]);
        }
        
    } elseif ($method === 'POST') {
        // Save test result
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("
            INSERT INTO test_results (user_id, test_id, score, answers, time_spent)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $data['test_id'],
            $data['score'] ?? 0,
            $data['answers'] ?? '{}',
            $data['time_spent'] ?? 0
        ]);
        
        sendJSON(['success' => true, 'message' => 'Test result saved']);
    }
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error'], 500);
}
?>