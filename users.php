<?php
// Admin Users Management
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);
    
    if (!$db || $userId <= 0) {
        $error = 'Invalid request';
    } else {
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
            } elseif ($action === 'delete') {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $message = 'User deleted successfully';
            }
        } catch (PDOException $e) {
            $error = 'Operation failed';
        }
    }
}

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$role = $_GET['role'] ?? '';
$page = intval($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search) {
    $where[] = "(u.email LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}
if ($status) {
    $where[] = "u.status = ?";
    $params[] = $status;
}
if ($role) {
    $where[] = "u.role = ?";
    $params[] = $role;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$countSql = "SELECT COUNT(*) FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalUsers = $stmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

$sql = "SELECT u.id, u.email, u.role, u.status, u.created_at, 
    p.first_name, p.last_name, p.photo, p.position, p.district, p.subcounty
    FROM users u 
    LEFT JOIN user_profiles p ON u.id = p.user_id 
    $whereClause 
    ORDER BY u.created_at DESC 
    LIMIT $perPage OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

function getBaseUrl() {
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | Kayunga District Youth Council Admin</title>
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
                <h1>Users</h1>
                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                    <a href="logout.php" class="btn btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="admin-content">
                <?php if ($message): ?>
                    <div class="alert success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                    <select name="role">
                        <option value="">All Roles</option>
                        <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>User</option>
                        <option value="leader" <?php echo $role === 'leader' ? 'selected' : ''; ?>>Leader</option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <button type="submit" class="btn">Search</button>
                </form>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Location</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--gray-200); overflow: hidden;">
                                                <?php if ($user['photo']): ?>
                                                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user['photo']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="fas fa-user" style="padding: 10px; color: var(--gray-400);"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                <div style="font-size: 0.75rem; color: var(--gray-500);"><?php echo htmlspecialchars($user['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($user['district']): ?>
                                            <?php echo htmlspecialchars($user['district']); ?>
                                            <?php if ($user['subcounty']): ?>
                                                <br><span style="font-size: 0.75rem; color: var(--gray-500);"><?php echo htmlspecialchars($user['subcounty']); ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: var(--gray-400);">Not specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $user['position'] ? htmlspecialchars($user['position']) : '<span style="color: var(--gray-400);">-</span>'; ?></td>
                                    <td><span class="status-badge <?php echo $user['status']; ?>"><?php echo $user['status']; ?></span></td>
                                    <td><span class="status-badge <?php echo $user['role'] === 'leader' ? 'active' : ''; ?>"><?php echo $user['role']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="view-user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm" title="View Profile"><i class="fas fa-eye"></i> View</a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <?php if ($user['status'] === 'pending'): ?>
                                                    <button type="submit" name="action" value="activate" class="btn btn-sm" title="Activate">Activate</button>
                                                <?php elseif ($user['status'] === 'active'): ?>
                                                    <button type="submit" name="action" value="suspend" class="btn btn-sm btn-danger" title="Suspend">Suspend</button>
                                                <?php endif; ?>
                                                <?php if ($user['role'] === 'user'): ?>
                                                    <button type="submit" name="action" value="make_leader" class="btn btn-sm" title="Make Leader">Promote</button>
                                                <?php elseif ($user['role'] === 'leader'): ?>
                                                    <button type="submit" name="action" value="remove_leader" class="btn btn-sm" title="Remove Leader">Demote</button>
                                                <?php endif; ?>
                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--gray-400);">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&role=<?php echo $role; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>