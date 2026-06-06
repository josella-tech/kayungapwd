<?php
// Database configuration
// Update these values for your hosting environment

define('DB_HOST', 'localhost');
define('DB_NAME', 'kayunga_youth');  // Create this database in your hosting panel
define('DB_USER', 'root');          // Your database username
define('DB_PASS', '');              // Your database password

// Application configuration
define('BASE_URL', 'http://localhost/KAYUNGA%20DISTRICT%20WEBSITE');
define('UPLOAD_PATH', 'C:/xampp/htdocs/KAYUNGA DISTRICT WEBSITE/uploads/');
define('PROFILE_PHOTO_MAX_SIZE', 10485760); // 10MB
define('ALLOWED_PHOTO_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '');
session_start();

// Database connection function
function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            $db = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $db;
}

// Helper function to sanitize input
function sanitize($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Helper function to return JSON response
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
}

// Helper function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Helper function to upload profile photo
function uploadProfilePhoto($file) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file upload'];
    }
    
    if ($file['size'] > PROFILE_PHOTO_MAX_SIZE) {
        return ['success' => false, 'message' => 'File too large. Max 10MB allowed'];
    }
    
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, ALLOWED_PHOTO_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type. JPG, PNG, GIF, WebP allowed'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = uniqid('profile_') . '.' . $extension;
    $targetPath = UPLOAD_PATH . 'profiles/' . $newFilename;
    
    if (!is_dir(UPLOAD_PATH . 'profiles')) {
        mkdir(UPLOAD_PATH . 'profiles', 0755, true);
    }
    
    // Try move_uploaded_file first, fallback to copy for Windows
    $moved = false;
    if (function_exists('move_uploaded_file') && is_uploaded_file($file['tmp_name'])) {
        $moved = @move_uploaded_file($file['tmp_name'], $targetPath);
    }
    
    if (!$moved && is_file($file['tmp_name'])) {
        $moved = @copy($file['tmp_name'], $targetPath);
    }
    
    if ($moved) {
        return ['success' => true, 'filename' => $newFilename];
    }
    
    error_log("Upload failed. Temp: " . $file['tmp_name'] . ", Target: " . $targetPath);
    return ['success' => false, 'message' => 'Failed to upload file'];
}

// Generate random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Send email (requires mail server configuration)
// For production, use PHPMailer or similar
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@kayungayouth.go.ug" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}