<?php
// Password Reset Request API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = sanitize($input['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Valid email is required'], 400);
}

$db = getDB();
if (!$db) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

try {
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['success' => true, 'message' => 'If email exists, reset link sent'], 200);
    }
    
    $resetToken = generateToken();
    $resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
    $stmt->execute([$resetToken, $resetExpires, $user['id']]);
    
    $resetLink = BASE_URL . "/reset-password.html?token=" . $resetToken;
    $message = "<html><body><h2>Password Reset - Kayunga District Youth Council</h2>";
    $message .= "<p>Click the link below to reset your password:</p>";
    $message .= "<p><a href='$resetLink'>Reset Password</a></p>";
    $message .= "<p>Or copy this link: $resetLink</p>";
    $message .= "<p>This link expires in 1 hour.</p></body></html>";
    
    sendEmail($email, "Password Reset - Kayunga District Youth Council", $message);
    
    jsonResponse(['success' => true, 'message' => 'If email exists, reset link sent']);
    
} catch (PDOException $e) {
    error_log("Password reset error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Request failed. Please try again.'], 500);
}