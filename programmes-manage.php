<?php
// Programmes Management Admin Page
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$db = getDB();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['ajax_action']) {
        case 'add':
            $title = sanitize($_POST['title'] ?? '');
            $summary = sanitize($_POST['summary'] ?? '');
            $description = $_POST['description'] ?? '';
            $eligibility = sanitize($_POST['eligibility'] ?? '');
            $deadline = sanitize($_POST['deadline'] ?? null);
            $status = sanitize($_POST['status'] ?? 'active');
            $link = sanitize($_POST['link'] ?? '');
            
            if (empty($title) || empty($description)) {
                echo json_encode(['success' => false, 'message' => 'Title and description required']);
                exit;
            }
            
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $image = uploadImage($_FILES['image'], 'programmes');
            }
            
            try {
                $stmt = $db->prepare("INSERT INTO programmes (title, summary, description, eligibility, deadline, status, image, link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $summary, $description, $eligibility, $deadline, $status, $image, $link]);
                echo json_encode(['success' => true, 'message' => 'Programme added successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to add']);
            }
            exit;
            
        case 'toggle_status':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit;
            }
            try {
                $stmt = $db->prepare("UPDATE programmes SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Status updated']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to update']);
            }
            exit;
            
        case 'edit':
            $id = intval($_POST['id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $summary = sanitize($_POST['summary'] ?? '');
            $description = $_POST['description'] ?? '';
            $eligibility = sanitize($_POST['eligibility'] ?? '');
            $deadline = sanitize($_POST['deadline'] ?? null);
            $status = sanitize($_POST['status'] ?? 'active');
            $link = sanitize($_POST['link'] ?? '');
            
            $sql = "UPDATE programmes SET title = ?, summary = ?, description = ?, eligibility = ?, deadline = ?, status = ?, link = ?";
            $params = [$title, $summary, $description, $eligibility, $deadline, $status, $link];
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $image = uploadImage($_FILES['image'], 'programmes');
                $sql .= ", image = ?";
                $params[] = $image;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            try {
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                echo json_encode(['success' => true, 'message' => 'Programme updated successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to update']);
            }
            exit;
            
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            try {
                $stmt = $db->prepare("DELETE FROM programmes WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Programme deleted']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to delete']);
            }
            exit;
    }
}

$programmes = $db->query("SELECT * FROM programmes ORDER BY created_at DESC")->fetchAll();

function uploadImage($file, $folder) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array(mime_content_type($file['tmp_name']), $allowedTypes)) return '';
    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = $folder . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) return $filename;
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programmes Management | Kayunga Admin</title>
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
        .form-group textarea { height: 100px; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .prog-card { display: flex; gap: 15px; padding: 15px; background: white; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .prog-thumb { width: 120px; height: 80px; border-radius: 8px; object-fit: cover; background: #eee; }
        .prog-info { flex: 1; }
        .prog-info h3 { margin: 0 0 5px 0; }
        .prog-info .meta { font-size: 0.85rem; color: #666; }
        .prog-info .deadline { color: #e74c3c; font-weight: 500; }
        .prog-actions { display: flex; gap: 8px; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h1>Programmes Management</h1>
                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                    <a href="logout.php" class="btn btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="admin-content">
                <button class="btn" onclick="openModal()" style="margin-bottom: 20px;">
                    <i class="fas fa-plus"></i> Add Programme
                </button>
                
                <div id="progList">
                    <?php foreach ($programmes as $p): ?>
                    <div class="prog-card">
                        <img src="../uploads/programmes/<?php echo htmlspecialchars($p['image'] ?? ''); ?>" class="prog-thumb" onerror="this.style.display='none'">
                        <div class="prog-info">
                            <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                            <p class="meta"><?php echo htmlspecialchars($p['summary'] ?? ''); ?></p>
                            <div class="meta">
                                <span class="status-badge <?php echo $p['status']; ?>"><?php echo $p['status']; ?></span>
                                <?php if ($p['deadline']): ?>
                                | Deadline: <span class="deadline"><?php echo date('M d, Y', strtotime($p['deadline'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="prog-actions">
                            <button class="btn btn-sm" style="background:<?php echo $p['status'] === 'active' ? '#f59e0b' : '#22c55e'; ?>;color:white;" onclick="toggleStatus(<?php echo $p['id']; ?>)"><i class="fas fa-<?php echo $p['status'] === 'active' ? 'eye-slash' : 'eye'; ?>"></i> <?php echo $p['status'] === 'active' ? 'Unpublish' : 'Publish'; ?></button>
                            <button class="btn btn-sm" onclick="editProg(<?php echo $p['id']; ?>)"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProg(<?php echo $p['id']; ?>)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($programmes)): ?>
                    <p style="text-align: center; color: #999; padding: 40px;">No programmes yet. Click "Add Programme" to create one.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div class="modal" id="progModal">
        <div class="modal-content">
            <h2 id="modalTitle">Add Programme</h2>
            <form id="progForm">
                <input type="hidden" id="progId" value="">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" id="progTitle" required>
                </div>
                <div class="form-group">
                    <label>Summary</label>
                    <input type="text" id="progSummary">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="progDescription" required></textarea>
                </div>
                <div class="form-group">
                    <label>Eligibility</label>
                    <input type="text" id="progEligibility">
                </div>
                <div class="form-group">
                    <label>Deadline</label>
                    <input type="date" id="progDeadline">
                </div>
                <div class="form-group">
                    <label>Application Link</label>
                    <input type="url" id="progLink" placeholder="https://">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="progStatus">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" id="progImage" accept="image/*">
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
    const progData = <?php echo json_encode($programmes); ?>;
    
    function openModal() {
        document.getElementById('modalTitle').textContent = 'Add Programme';
        document.getElementById('progForm').reset();
        document.getElementById('progId').value = '';
        document.getElementById('progModal').classList.add('show');
    }
    
    function closeModal() {
        document.getElementById('progModal').classList.remove('show');
    }
    
    function editProg(id) {
        const p = progData.find(x => x.id == id);
        if (!p) return;
        
        document.getElementById('modalTitle').textContent = 'Edit Programme';
        document.getElementById('progId').value = id;
        document.getElementById('progTitle').value = p.title;
        document.getElementById('progSummary').value = p.summary || '';
        document.getElementById('progDescription').value = p.description;
        document.getElementById('progEligibility').value = p.eligibility || '';
        document.getElementById('progDeadline').value = p.deadline || '';
        document.getElementById('progLink').value = p.link || '';
        document.getElementById('progStatus').value = p.status;
        document.getElementById('progModal').classList.add('show');
    }
    
    function deleteProg(id) {
        if (!confirm('Delete this programme?')) return;
        const formData = new FormData();
        formData.append('ajax_action', 'delete');
        formData.append('id', id);
        fetch('programmes-manage.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => { alert(data.message); if (data.success) location.reload(); });
    }
    
    function toggleStatus(id) {
        const formData = new FormData();
        formData.append('ajax_action', 'toggle_status');
        formData.append('id', id);
        fetch('programmes-manage.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => { alert(data.message); if (data.success) location.reload(); });
    }
    
    document.getElementById('progForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData();
        const id = document.getElementById('progId').value;
        formData.append('ajax_action', id ? 'edit' : 'add');
        formData.append('title', document.getElementById('progTitle').value);
        formData.append('summary', document.getElementById('progSummary').value);
        formData.append('description', document.getElementById('progDescription').value);
        formData.append('eligibility', document.getElementById('progEligibility').value);
        formData.append('deadline', document.getElementById('progDeadline').value);
        formData.append('link', document.getElementById('progLink').value);
        formData.append('status', document.getElementById('progStatus').value);
        if (id) formData.append('id', id);
        const imgFile = document.getElementById('progImage').files[0];
        if (imgFile) formData.append('image', imgFile);
        fetch('programmes-manage.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => { alert(data.message); if (data.success) location.reload(); });
    });
    </script>
</body>
</html>