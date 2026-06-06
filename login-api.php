<?php
// Admin Login API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/admin_auth.php';

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
    $stmt = $db->prepare("SELECT id, email, password_hash, role, status FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }
    
    if ($admin['status'] === 'inactive') {
        jsonResponse(['success' => false, 'message' => 'Account is inactive'], 403);
    }
    
    if (!password_verify($password, $admin['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }
    
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role'];
    
    jsonResponse([
        'success' => true,
        'message' => 'Login successful!',
        'admin' => [
            'id' => $admin['id'],
            'email' => $admin['email'],
            'role' => $admin['role']
        ],
        'redirect' => 'index.php'
    ]);
    
} catch (PDOException $e) {
    error_log("Admin login error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Login failed. Please try again.'], 500);
}