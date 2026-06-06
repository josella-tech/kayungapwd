<?php
// News Management API
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        addNews();
        break;
    case 'edit':
        editNews();
        break;
    case 'delete':
        deleteNews();
        break;
    case 'get':
        getNews();
        break;
    case 'list':
    default:
        listNews();
        break;
}

function listNews() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM news ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'news' => $stmt->fetchAll()]);
}

function getNews() {
    $db = getDB();
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $news = $stmt->fetch();
    if ($news) {
        echo json_encode(['success' => true, 'news' => $news]);
    } else {
        echo json_encode(['success' => false, 'message' => 'News not found']);
    }
}

function addNews() {
    $db = getDB();
    
    $title = sanitize($_POST['title'] ?? '');
    $summary = sanitize($_POST['summary'] ?? '');
    $content = $_POST['content'] ?? '';
    $author = sanitize($_POST['author'] ?? '');
    $status = sanitize($_POST['status'] ?? 'draft');
    $image = '';
    
    if (empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Title and content are required']);
        return;
    }
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $result = uploadNewsImage($_FILES['image']);
        if ($result['success']) {
            $image = $result['filename'];
        }
    }
    
    try {
        $stmt = $db->prepare("INSERT INTO news (title, summary, content, author, image, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $summary, $content, $author, $image, $status]);
        echo json_encode(['success' => true, 'message' => 'News added successfully', 'id' => $db->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to add news']);
    }
}

function editNews() {
    $db = getDB();
    
    $id = intval($_POST['id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $summary = sanitize($_POST['summary'] ?? '');
    $content = $_POST['content'] ?? '';
    $author = sanitize($_POST['author'] ?? '');
    $status = sanitize($_POST['status'] ?? 'draft');
    
    if ($id <= 0 || empty($title) || empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }
    
    $image_sql = "";
    $params = [$title, $summary, $content, $author, $status, $id];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $result = uploadNewsImage($_FILES['image']);
        if ($result['success']) {
            $image_sql = ", image = ?";
            array_splice($params, 4, 0, [$result['filename']]);
        }
    }
    
    try {
        $sql = "UPDATE news SET title = ?, summary = ?, content = ?, author = ?, status = ?$image_sql WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true, 'message' => 'News updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update news']);
    }
}

function deleteNews() {
    $db = getDB();
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM news WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'News deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete news']);
    }
}

function uploadNewsImage($file) {
    $maxSize = 10 * 1024 * 1024; // 10MB
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $uploadDir = UPLOAD_PATH . 'news/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'news_' . uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'message' => 'Upload failed'];
}
?>