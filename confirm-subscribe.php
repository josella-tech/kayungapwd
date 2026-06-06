<?php
// Newsletter Confirm API
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
    $stmt = $db->prepare("UPDATE subscribers SET confirmed = 'confirmed', confirmation_token = NULL WHERE confirmation_token = ?");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() === 0) {
        die("Invalid or already confirmed");
    }
    
    header('Location: ' . BASE_URL . '/index.html?subscribed=1');
    
} catch (PDOException $e) {
    error_log("Confirm error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Confirmation failed'], 500);
}