<?php
// Profile Photo Upload API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please login first'], 401);
}

$userId = getCurrentUserId();
$db = getDB();

if (!$db) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
}

if (!isset($_FILES['photo'])) {
    jsonResponse(['success' => false, 'message' => 'No file uploaded'], 400);
}

$file = $_FILES['photo'];
$result = uploadProfilePhoto($file);

if (!$result['success']) {
    jsonResponse(['success' => false, 'message' => $result['message']], 400);
}

try {
    $stmt = $db->prepare("SELECT photo FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
    
    if ($profile && $profile['photo'] && file_exists(UPLOAD_PATH . 'profiles/' . $profile['photo'])) {
        unlink(UPLOAD_PATH . 'profiles/' . $profile['photo']);
    }
    
    $checkStmt = $db->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
    $checkStmt->execute([$userId]);
    
    if ($checkStmt->fetch()) {
        $updateStmt = $db->prepare("UPDATE user_profiles SET photo = ? WHERE user_id = ?");
        $updateStmt->execute([$result['filename'], $userId]);
    } else {
        $insertStmt = $db->prepare("INSERT INTO user_profiles (user_id, photo) VALUES (?, ?)");
        $insertStmt->execute([$userId, $result['filename']]);
    }
    
    $photoUrl = BASE_URL . '/uploads/profiles/' . $result['filename'];
    
    jsonResponse([
        'success' => true,
        'message' => 'Photo uploaded successfully!',
        'photo' => $photoUrl,
        'filename' => $result['filename']
    ]);
    
} catch (PDOException $e) {
    error_log("Photo upload error: " . $e->getMessage());
    if (file_exists(UPLOAD_PATH . 'profiles/' . $result['filename'])) {
        unlink(UPLOAD_PATH . 'profiles/' . $result['filename']);
    }
    jsonResponse(['success' => false, 'message' => 'Failed to save photo'], 500);
}