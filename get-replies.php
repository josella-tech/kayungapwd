<?php
// Get replies for a message - for admin use
header('Content-Type: application/json');

$messageId = intval($_GET['id'] ?? 0);

if ($messageId <= 0) {
    echo json_encode(['success' => false, 'replies' => []]);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
$db = getDB();

try {
    $stmt = $db->prepare("SELECT * FROM message_replies WHERE message_id = ? ORDER BY created_at ASC");
    $stmt->execute([$messageId]);
    $replies = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'replies' => $replies]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'replies' => []]);
}
?>