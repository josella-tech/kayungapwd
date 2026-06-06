<?php
// District History API (Public)
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

try {
    $history = $db->query("SELECT id, title, content, year, category, image, display_order FROM district_history WHERE status = 'active' ORDER BY display_order ASC, year ASC")->fetchAll();
    echo json_encode(['success' => true, 'history' => $history]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching history']);
}
?>