<?php
// Get replies for a chat message
header('Content-Type: application/json');

$messageId = intval($_GET['message_id'] ?? 0);

if ($messageId <= 0) {
    echo json_encode(['success' => false, 'replies' => []]);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
$db = getDB();

try {
    // Get the original message
    $stmt = $db->prepare("SELECT * FROM user_messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $message = $stmt->fetch();
    
    $replies = [];
    
    if ($message && $message['sender_id'] > 0) {
        // Get all messages between this user and admin (sender_id = user, receiver_id = 0 and vice versa)
        $userId = $message['sender_id'];
        $chatStmt = $db->prepare("
            SELECT * FROM user_messages 
            WHERE (sender_id = ? AND receiver_id = 0) OR (sender_id = 0 AND receiver_id = ?)
            ORDER BY created_at ASC
        ");
        $chatStmt->execute([$userId, $userId]);
        $chatMessages = $chatStmt->fetchAll();
        
        // Get the original message and find messages AFTER it (replies from admin)
        $foundOriginal = false;
        foreach ($chatMessages as $chat) {
            if ($chat['id'] == $messageId) {
                $foundOriginal = true;
                continue;
            }
            if ($foundOriginal && $chat['sender_id'] == 0) {
                $replies[] = [
                    'id' => $chat['id'],
                    'reply_text' => $chat['message'],
                    'admin_email' => 'admin@kayungayouth.go.ug',
                    'created_at' => $chat['created_at']
                ];
            }
        }
    }
    
    echo json_encode(['success' => true, 'replies' => $replies]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'replies' => []]);
}
?>