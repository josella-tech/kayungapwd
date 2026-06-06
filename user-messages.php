<?php
// User Messages API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

// For POST, get user_id from body or POST
$userId = 0;
if ($method === 'POST') {
    $userId = intval($_POST['user_id'] ?? 0);
} else {
    $userId = intval($_GET['user_id'] ?? 0);
}

if (!$userId) {
    session_start();
    $userId = intval($_SESSION['user_id'] ?? 0);
}

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($method === 'GET') {
    $conn = getDB();
    try {
        // Simple query without extra joins
        $stmt = $conn->prepare("SELECT id, sender_id, receiver_id, subject, message, is_from_admin, status, created_at 
            FROM user_messages 
            WHERE sender_id = ? OR receiver_id = ?
            ORDER BY created_at DESC 
            LIMIT 30");
        $stmt->execute([$userId, $userId]);
        $messages = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error loading messages']);
    }
    exit;
}

if ($method === 'POST') {
    // Support JSON, FormData, and regular POST
    $receiverId = 0;
    $message = '';
    
    // Check regular POST first
    if (isset($_POST['receiver_id']) && isset($_POST['message'])) {
        $receiverId = intval($_POST['receiver_id']);
        $message = sanitize($_POST['message']);
    } 
    // Then check JSON input
    elseif (empty($receiverId)) {
        $json = json_decode(file_get_contents('php://input'), true);
        if ($json) {
            $receiverId = intval($json['receiver_id'] ?? 0);
            $message = sanitize($json['message'] ?? '');
        }
    }
    
    // Debug - log what we received
    error_log("POST received: receiver_id=$receiverId, message=$message, POST data=" . json_encode($_POST));
    
    if ($receiverId <= 0 || empty($message)) {
        jsonResponse(['success' => false, 'message' => 'Recipient and message required', 'debug' => $_POST]);
    }
    
    $conn = getDB();
    try {
        $subject = 'Message';
        $stmt = $conn->prepare("INSERT INTO user_messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $receiverId, $subject, $message]);
        
        jsonResponse(['success' => true, 'message' => 'Message sent!']);
    } catch (PDOException $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>