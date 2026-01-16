<?php
require_once '../config/db.php';

if (!isAdmin()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized'], 401);
}

try {
    // Total users
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $totalUsers = $stmt->fetch()['count'];
    
    // Total vocabulary
    $stmt = $conn->query("SELECT COUNT(*) as count FROM vocabulary");
    $totalVocab = $stmt->fetch()['count'];
    
    // Total tests
    $stmt = $conn->query("SELECT COUNT(*) as count FROM mock_tests");
    $totalTests = $stmt->fetch()['count'];
    
    // Active today
    $stmt = $conn->query("
        SELECT COUNT(DISTINCT user_id) as count 
        FROM user_progress 
        WHERE DATE(created_at) = CURDATE()
    ");
    $activeToday = $stmt->fetch()['count'];
    
    sendJSON([
        'success' => true,
        'total_users' => $totalUsers,
        'total_vocab' => $totalVocab,
        'total_tests' => $totalTests,
        'active_today' => $activeToday
    ]);
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error'], 500);
}
?>