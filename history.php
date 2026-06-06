<?php
// District History API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

if (!$db) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

try {
    $history = $db->query("SELECT * FROM district_history WHERE status = 'active' ORDER BY year DESC, display_order ASC")->fetchAll();
    jsonResponse(['success' => true, 'history' => $history]);
} catch (PDOException $e) {
    error_log("History fetch error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch history'], 500);
}