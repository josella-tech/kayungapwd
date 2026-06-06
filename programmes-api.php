<?php
// Programmes Management API
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        addProgramme();
        break;
    case 'edit':
        editProgramme();
        break;
    case 'delete':
        deleteProgramme();
        break;
    case 'get':
        getProgramme();
        break;
    case 'list':
    default:
        listProgrammes();
        break;
}

function listProgrammes() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM programmes ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'programmes' => $stmt->fetchAll()]);
}

function getProgramme() {
    $db = getDB();
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM programmes WHERE id = ?");
    $stmt->execute([$id]);
    $programme = $stmt->fetch();
    if ($programme) {
        echo json_encode(['success' => true, 'programme' => $programme]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Programme not found']);
    }
}

function addProgramme() {
    $db = getDB();
    
    $title = sanitize($_POST['title'] ?? '');
    $summary = sanitize($_POST['summary'] ?? '');
    $description = $_POST['description'] ?? '';
    $eligibility = sanitize($_POST['eligibility'] ?? '');
    $deadline = sanitize($_POST['deadline'] ?? null);
    $status = sanitize($_POST['status'] ?? 'active');
    $image = '';
    $link = sanitize($_POST['link'] ?? '');
    
    if (empty($title) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Title and description are required']);
        return;
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $result = uploadProgrammeImage($_FILES['image']);
        if ($result['success']) {
            $image = $result['filename'];
        }
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO programmes (title, summary, description, eligibility, deadline, status, image, link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $summary, $description, $eligibility, $deadline, $status, $image, $link]);
        echo json_encode(['success' => true, 'message' => 'Programme added successfully', 'id' => $db->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to add programme']);
    }
}

function editProgramme() {
    $db = getDB();
    
    $id = intval($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $summary = sanitize($_POST['summary'] ?? '');
    $description = $_POST['description'] ?? '';
    $eligibility = sanitize($_POST['eligibility'] ?? '');
    $deadline = sanitize($_POST['deadline'] ?? null);
    $status = sanitize($_POST['status'] ?? 'active');
    $link = sanitize($_POST['link'] ?? '');
    
    if ($id <= 0 || empty($title) || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }
    
    $params = [$title, $summary, $description, $eligibility, $deadline, $status, $link, $id];
    $image_sql = "";
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $result = uploadProgrammeImage($_FILES['image']);
        if ($result['success']) {
            $image_sql = ", image = ?";
            array_splice($params, 6, 0, [$result['filename']]);
        }
    }
    
    try {
        $sql = "UPDATE programmes SET title = ?, summary = ?, description = ?, eligibility = ?, deadline = ?, status = ?, link = ?$image_sql WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'message' => 'Programme updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update programme']);
    }
}

function deleteProgramme() {
    $db = getDB();
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM programmes WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Programme deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete programme']);
    }
}

function uploadProgrammeImage($file) {
    $maxSize = 10 * 1024 * 1024;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $uploadDir = UPLOAD_PATH . 'programmes/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'programme_' . uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}
?>