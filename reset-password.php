<?php
// Password Reset API (set new password with token)
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$token = sanitize($input['token'] ?? '');
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if (empty($token) || empty($newPassword)) {
    jsonResponse(['success' => false, 'message' => 'Token and new password are required'], 400);
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $newPassword)) {
    jsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, number and special character'], 400);
}

if ($newPassword !== $confirmPassword) {
    jsonResponse(['success' => false, 'message' => 'Passwords do not match'], 400);
}

$db = getDB();
if (!$db) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

try {
    $stmt = $db->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Invalid or expired reset token'], 400);
    }
    
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmt->execute([$passwordHash, $user['id']]);
    
    jsonResponse(['success' => true, 'message' => 'Password reset successful! Please login with your new password.', 'redirect' => 'login.html']);
    
} catch (PDOException $e) {
    error_log("Password reset error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Reset failed. Please try again.'], 500);
}