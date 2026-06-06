<?php
// Public Opportunities API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$limit = intval($_GET['limit'] ?? 10);
$status = sanitize($_GET['status'] ?? 'active');

try {
    $stmt = $db->prepare("SELECT id, title, type, organization, description, requirements, location, deadline, link, created_at FROM opportunities WHERE status = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$status, $limit]);
    $opportunities = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'opportunities' => $opportunities
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching opportunities']);
}
?>