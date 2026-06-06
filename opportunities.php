<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $oppId = intval($_POST['opportunity_id'] ?? 0);
    
    if ($action === 'add') {
        $title = sanitize($_POST['title'] ?? '');
        $type = sanitize($_POST['type'] ?? '');
        $organization = sanitize($_POST['organization'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $requirements = sanitize($_POST['requirements'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $deadline = sanitize($_POST['deadline'] ?? '');
        $link = sanitize($_POST['link'] ?? '');
        
        if (empty($title) || empty($description)) {
            $error = 'Title and description are required';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO opportunities (title, type, organization, description, requirements, location, deadline, link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $type, $organization, $description, $requirements, $location, $deadline, $link]);
                $message = 'Opportunity added';
            } catch (PDOException $e) {
                $error = 'Operation failed';
            }
        }
    } elseif ($action === 'delete' && $oppId > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM opportunities WHERE id = ?");
            $stmt->execute([$oppId]);
            $message = 'Opportunity deleted';
        } catch (PDOException $e) {
            $error = 'Delete failed';
        }
    }
}

$opportunities = $db->query("SELECT * FROM opportunities ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Opportunities | Kayunga District Youth Council Admin</title>
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
                <h1>Opportunities</h1>
                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                    <a href="logout.php" class="btn btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                
                <button class="btn" onclick="document.getElementById('addForm').style.display='block'" style="margin-bottom: 20px;">
                    <i class="fas fa-plus"></i> Add Opportunity
                </button>
                
                <div id="addForm" class="form-container" style="display: none; margin-bottom: 20px;">
                    <h2>Add Opportunity</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="form-row">
                            <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
                            <div class="form-group">
                                <label>Type</label>
                                <select name="type" required>
                                    <option value="job">Job</option>
                                    <option value="internship">Internship</option>
                                    <option value="volunteer">Volunteer</option>
                                    <option value="training">Training</option>
                                    <option value="scholarship">Scholarship</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group"><label>Organization</label><input type="text" name="organization"></div>
                        <div class="form-group"><label>Description</label><textarea name="description" rows="6" required></textarea></div>
                        <div class="form-group"><label>Requirements</label><textarea name="requirements" rows="3"></textarea></div>
                        <div class="form-row">
                            <div class="form-group"><label>Location</label><input type="text" name="location"></div>
                            <div class="form-group"><label>Deadline</label><input type="date" name="deadline"></div>
                        </div>
                        <div class="form-group"><label>Application Link</label><input type="url" name="link"></div>
                        <button type="submit" class="btn">Save</button>
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('addForm').style.display='none'">Cancel</button>
                    </form>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Title</th><th>Type</th><th>Organization</th><th>Deadline</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($opportunities as $o): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($o['title']); ?></td>
                                    <td><span class="status-badge"><?php echo $o['type']; ?></span></td>
                                    <td><?php echo htmlspecialchars($o['organization'] ?? '-'); ?></td>
                                    <td><?php echo $o['deadline'] ? date('M d, Y', strtotime($o['deadline'])) : '-'; ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="opportunity_id" value="<?php echo $o['id']; ?>">
                                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($opportunities)): ?>
                                <tr><td colspan="5" style="text-align: center; padding: 40px;">No opportunities yet</td></tr>
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