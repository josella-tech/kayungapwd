<?php
// User Registration API with Welcome Message
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
$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Email and password are required'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Invalid email format'], 400);
}

if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
    jsonResponse(['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, number and special character'], 400);
}

if ($password !== $confirmPassword) {
    jsonResponse(['success' => false, 'message' => 'Passwords do not match'], 400);
}

$db = getDB();
if (!$db) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

try {
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Email already registered'], 400);
    }
    
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $verificationToken = generateToken();
    
    $stmt = $db->prepare("INSERT INTO users (email, password_hash, verification_token, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$email, $passwordHash, $verificationToken]);
    
    $userId = $db->lastInsertId();
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = 'user';
    
    $verificationLink = BASE_URL . "/backend/api/verify.php?token=" . $verificationToken;
    $message = "<html><body><h2>Welcome to Kayunga District Youth Council!</h2>";
    $message .= "<p>Please verify your email by clicking the link below:</p>";
    $message .= "<p><a href='$verificationLink'>Verify Email</a></p>";
    $message .= "<p>Or copy this link: $verificationLink</p></body></html>";
    
    sendEmail($email, "Verify your email - Kayunga District Youth Council", $message);
    
    $welcomeTitle = "Welcome to Kayunga District Youth Council!";
    $welcomeMessage = <<<WELCOME
    Welcome to Kayunga District Youth Council!
    
    You are now part of a growing community of young leaders dedicated to transforming our district.
    
    Here's what you can do:
    ✦ Complete your profile and connect with other youth
    ✦ Stay updated on opportunities, programs, and events
    ✦ Share your ideas and contribute to district development
    ✦ Access training, scholarships, and employment opportunities
    
    Your voice matters in building a better Kayunga!
    
    Let's build our future together!
    - The Kayunga Youth Council Team
WELCOME;
    
    $features = [
        "Complete your profile and connect with youth across Kayunga",
        "Stay updated on opportunities, programs, and events",
        "Access training, scholarships, and job opportunities",
        "Share your ideas and contribute to district development",
        "Your voice matters in building a better Kayunga!"
    ];
    
    jsonResponse([
        'success' => true, 
        'welcome_title' => $welcomeTitle,
        'welcome_message' => $welcomeMessage,
        'features' => $features,
        'user_id' => $userId,
        'redirect' => 'edit-profile.html'
    ]);
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Registration failed. Please try again.'], 500);
}