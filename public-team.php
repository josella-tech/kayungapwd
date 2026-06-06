<?php
// Public Team API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

try {
    $stmt = $db->query("SELECT id, name, position, bio, photo, email, phone, order_num FROM team WHERE status = 'active' ORDER BY order_num ASC");
    $team = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'team' => $team
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching team']);
}
?>