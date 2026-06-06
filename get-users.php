<?php
// Get Users List API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

session_start();
$userId = $_SESSION['user_id'] ?? 0;

$conn = getDB();

$stmt = $conn->prepare("SELECT u.id, u.email, p.first_name, p.last_name, p.position FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id != ? AND u.status = 'active' ORDER BY p.first_name");
$stmt->execute([$userId]);
$users = $stmt->fetchAll();

echo json_encode(['success' => true, 'users' => $users]);
?>