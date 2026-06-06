<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$currentAdminRole = $_SESSION['admin_role'] ?? '';
$isCurrentSuperAdmin = ($currentAdminRole === 'super_admin');

if (!$isCurrentSuperAdmin) {
    die('Access denied. Super admin only.');
}

$db = getDB();
$message = '';
$error = '';

function isCurrentSuperAdmin() {
    return ($_SESSION['admin_role'] ?? '') === 'super_admin';
}

// Only super admin can add or delete admins
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isCurrentSuperAdmin()) {
    $action = $_POST['action'] ?? '';
    $adminId = intval($_POST['admin_id'] ?? 0);
    
    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitize($_POST['role'] ?? 'editor');
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'All fields are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            try {
                $checkStmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $error = 'Email already exists';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO admins (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $passwordHash, $role]);
                    $message = 'Admin added successfully';
                }
            } catch (PDOException $e) {
                $error = 'Operation failed';
            }
        }
    } elseif ($action === 'delete' && $adminId > 0 && $adminId != getAdminId()) {
        try {
            $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$adminId]);
            $message = 'Admin deleted';
        } catch (PDOException $e) {
            $error = 'Delete failed';
        }
    }
}

$admins = $db->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admins | Kayunga District Youth Council Admin</title>
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
                <h1>Admin Users</h1>
                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                    <a href="logout.php" class="btn btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?><div class="alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                
                <?php if (isCurrentSuperAdmin()): ?>
                <button class="btn" onclick="document.getElementById('addForm').style.display='block'" style="margin-bottom: 20px;">
                    <i class="fas fa-plus"></i> Add Admin
                </button>
                <?php endif; ?>
                
                <div id="addForm" class="form-container" style="display: none; margin-bottom: 20px;">
                    <h2>Add Admin User</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group"><label>Name</label><input type="text" name="name" required></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                        <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
                        <div class="form-group">
                            <label>Role</label>
                            <select name="role">
                                <option value="editor">Editor</option>
                                <?php if (isCurrentSuperAdmin()): ?>
                                <option value="super_admin">Super Admin</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn">Save</button>
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('addForm').style.display='none'">Cancel</button>
                    </form>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($admin['name']); ?></td>
                                    <td><?php 
                                        if (!isCurrentSuperAdmin()) {
                                            echo '••••••••';
                                        } else {
                                            echo htmlspecialchars($admin['email']);
                                        }
                                    ?></td>
                                    <td><span class="status-badge <?php echo $admin['role'] === 'super_admin' ? 'active' : ''; ?>"><?php echo $admin['role']; ?></span></td>
                                    <td><span class="status-badge <?php echo $admin['status']; ?>"><?php echo $admin['status']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                    <td>
                                        <?php if (isCurrentSuperAdmin() && $admin['id'] != getAdminId()): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete this admin?')">Delete</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: var(--gray-400); font-size: 0.75rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>