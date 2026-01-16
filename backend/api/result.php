<?php
require_once '../config/db.php';

if (!isLoggedIn()) {
    sendJSON(['success' => false, 'message' => 'Unauthorized'], 401);
}

$userId = $_SESSION['user_id'];

try {
    $type = $_GET['type'] ?? null;
    $days = $_GET['days'] ?? 30;
    
    // Build where clause
    $whereClause = "user_id = ?";
    $params = [$userId];
    
    if ($type) {
        $whereClause .= " AND activity_type = ?";
        $params[] = $type;
    }
    
    if ($days !== 'all') {
        $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params[] = $days;
    }
    
    // Get stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_tests,
            AVG(score) as avg_score,
            MAX(score) as highest_score,
            SUM(time_spent) as total_time
        FROM user_progress
        WHERE $whereClause AND completed = 1
    ");
    $stmt->execute($params);
    $stats = $stmt->fetch();
    
    // Get results list
    $stmt = $conn->prepare("
        SELECT 
            up.*,
            CASE 
                WHEN up.activity_type = 'test' THEN mt.title
                WHEN up.activity_type = 'vocabulary' THEN v.word
                WHEN up.activity_type = 'grammar' THEN g.title
                WHEN up.activity_type = 'listening' THEN l.title
                WHEN up.activity_type = 'reading' THEN r.title
                ELSE 'Unknown'
            END as test_name
        FROM user_progress up
        LEFT JOIN mock_tests mt ON up.activity_type = 'test' AND up.activity_id = mt.id
        LEFT JOIN vocabulary v ON up.activity_type = 'vocabulary' AND up.activity_id = v.id
        LEFT JOIN grammar g ON up.activity_type = 'grammar' AND up.activity_id = g.id
        LEFT JOIN listening l ON up.activity_type = 'listening' AND up.activity_id = l.id
        LEFT JOIN reading r ON up.activity_type = 'reading' AND up.activity_id = r.id
        WHERE $whereClause AND up.completed = 1
        ORDER BY up.created_at DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    // Get analysis
    $stmt = $conn->prepare("
        SELECT 
            activity_type,
            AVG(score) as avg_score,
            COUNT(*) as count
        FROM user_progress
        WHERE user_id = ? AND completed = 1
        GROUP BY activity_type
    ");
    $stmt->execute([$userId]);
    $breakdown = $stmt->fetchAll();
    
    $strengths = [];
    $weaknesses = [];
    
    foreach ($breakdown as $item) {
        if ($item['avg_score'] >= 80) {
            $strengths[] = ucfirst($item['activity_type']) . " (Điểm TB: " . round($item['avg_score'], 1) . "%)";
        } elseif ($item['avg_score'] < 60) {
            $weaknesses[] = ucfirst($item['activity_type']) . " (Điểm TB: " . round($item['avg_score'], 1) . "%)";
        }
    }
    
    sendJSON([
        'success' => true,
        'stats' => [
            'total_tests' => $stats['total_tests'] ?? 0,
            'avg_score' => round($stats['avg_score'] ?? 0, 1),
            'highest_score' => round($stats['highest_score'] ?? 0, 1),
            'total_time' => $stats['total_time'] ?? 0
        ],
        'results' => $results,
        'analysis' => [
            'strengths' => $strengths,
            'weaknesses' => $weaknesses
        ]
    ]);
    
} catch (PDOException $e) {
    sendJSON(['success' => false, 'message' => 'Database error'], 500);
}
?>