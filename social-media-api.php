<?php
// Social Media Settings API
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'update':
        updateSocialMedia();
        break;
    case 'get':
    default:
        getSocialMedia();
        break;
}

function getSocialMedia() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM social_media LIMIT 1");
    $social = $stmt->fetch();
    echo json_encode(['success' => true, 'social' => $social ?: []]);
}

function updateSocialMedia() {
    $db = getDB();
    
    $facebook = sanitize($_POST['facebook'] ?? '');
    $twitter = sanitize($_POST['twitter'] ?? '');
    $instagram = sanitize($_POST['instagram'] ?? '');
    $linkedin = sanitize($_POST['linkedin'] ?? '');
    $youtube = sanitize($_POST['youtube'] ?? '');
    $whatsapp = sanitize($_POST['whatsapp'] ?? '');
    $tiktok = sanitize($_POST['tiktok'] ?? '');
    
    // Check if record exists
    $stmt = $db->query("SELECT id FROM social_media LIMIT 1");
    $exists = $stmt->fetch();
    
    if ($exists) {
        $sql = "UPDATE social_media SET facebook = ?, twitter = ?, instagram = ?, linkedin = ?, youtube = ?, whatsapp = ?, tiktok = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$facebook, $twitter, $instagram, $linkedin, $youtube, $whatsapp, $tiktok, $exists['id']]);
    } else {
        $sql = "INSERT INTO social_media (facebook, twitter, instagram, linkedin, youtube, whatsapp, tiktok) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$facebook, $twitter, $instagram, $linkedin, $youtube, $whatsapp, $tiktok]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Social media links updated successfully']);
}
?>