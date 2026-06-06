<?php
// Leadership/Administration API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

if (!$db) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

try {
    $leadership = $db->query("SELECT * FROM leadership WHERE status = 'active' ORDER BY display_order ASC, position ASC")->fetchAll();
    jsonResponse(['success' => true, 'leadership' => $leadership]);
} catch (PDOException $e) {
    error_log("Leadership fetch error: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to fetch leadership'], 500);
}