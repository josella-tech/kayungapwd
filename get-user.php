<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare("SELECT u.id, u.email, u.role, u.status, u.created_at,
        p.first_name, p.last_name, p.gender, p.phone,
        p.district, p.subcounty, p.photo, p.position, p.bio
        FROM users u 
        LEFT JOIN user_profiles p ON u.id = p.user_id 
        WHERE u.id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>