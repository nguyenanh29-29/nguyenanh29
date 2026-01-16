<?php
require_once '../config/db.php';

if (!isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized'], 401);
}

try {
    $stmt = $conn->query("
        SELECT id, email, full_name, role, created_at 
        FROM users 
        ORDER BY created_at DESC
    ");
    
    $users = $stmt->fetchAll();
    
    sendJSON([
        'success' => true,
        'users' => $users
    ]);
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error'], 500);
}
?>