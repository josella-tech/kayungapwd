<?php
// User Logout API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
if (isLoggedIn() && $db) {
    $logStmt = $db->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (?, 'logout', ?)");
    $logStmt->execute([getCurrentUserId(), $_SERVER['REMOTE_ADDR'] ?? '']);
}

session_unset();
session_destroy();

jsonResponse(['success' => true, 'message' => 'Logged out successfully', 'redirect' => 'index.html']);