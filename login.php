<?php
// User Login API with Welcome Message
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

if (empty($email) || empty($password)) {
    jsonResponse(['success' => false, 'message' => 'Email and password are required'], 400);
}

$db = getDB();
if (!$db) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

try {
    $stmt = $db->prepare("SELECT id, email, password_hash, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }
    
    if ($user['status'] === 'suspended') {
        jsonResponse(['success' => false, 'message' => 'Account suspended. Contact support.'], 403);
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }
    
    $stmt = $db->prepare("SELECT id, first_name, last_name, position, district FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch();
    
    $firstName = $profile['first_name'] ?? '';
    $hasProfile = !empty($firstName);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $user['role'];
    
    $logStmt = $db->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (?, 'login', ?)");
    $logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '']);
    
    $welcomeMessages = [
        "Welcome back to Kayunga District Youth Council! We're glad to have you here.",
        "Hello there! Great to see you again at Kayunga Youth Council.",
        "Welcome home! Your presence makes our community stronger.",
        "Hey! Nice to have you back. Let's make a difference together!"
    ];
    $welcomeMessage = $welcomeMessages[array_rand($welcomeMessages)];
    
    if ($hasProfile && $profile['position']) {
        $welcomeMessage = "Welcome back, {$profile['position']}! Great to have you with us at Kayunga Youth Council.";
    } elseif ($hasProfile) {
        $welcomeMessage = "Welcome back, {$firstName}! Let's continue building our community together.";
    }
    
    jsonResponse([
        'success' => true,
        'welcome_message' => $welcomeMessage,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'first_name' => $firstName,
            'has_profile' => $hasProfile
        ],
        'redirect' => 'user-dashboard.html'
    ]);
    
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Server error. Please try again.'], 500);
}