<?php
// News Management Admin Page
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$db = getDB();
$message = '';
$error = '';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['ajax_action']) {
        case 'add':
            $title = sanitize($_POST['title'] ?? '');
            $summary = sanitize($_POST['summary'] ?? '');
            $content = $_POST['content'] ?? '';
            $author = sanitize($_POST['author'] ?? '');
            $status = sanitize($_POST['status'] ?? 'draft');
            $image = '';
            
            if (empty($title) || empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Title and content required']);
                exit;
            }
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $image = uploadImage($_FILES['image'], 'news');
            }
            
            try {
                $stmt = $db->prepare("INSERT INTO news (title, summary, content, author, image, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $summary, $content, $author, $image, $status]);
                echo json_encode(['success' => true, 'message' => 'News added successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to add news']);
            }
            exit;
            
        case 'edit':
            $id = intval($_POST['id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $summary = sanitize($_POST['summary'] ?? '');
            $content = $_POST['content'] ?? '';
            $author = sanitize($_POST['author'] ?? '');
            $status = sanitize($_POST['status'] ?? 'draft');
            
            if ($id <= 0 || empty($title) || empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }
            
            $sql = "UPDATE news SET title = ?, summary = ?, content = ?, author = ?, status = ?";
            $params = [$title, $summary, $content, $author, $status];
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $image = uploadImage($_FILES['image'], 'news');
                $sql .= ", image = ?";
                $params[] = $image;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            try {
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                echo json_encode(['success' => true, 'message' => 'News updated successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to update']);
            }
            exit;
            
        case 'toggle_status':
            $id = intval($_POST['id'] ?? 0);
            try {
                $stmt = $db->prepare("UPDATE news SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Status updated']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed']);
            }
            exit;
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            try {
                $stmt = $db->prepare("DELETE FROM news WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'News deleted']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete']);
            }
            exit;
    }
}

// Get all news
$news = $db->query("SELECT * FROM news ORDER BY created_at DESC")->fetchAll();

function uploadImage($file, $folder) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array(mime_content_type($file['tmp_name']), $allowedTypes)) {
        return '';
    }
    
    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = $folder . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News Management | Kayunga Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal.show { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .form-group textarea { height: 150px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .news-card { display: flex; gap: 15px; padding: 15px; background: white; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .news-thumb { width: 120px; height: 80px; border-radius: 8px; object-fit: cover; background: #eee; }
        .news-info { flex: 1; }
        .news-info h3 { margin: 0 0 5px 0; }
        .news-info .meta { font-size: 0.85rem; color: #666; }
        .news-actions { display: flex; gap: 8px; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h1>News Management</h1>
                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                    <a href="logout.php" class="btn btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="admin-content">
                <button class="btn" onclick="openModal()" style="margin-bottom: 20px;">
                    <i class="fas fa-plus"></i> Add News
                </button>
                
                <div id="newsList">
                    <?php foreach ($news as $item): ?>
                    <div class="news-card">
                        <img src="../uploads/news/<?php echo htmlspecialchars($item['image'] ?? ''); ?>" class="news-thumb" onerror="this.style.display='none'">
                        <div class="news-info">
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p class="meta"><?php echo htmlspecialchars($item['summary'] ?? ''); ?></p>
                            <div class="meta">
                                <span class="status-badge <?php echo $item['status']; ?>"><?php echo $item['status']; ?></span>
                                | <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                            </div>
                        </div>
                        <div class="news-actions">
                            <button class="btn btn-sm" style="background:<?php echo $item['status'] === 'active' ? '#f59e0b' : '#22c55e'; ?>;color:white;" onclick="toggleNewsStatus(<?php echo $item['id']; ?>)"><i class="fas fa-<?php echo $item['status'] === 'active' ? 'eye-slash' : 'eye'; ?>"></i></button>
                            <button class="btn btn-sm" onclick="editNews(<?php echo $item['id']; ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="deleteNews(<?php echo $item['id']; ?>)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($news)): ?>
                    <p style="text-align: center; color: #999; padding: 40px;">No news articles yet. Click "Add News" to create one.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div class="modal" id="newsModal">
        <div class="modal-content">
            <h2 id="modalTitle">Add News</h2>
            <form id="newsForm">
                <input type="hidden" id="newsId" value="">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" id="newsTitle" required>
                </div>
                <div class="form-group">
                    <label>Summary</label>
                    <input type="text" id="newsSummary">
                </div>
                <div class="form-group">
                    <label>Content</label>
                    <textarea id="newsContent" required></textarea>
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" id="newsAuthor">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="newsStatus">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" id="newsImage" accept="image/*">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn">Save</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="script.js"></script>
    <script>
    const newsData = <?php echo json_encode($news); ?>;
    
    function openModal() {
        document.getElementById('modalTitle').textContent = 'Add News';
        document.getElementById('newsForm').reset();
        document.getElementById('newsId').value = '';
        document.getElementById('newsModal').classList.add('show');
    }
    
    function closeModal() {
        document.getElementById('newsModal').classList.remove('show');
    }
    
    function editNews(id) {
        const item = newsData.find(n => n.id == id);
        if (!item) return;
        
        document.getElementById('modalTitle').textContent = 'Edit News';
        document.getElementById('newsId').value = id;
        document.getElementById('newsTitle').value = item.title;
        document.getElementById('newsSummary').value = item.summary || '';
        document.getElementById('newsContent').value = item.content;
        document.getElementById('newsAuthor').value = item.author || '';
        document.getElementById('newsStatus').value = item.status;
        document.getElementById('newsModal').classList.add('show');
    }
    
    function deleteNews(id) {
        if (!confirm('Delete this news article?')) return;
        
        const formData = new FormData();
        formData.append('ajax_action', 'delete');
        formData.append('id', id);
        
        fetch('news-manage.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
    }
    
    function toggleNewsStatus(id) {
        const formData = new FormData();
        formData.append('ajax_action', 'toggle_status');
        formData.append('id', id);
        
        fetch('news-manage.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
    }
    
    document.getElementById('newsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData();
        const id = document.getElementById('newsId').value;
        formData.append('ajax_action', id ? 'edit' : 'add');
        formData.append('title', document.getElementById('newsTitle').value);
        formData.append('summary', document.getElementById('newsSummary').value);
        formData.append('content', document.getElementById('newsContent').value);
        formData.append('author', document.getElementById('newsAuthor').value);
        formData.append('status', document.getElementById('newsStatus').value);
        
        if (id) formData.append('id', id);
        
        const imageFile = document.getElementById('newsImage').files[0];
        if (imageFile) formData.append('image', imageFile);
        
        fetch('news-manage.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                alert(data.message);
                if (data.success) location.reload();
            });
    });
    </script>
</body>
</html>