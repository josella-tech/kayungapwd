<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$db = getDB();
$message = '';
$error = '';

function uploadImage($file, $folder) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array(mime_content_type($file['tmp_name']), $allowedTypes)) return '';
    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = $folder . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) return $filename;
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $teamId = intval($_POST['team_id'] ?? 0);
    
    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $position = sanitize($_POST['position'] ?? '');
        $bio = sanitize($_POST['bio'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $orderNum = intval($_POST['order_num'] ?? 0);
        
        $photo = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $photo = uploadImage($_FILES['photo'], 'team');
        }
        
        if (empty($name) || empty($position)) {
            $error = 'Name and position are required';
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO team (name, position, bio, email, phone, order_num, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $position, $bio, $email, $phone, $orderNum, $photo]);
                $message = 'Team member added';
            } catch (PDOException $e) {
                $error = 'Operation failed';
            }
        }
    } elseif ($action === 'edit' && $teamId > 0) {
        $name = sanitize($_POST['name'] ?? '');
        $position = sanitize($_POST['position'] ?? '');
        $bio = sanitize($_POST['bio'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $orderNum = intval($_POST['order_num'] ?? 0);
        
        $sql = "UPDATE team SET name = ?, position = ?, bio = ?, email = ?, phone = ?, order_num = ?";
        $params = [$name, $position, $bio, $email, $phone, $orderNum];
        
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $photo = uploadImage($_FILES['photo'], 'team');
            $sql .= ", photo = ?";
            $params[] = $photo;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $teamId;
        
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $message = 'Team member updated';
        } catch (PDOException $e) {
            $error = 'Update failed';
        }
    } elseif ($action === 'delete' && $teamId > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM team WHERE id = ?");
            $stmt->execute([$teamId]);
            $message = 'Team member deleted';
        } catch (PDOException $e) {
            $error = 'Delete failed';
        }
    } elseif ($action === 'toggle_status' && $teamId > 0) {
        try {
            $stmt = $db->prepare("UPDATE team SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
            $stmt->execute([$teamId]);
            $message = 'Status updated';
        } catch (PDOException $e) {
            $error = 'Failed to update status';
        }
    }
}

$team = $db->query("SELECT * FROM team ORDER BY order_num ASC, name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team | Kayunga District Youth Council Admin</title>
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
                <h1>Team Members</h1>
                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                    <a href="logout.php" class="btn btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                
                <button class="btn" onclick="document.getElementById('addForm').style.display='block'" style="margin-bottom: 20px;">
                    <i class="fas fa-plus"></i> Add Team Member
                </button>
                
                <div id="addForm" class="form-container" style="display: none; margin-bottom: 20px;">
                    <h2>Add Team Member</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <div class="form-row">
                            <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                            <div class="form-group"><label>Position</label><input type="text" name="position" required></div>
                        </div>
                        <div class="form-group"><label>Photo</label><input type="file" name="photo" accept="image/*"></div>
                        <div class="form-group"><label>Bio</label><textarea name="bio" rows="3"></textarea></div>
                        <div class="form-row">
                            <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                            <div class="form-group"><label>Phone</label><input type="tel" name="phone"></div>
                        </div>
                        <div class="form-group"><label>Order</label><input type="number" name="order_num" value="0"></div>
                        <button type="submit" class="btn">Save</button>
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('addForm').style.display='none'">Cancel</button>
                    </form>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Photo</th><th>Name</th><th>Position</th><th>Email</th><th>Phone</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($team as $t): ?>
                                <tr>
                                    <td><?php if($t['photo']): ?><img src="../../uploads/team/<?php echo htmlspecialchars($t['photo']); ?>" style="width:150px;height:150px;object-fit:cover;border-radius:4px;"><?php else: ?>-<?php endif; ?></td>
                                    <td><?php echo htmlspecialchars($t['name']); ?></td>
                                    <td><?php echo htmlspecialchars($t['position']); ?></td>
                                    <td><?php echo htmlspecialchars($t['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($t['phone'] ?? '-'); ?></td>
                                    <td>
                                        <span style="display:inline-block;padding:4px 8px;background:<?php echo $t['status'] === 'active' ? '#d1fae5' : '#fee2e2'; ?>;color:<?php echo $t['status'] === 'active' ? '#065f46' : '#991b1b'; ?>;border-radius:4px;font-size:12px;margin-right:5px;"><?php echo $t['status']; ?></span>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="team_id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" name="action" value="toggle_status" class="btn btn-sm" style="background:#<?php echo $t['status'] === 'active' ? 'f59e0b' : '22c55e'; ?>;color:white;"><?php echo $t['status'] === 'active' ? 'Unpublish' : 'Publish'; ?></button>
                                        </form>
                                        <button type="button" class="btn btn-sm" onclick="showEdit(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['name']); ?>', '<?php echo htmlspecialchars($t['position']); ?>', '<?php echo htmlspecialchars($t['email'] ?? ''); ?>', '<?php echo htmlspecialchars($t['phone'] ?? ''); ?>', '<?php echo htmlspecialchars($t['bio'] ?? ''); ?>', <?php echo $t['order_num']; ?>)">Edit</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="team_id" value="<?php echo $t['id']; ?>">
                                            <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($team)): ?>
                                <tr><td colspan="5" style="text-align: center; padding: 40px;">No team members yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div id="editForm" class="form-container" style="display: none; margin-bottom: 20px;">
                    <h2>Edit Team Member</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="team_id" id="edit_id">
                        <div class="form-row">
                            <div class="form-group"><label>Name</label><input type="text" name="name" id="edit_name" required></div>
                            <div class="form-group"><label>Position</label><input type="text" name="position" id="edit_position" required></div>
                        </div>
                        <div class="form-group"><label>Photo (leave empty to keep current)</label><input type="file" name="photo" accept="image/*"></div>
                        <div class="form-group"><label>Bio</label><textarea name="bio" id="edit_bio" rows="3"></textarea></div>
                        <div class="form-row">
                            <div class="form-group"><label>Email</label><input type="email" name="email" id="edit_email"></div>
                            <div class="form-group"><label>Phone</label><input type="tel" name="phone" id="edit_phone"></div>
                        </div>
                        <div class="form-group"><label>Order</label><input type="number" name="order_num" id="edit_order"></div>
                        <button type="submit" class="btn">Update</button>
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('editForm').style.display='none'">Cancel</button>
                    </form>
                </div>
                
                <script>
                function showEdit(id, name, position, email, phone, bio, order) {
                    document.getElementById('edit_id').value = id;
                    document.getElementById('edit_name').value = name;
                    document.getElementById('edit_position').value = position;
                    document.getElementById('edit_email').value = email;
                    document.getElementById('edit_phone').value = phone;
                    document.getElementById('edit_bio').value = bio;
                    document.getElementById('edit_order').value = order;
                    document.getElementById('editForm').style.display = 'block';
                    document.getElementById('addForm').style.display = 'none';
                }
                </script>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>