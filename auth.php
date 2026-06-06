<?php
// Get Current User API (check if logged in)
require_once __DIR__ . '/../includes/config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'logged_in' => false]);
    exit;
}

$userId = getCurrentUserId();

$db = getDB();
if (!$db) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'logged_in' => false]);
    exit;
}

try {
    $stmt = $db->prepare("SELECT u.id, u.email, u.role, u.status, u.created_at,
        p.first_name, p.last_name, p.photo, p.position, p.district
        FROM users u 
        LEFT JOIN user_profiles p ON u.id = p.user_id 
        WHERE u.id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_unset();
        session_destroy();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'logged_in' => false]);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'photo' => $user['photo'] ? BASE_URL . '/uploads/profiles/' . $user['photo'] : null,
            'position' => $user['position'],
            'district' => $user['district']
        ]
    ]);
    exit;
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'logged_in' => false]);
    exit;
}

$userId = getCurrentUserId();

$db = getDB();
if (!$db) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'logged_in' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT u.id, u.email, u.role, u.status, u.created_at,
        p.first_name, p.last_name, p.photo, p.position, p.district
        FROM users u 
        LEFT JOIN user_profiles p ON u.id = p.user_id 
        WHERE u.id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_unset();
        session_destroy();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'logged_in' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'photo' => $user['photo'] ? BASE_URL . '/uploads/profiles/' . $user['photo'] : null,
            'position' => $user['position'],
            'district' => $user['district']
        ]
    ]);
    exit;
    
} catch (PDOException $e) {
    error_log("Auth check error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'logged_in' => false, 'message' => 'Not logged in']);
    exit;
}