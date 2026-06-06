<?php
// View User Profile
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$userId = intval($_GET['id'] ?? 0);

if (!$userId) {
    die('Invalid user ID');
}

$db = getDB();
$message = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);
    
    if ($userId > 0) {
        try {
            if ($action === 'activate') {
                $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$userId]);
                $message = 'User activated successfully';
            } elseif ($action === 'suspend') {
                $stmt = $db->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
                $stmt->execute([$userId]);
                $message = 'User suspended successfully';
            } elseif ($action === 'make_leader') {
                $stmt = $db->prepare("UPDATE users SET role = 'leader' WHERE id = ?");
                $stmt->execute([$userId]);
                $message = 'User promoted to leader';
            } elseif ($action === 'remove_leader') {
                $stmt = $db->prepare("UPDATE users SET role = 'user' WHERE id = ?");
                $stmt->execute([$userId]);
                $message = 'Leader role removed';
            }
        } catch (PDOException $e) {
            $message = 'Operation failed';
        }
    }
}

try {
    $stmt = $db->prepare("SELECT u.*, p.* FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die('User not found');
    }
} catch (PDOException $e) {
    die('Error fetching user');
}

function getBaseUrl() {
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User | Kayunga District Youth Council Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-header { display: flex; gap: 30px; margin-bottom: 30px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .profile-photo { width: 150px; height: 150px; border-radius: 50%; overflow: hidden; background: var(--gray-200); flex-shrink: 0; }
        .profile-photo img { width: 100%; height: 100%; object-fit: cover; }
        .profile-info h1 { margin-bottom: 5px; }
        .profile-info .email { color: var(--gray-500); margin-bottom: 10px; }
        .profile-info .badges { display: flex; gap: 10px; }
        .profile-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .profile-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .profile-card h3 { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid var(--gray-200); }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--gray-100); }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--gray-500); font-weight: 500; }
        .info-value { color: var(--gray-900); }
        .action-bar { display: flex; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h1>User Profile</h1>
                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                    <a href="logout.php" class="btn btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="admin-content">
                <a href="users.php" class="btn" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> Back to Users</a>
                
                <div class="profile-header">
                    <div class="profile-photo">
                        <?php if ($user['photo']): ?>
                            <img src="../uploads/profiles/<?php echo htmlspecialchars($user['photo']); ?>" alt="Profile Photo">
                        <?php else: ?>
                            <i class="fas fa-user" style="font-size: 60px; padding: 45px; color: var(--gray-400);"></i>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                        <div class="email"><?php echo htmlspecialchars($user['email']); ?></div>
                        <div class="badges">
                            <span class="status-badge <?php echo $user['status']; ?>"><?php echo $user['status']; ?></span>
                            <span class="status-badge <?php echo $user['role'] === 'leader' ? 'active' : ''; ?>"><?php echo $user['role']; ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="action-bar">
                    <?php if ($user['status'] === 'pending'): ?>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="activate">
                            <button type="submit" class="btn btn-sm">Activate User</button>
                        </form>
                    <?php elseif ($user['status'] === 'active'): ?>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="suspend">
                            <button type="submit" class="btn btn-sm btn-danger">Suspend User</button>
                        </form>
                    <?php elseif ($user['status'] === 'suspended'): ?>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="activate">
                            <button type="submit" class="btn btn-sm">Activate User</button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($user['role'] === 'user'): ?>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="make_leader">
                            <button type="submit" class="btn btn-sm">Make Leader</button>
                        </form>
                    <?php elseif ($user['role'] === 'leader'): ?>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="action" value="remove_leader">
                            <button type="submit" class="btn btn-sm">Remove Leader</button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <br><br>
                
                <div class="profile-grid">
                    <div class="profile-card">
                        <h3><i class="fas fa-user"></i> Personal Info</h3>
                        <div class="info-row">
                            <span class="info-label">Gender</span>
                            <span class="info-value"><?php echo $user['gender'] ? htmlspecialchars($user['gender']) : 'Not specified'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date of Birth</span>
                            <span class="info-value"><?php echo $user['date_of_birth'] ? date('M d, Y', strtotime($user['date_of_birth'])) : 'Not specified'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone</span>
                            <span class="info-value"><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Not specified'; ?></span>
                        </div>
                    </div>
                    
                    <div class="profile-card">
                        <h3><i class="fas fa-map-marker-alt"></i> Location</h3>
                        <div class="info-row">
                            <span class="info-label">District</span>
                            <span class="info-value"><?php echo $user['district'] ? htmlspecialchars($user['district']) : 'Not specified'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Subcounty</span>
                            <span class="info-value"><?php echo $user['subcounty'] ? htmlspecialchars($user['subcounty']) : 'Not specified'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Parish</span>
                            <span class="info-value"><?php echo $user['parish'] ? htmlspecialchars($user['parish']) : 'Not specified'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Village</span>
                            <span class="info-value"><?php echo $user['village'] ? htmlspecialchars($user['village']) : 'Not specified'; ?></span>
                        </div>
                    </div>
                    
                    <div class="profile-card">
                        <h3><i class="fas fa-briefcase"></i> Position</h3>
                        <div class="info-row">
                            <span class="info-label">Position</span>
                            <span class="info-value"><?php echo $user['position'] ? htmlspecialchars($user['position']) : 'Not specified'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Organization</span>
                            <span class="info-value"><?php echo $user['organization'] ? htmlspecialchars($user['organization']) : 'Not specified'; ?></span>
                        </div>
                    </div>
                    
                    <div class="profile-card">
                        <h3><i class="fas fa-link"></i> Social Media</h3>
                        <div class="info-row">
                            <span class="info-label">Facebook</span>
                            <span class="info-value"><?php echo $user['facebook'] ? htmlspecialchars($user['facebook']) : 'Not specified'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Twitter</span>
                            <span class="info-value"><?php echo $user['twitter'] ? htmlspecialchars($user['twitter']) : 'Not specified'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Instagram</span>
                            <span class="info-value"><?php echo $user['instagram'] ? htmlspecialchars($user['instagram']) : 'Not specified'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">LinkedIn</span>
                            <span class="info-value"><?php echo $user['linkedin'] ? htmlspecialchars($user['linkedin']) : 'Not specified'; ?></span>
                        </div>
                    </div>
                    
                    <div class="profile-card" style="grid-column: span 2;">
                        <h3><i class="fas fa-heart"></i> Bio</h3>
                        <p><?php echo $user['bio'] ? nl2br(htmlspecialchars($user['bio'])) : 'Not specified'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>