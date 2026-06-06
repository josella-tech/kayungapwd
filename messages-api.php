<?php
// Messages Management API
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'reply':
        replyToMessage();
        break;
    case 'mark_read':
        markAsRead();
        break;
    case 'delete':
        deleteMessage();
        break;
    case 'get':
        getMessage();
        break;
    case 'list':
    default:
        listMessages();
        break;
}

function listMessages() {
    $db = getDB();
    $status = sanitize($_GET['status'] ?? '');
    
    $sql = "SELECT * FROM messages";
    $params = [];
    
    if ($status) {
        $sql .= " WHERE status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'messages' => $stmt->fetchAll()]);
}

function getMessage() {
    $db = getDB();
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->execute([$id]);
    $message = $stmt->fetch();
    
    if ($message) {
        // Get replies
        $replyStmt = $db->prepare("SELECT * FROM message_replies WHERE message_id = ? ORDER BY created_at ASC");
        $replyStmt->execute([$id]);
        $message['replies'] = $replyStmt->fetchAll();
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Message not found']);
    }
}

function markAsRead() {
    $db = getDB();
    $id = intval($_POST['id'] ?? 0);
    
    try {
        $stmt = $db->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Message marked as read']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
}

function replyToMessage() {
    $db = getDB();
    
    $messageId = intval($_POST['message_id'] ?? 0);
    $replyText = sanitize($_POST['reply'] ?? '');
    $recipientEmail = sanitize($_POST['email'] ?? '');
    
    if ($messageId <= 0 || empty($replyText)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }
    
    // Get subscriber info if exists
    if (empty($recipientEmail)) {
        $stmt = $db->prepare("SELECT email FROM subscribers WHERE email = (SELECT email FROM messages WHERE id = ?)");
        $stmt->execute([$messageId]);
        $subscriber = $stmt->fetch();
        if ($subscriber) {
            $recipientEmail = $subscriber['email'];
        }
    }
    
    try {
        // Save reply
        $stmt = $db->prepare("INSERT INTO message_replies (message_id, reply_text, admin_email) VALUES (?, ?, ?)");
        $stmt->execute([$messageId, $replyText, $_SESSION['admin_email']]);
        
        // Update message status
        $updateStmt = $db->prepare("UPDATE messages SET status = 'replied' WHERE id = ?");
        $updateStmt->execute([$messageId]);
        
        // Save reply notification for subscriber
        if ($recipientEmail) {
            $notifStmt = $db->prepare("INSERT INTO reply_notifications (email, message_id, reply_text) VALUES (?, ?, ?)");
            $notifStmt->execute([$recipientEmail, $messageId, $replyText]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Reply sent successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to send reply']);
    }
}

function deleteMessage() {
    $db = getDB();
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        return;
    }
    
    try {
        // Delete replies first
        $stmt = $db->prepare("DELETE FROM message_replies WHERE message_id = ?");
        $stmt->execute([$id]);
        
        // Delete notifications
        $notifStmt = $db->prepare("DELETE FROM reply_notifications WHERE message_id = ?");
        $notifStmt->execute([$id]);
        
        // Delete message
        $msgStmt = $db->prepare("DELETE FROM messages WHERE id = ?");
        $msgStmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
    }
}
?>