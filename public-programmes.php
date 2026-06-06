<?php
// Public Programmes API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$limit = intval($_GET['limit'] ?? 10);
$status = sanitize($_GET['status'] ?? 'active');

try {
    $stmt = $db->prepare("SELECT id, title, summary, description, eligibility, deadline, image, link, created_at FROM programmes WHERE status = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$status, $limit]);
    $programmes = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'programmes' => $programmes
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching programmes']);
}
?>