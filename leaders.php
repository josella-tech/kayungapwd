<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$db = getDB();

$search = $_GET['search'] ?? '';
$page = intval($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = "u.role = 'leader'";
$params = [];

if ($search) {
    $where .= " AND (u.email LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ? OR p.position LIKE ? OR p.district LIKE ?)";
    $searchParam = "%$search%";
    $params = array_fill(0, 5, $searchParam);
}

$countSql = "SELECT COUNT(*) FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE $where";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$sql = "SELECT u.id, u.email, u.status, u.created_at, p.first_name, p.last_name, p.photo, p.position, p.district, p.subcounty, p.organization FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE $where ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$leaders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaders | Kayunga District Youth Council Admin</title>
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
                <h1>Leaders</h1>
                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['admin_email']); ?></span>
                    <a href="logout.php" class="btn btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="admin-content">
                <form method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search leaders..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Search</button>
                </form>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Leader</th>
                                <th>Position</th>
                                <th>Organization</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaders as $leader): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--gray-200); overflow: hidden;">
                                                <?php if ($leader['photo']): ?>
                                                    <img src="../uploads/profiles/<?php echo htmlspecialchars($leader['photo']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="fas fa-user" style="padding: 10px; color: var(--gray-400);"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($leader['first_name'] . ' ' . $leader['last_name']); ?></strong>
                                                <div style="font-size: 0.75rem; color: var(--gray-500);"><?php echo htmlspecialchars($leader['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $leader['position'] ? htmlspecialchars($leader['position']) : '<span style="color: var(--gray-400);">-</span>'; ?></td>
                                    <td><?php echo $leader['organization'] ? htmlspecialchars($leader['organization']) : '-'; ?></td>
                                    <td>
                                        <?php if ($leader['district']): ?>
                                            <?php echo htmlspecialchars($leader['district']); ?>
                                            <?php if ($leader['subcounty']): ?>
                                                <br><span style="font-size: 0.75rem; color: var(--gray-500);"><?php echo htmlspecialchars($leader['subcounty']); ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: var(--gray-400);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="status-badge <?php echo $leader['status']; ?>"><?php echo $leader['status']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($leader['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($leaders)): ?>
                                <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--gray-400);">No leaders found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>