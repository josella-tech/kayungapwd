<?php
// Public Social Media API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

try {
    $stmt = $db->query("SELECT facebook, twitter, instagram, linkedin, youtube, whatsapp, tiktok FROM social_media LIMIT 1");
    $social = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'social' => $social ?: []
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching social media']);
}
?>