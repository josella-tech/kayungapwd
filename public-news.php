<?php
// Public News API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$limit = intval($_GET['limit'] ?? 10);
$status = sanitize($_GET['status'] ?? 'published');

try {
    $stmt = $db->prepare("SELECT id, title, summary, content, image, author, created_at FROM news WHERE status = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$status, $limit]);
    $news = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'news' => $news
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching news']);
}
?>