<?php
// Subscriber Notifications API - Check for message replies
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$email = sanitize($_GET['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}

$db = getDB();

try {
    // Get unread notifications for this subscriber
    $stmt = $db->prepare("SELECT n.*, m.name as sender_name, m.subject as original_subject 
                          FROM reply_notifications n 
                          JOIN messages m ON n.message_id = m.id 
                          WHERE n.email = ? AND n.read_status = 'unread' 
                          ORDER BY n.created_at DESC");
    $stmt->execute([$email]);
    $notifications = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true, 
        'has_notifications' => count($notifications) > 0,
        'count' => count($notifications),
        'notifications' => $notifications
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error checking notifications']);
}
?>