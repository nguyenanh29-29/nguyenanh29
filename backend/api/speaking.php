speaking.php - Tương tự, chỉ thay 'writing' bằng 'speaking'
<?php
require_once '../config/db.php';
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $db->prepare("SELECT * FROM speaking WHERE id = :id");
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        echo json_encode(["success" => true, "speaking" => $stmt->fetch(PDO::FETCH_ASSOC)]);
    } else {
        $level = isset($_GET['level']) ? $_GET['level'] : '';
        $query = "SELECT * FROM speaking WHERE 1=1";
        if ($level) $query .= " AND level = :level";
        $query .= " ORDER BY created_at DESC LIMIT 50";
        $stmt = $db->prepare($query);
        if ($level) $stmt->bindParam(":level", $level);
        $stmt->execute();
        echo json_encode(["success" => true, "speaking" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
}
?>
*/
?>