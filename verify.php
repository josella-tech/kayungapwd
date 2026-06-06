<?php
// Email Verification API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$token = sanitize($_GET['token'] ?? '');

if (empty($token)) {
    die(jsonResponse(['success' => false, 'message' => 'Invalid token'], 400));
}

$db = getDB();
if (!$db) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

try {
    $stmt = $db->prepare("UPDATE users SET status = 'active', verification_token = NULL WHERE verification_token = ? AND status = 'pending'");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() === 0) {
        jsonResponse(['success' => false, 'message' => 'Invalid or already verified token'], 400);
    }
    
    header('Location: ' . BASE_URL . '/index.html?verified=1');
    
} catch (PDOException $e) {
    error_log("Verification error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Verification failed'], 500);
}