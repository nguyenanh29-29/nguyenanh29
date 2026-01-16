<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized'], 401);
}

$userId = $_SESSION['user_id'];

try {
    // Get vocabulary count
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT activity_id) as count 
        FROM user_progress 
        WHERE user_id = ? AND activity_type = 'vocabulary' AND completed = 1
    ");
    $stmt->execute([$userId]);
    $vocabCount = $stmt->fetch()['count'] ?? 0;
    
    // Get tests completed
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM test_results WHERE user_id = ?");
    $stmt->execute([$userId]);
    $testsCompleted = $stmt->fetch()['count'] ?? 0;
    
    // Get study streak
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT DATE(created_at)) as streak
        FROM user_progress
        WHERE user_id = ?
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $studyStreak = $stmt->fetch()['streak'] ?? 0;
    
    // Get average score
    $stmt = $conn->prepare("SELECT AVG(score) as avg_score FROM test_results WHERE user_id = ?");
    $stmt->execute([$userId]);
    $avgScore = round($stmt->fetch()['avg_score'] ?? 0, 1);
    
    sendJSON([
        'success' => true,
        'vocab_count' => $vocabCount,
        'tests_completed' => $testsCompleted,
        'study_streak' => $studyStreak,
        'avg_score' => $avgScore
    ]);
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error'], 500);
}
?>