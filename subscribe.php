<?php
// Newsletter Subscribe API
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
    $stmt = $db->prepare("SELECT id, confirmed FROM subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['confirmed'] === 'confirmed') {
            jsonResponse(['success' => false, 'message' => 'Email already subscribed'], 400);
        }
        
        $confirmationToken = generateToken();
        $stmt = $db->prepare("UPDATE subscribers SET confirmation_token = ? WHERE id = ?");
        $stmt->execute([$confirmationToken, $existing['id']]);
        
        $confirmLink = BASE_URL . "/backend/api/confirm-subscribe.php?token=" . $confirmationToken;
        $message = "<html><body><h2>Confirm Newsletter Subscription - Kayunga District Youth Council</h2>";
        $message .= "<p>Click the link below to confirm your subscription:</p>";
        $message .= "<p><a href='$confirmLink'>Confirm Subscription</a></p></body></html>";
        
        sendEmail($email, "Confirm Newsletter - Kayunga District Youth Council", $message);
        
        jsonResponse(['success' => true, 'message' => 'Please check your email to confirm subscription']);
    }
    
    $confirmationToken = generateToken();
    $stmt = $db->prepare("INSERT INTO subscribers (email, confirmation_token) VALUES (?, ?)");
    $stmt->execute([$email, $confirmationToken]);
    
    $confirmLink = BASE_URL . "/backend/api/confirm-subscribe.php?token=" . $confirmationToken;
    $message = "<html><body><h2>Welcome to Kayunga District Youth Council Newsletter!</h2>";
    $message .= "<p>Please confirm your subscription by clicking the link below:</p>";
    $message .= "<p><a href='$confirmLink'>Confirm Subscription</a></p></body></html>";
    
    sendEmail($email, "Confirm Newsletter - Kayunga District Youth Council", $message);
    
    jsonResponse(['success' => true, 'message' => 'Please check your email to confirm subscription']);
    
} catch (PDOException $e) {
    error_log("Subscribe error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Subscription failed. Please try again.'], 500);
}