<?php
require_once '../config/db.php';
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action === 'start' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $db->prepare("SELECT * FROM tests WHERE id = :id");
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $test = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test) {
            $qStmt = $db->prepare("SELECT * FROM questions WHERE test_id = :test_id ORDER BY RAND()");
            $qStmt->bindParam(":test_id", $id);
            $qStmt->execute();
            $test['questions'] = $qStmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "test" => $test]);
        } else {
            echo json_encode(["success" => false, "message" => "Không tìm thấy bài thi"]);
        }
    } else {
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $level = isset($_GET['level']) ? $_GET['level'] : '';
        $query = "SELECT * FROM tests WHERE 1=1";
        if ($type) $query .= " AND type = :type";
        if ($level) $query .= " AND level = :level";
        $query .= " ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        if ($type) $stmt->bindParam(":type", $type);
        if ($level) $stmt->bindParam(":level", $level);
        $stmt->execute();
        echo json_encode(["success" => true, "tests" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
}
?>