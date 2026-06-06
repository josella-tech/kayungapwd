<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $historyId = intval($_POST['history_id'] ?? 0);
    
    if ($action === 'add' || $action === 'edit') {
        $title = sanitize($_POST['title'] ?? '');
        $content = sanitize($_POST['content'] ?? '');
        $year = sanitize($_POST['year'] ?? '');
        $category = sanitize($_POST['category'] ?? 'other');
        $displayOrder = intval($_POST['display_order'] ?? 0);
        
        if (empty($title) || empty($content)) {
            $error = 'Title and content are required';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $db->prepare("INSERT INTO district_history (title, content, year, category, display_order) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $content, $year, $category, $displayOrder]);
                    $message = 'History entry added';
                } else {
                    $stmt = $db->prepare("UPDATE district_history SET title = ?, content = ?, year = ?, category = ?, display_order = ? WHERE id = ?");
                    $stmt->execute([$title, $content, $year, $category, $displayOrder, $historyId]);
                    $message = 'History entry updated';
                }
            } catch (PDOException $e) {
                $error = 'Operation failed';
            }
        }
    } elseif ($action === 'delete' && $historyId > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM district_history WHERE id = ?");
            $stmt->execute([$historyId]);
            $message = 'History entry deleted';
        } catch (PDOException $e) {
            $error = 'Delete failed';
        }
    }
}

$history = $db->query("SELECT * FROM district_history ORDER BY year DESC, display_order ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>District History | Kayunga District Youth Council Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h1>District History</h1>
                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                    <a href="logout.php" class="btn btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                
                <button class="btn" onclick="document.getElementById('addForm').style.display='block'" style="margin-bottom: 20px;">
                    <i class="fas fa-plus"></i> Add History
                </button>
                
                <div id="addForm" class="form-container" style="display: none; margin-bottom: 20px;">
                    <h2>Add District History</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
                        <div class="form-group"><label>Year</label><input type="text" name="year" placeholder="e.g., 1990"></div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category">
                                <option value="foundation">Foundation</option>
                                <option value="development">Development</option>
                                <option value="achievement">Achievement</option>
                                <option value="milestone">Milestone</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Content</label><textarea name="content" rows="6" required></textarea></div>
                        <div class="form-group"><label>Display Order</label><input type="number" name="display_order" value="0"></div>
                        <button type="submit" class="btn">Save</button>
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('addForm').style.display='none'">Cancel</button>
                    </form>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Year</th><th>Title</th><th>Category</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($h['year'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($h['title']); ?></td>
                                    <td><span class="status-badge"><?php echo $h['category']; ?></span></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="history_id" value="<?php echo $h['id']; ?>">
                                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($history)): ?>
                                <tr><td colspan="4" style="text-align: center; padding: 40px;">No history entries yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>