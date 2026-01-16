<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

try {
    if ($method === 'GET') {
        // Get user profile
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Remove sensitive data
        unset($user['password']);
        
        sendJSON([
            'success' => true,
            'user' => $user
        ]);
        
    } elseif ($method === 'POST') {
        // Update profile
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conn->prepare("
            UPDATE users 
            SET full_name = ?,
                phone = ?,
                birthdate = ?,
                goal = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $data['full_name'] ?? '',
            $data['phone'] ?? null,
            $data['birthdate'] ?? null,
            $data['goal'] ?? null,
            $userId
        ]);
        
        sendJSON(['success' => true, 'message' => 'Profile updated']);
    }
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error'], 500);
}
?>