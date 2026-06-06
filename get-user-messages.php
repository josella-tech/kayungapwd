<?php
// Get user messages for dashboard - optimized
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$userId = intval($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

$result = ['success' => true, 'messages' => []];
$messages = [];

try {
    // Get chat messages only (faster - single query)
    $chatStmt = $db->prepare("SELECT id, sender_id, receiver_id, subject, message, is_from_admin, status, created_at 
        FROM user_messages 
        WHERE sender_id = ? OR receiver_id = ?
        ORDER BY created_at DESC LIMIT 15");
    $chatStmt->execute([$userId, $userId]);
    
    while ($row = $chatStmt->fetch()) {
        $messages[] = [
            'id' => intval($row['id']),
            'type' => 'chat',
            'subject' => $row['subject'],
            'message' => $row['message'],
            'is_from_admin' => intval($row['is_from_admin']),
            'direction' => $row['sender_id'] == $userId ? 'sent' : 'received',
            'created_at' => $row['created_at'],
            'status' => $row['status']
        ];
    }
    
    $result['messages'] = $messages;
    echo json_encode($result);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>