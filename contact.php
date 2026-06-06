<?php
// Contact Form API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$name = sanitize($input['name'] ?? '');
$email = sanitize($input['email'] ?? '');
$phone = sanitize($input['phone'] ?? '');
$subject = sanitize($input['subject'] ?? '');
$message = sanitize($input['message'] ?? '');
$userId = $input['user_id'] ?? null;

if (empty($name) || empty($email) || empty($message)) {
    jsonResponse(['success' => false, 'message' => 'Name, email and message are required'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Invalid email format'], 400);
}

$db = getDB();
if (!$db) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

try {
    // Check if email is registered user
    $userCheck = $db->prepare("SELECT id, first_name FROM users WHERE email = ?");
    $userCheck->execute([$email]);
    $user = $userCheck->fetch();
    
    if ($user) {
        // Registered user - link message to user
        $registeredUserId = $user['id'];
    } else {
        $registeredUserId = null;
    }
    
    $stmt = $db->prepare("INSERT INTO messages (user_id, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$registeredUserId, $name, $email, $phone, $subject, $message]);
    
    $autoReply = "<html><body><h2>Thank you for contacting Kayunga District Youth Council</h2>";
    $autoReply .= "<p>Dear $name,</p>";
    
    if ($registeredUserId) {
        $autoReply .= "<p>We have received your message and will get back to you within 24 hours.</p>";
        $autoReply .= "<p>You can also track your message in your dashboard.</p>";
    } else {
        $autoReply .= "<p>We have received your message. To track your messages and get faster responses, we invite you to register on our platform.</p>";
        $autoReply .= "<p><a href='https://kayungayouth.go.ug/register.html' style='background:#d97706;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;'>Register Here</a></p>";
    }
    
    $autoReply .= "<p>Best regards,<br>Kayunga District Youth Council</p></body></html>";
    
    sendEmail($email, "We received your message - Kayunga District Youth Council", $autoReply);
    
    if ($registeredUserId) {
        jsonResponse(['success' => true, 'message' => 'Message sent! Our team will respond within 24 hours.']);
    } else {
        jsonResponse(['success' => true, 'message' => 'Message sent! To track your message, please register. Response will be within 24 hours.']);
    }
    
} catch (PDOException $e) {
    error_log("Contact message error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to send message. Please try again.'], 500);
}