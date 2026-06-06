<?php
// Admin Subscribers Management
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$db = getDB();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $subscriberId = intval($_POST['subscriber_id'] ?? 0);
    
    if (!$db || $subscriberId <= 0) {
        $error = 'Invalid request';
    } else {
        try {
            if ($action === 'confirm') {
                $stmt = $db->prepare("UPDATE subscribers SET confirmed = 'confirmed' WHERE id = ?");
                $stmt->execute([$subscriberId]);
                $message = 'Subscriber confirmed';
            } elseif ($action === 'unsubscribe') {
                $stmt = $db->prepare("UPDATE subscribers SET confirmed = 'unsubscribed' WHERE id = ?");
                $stmt->execute([$subscriberId]);
                $message = 'Subscriber unsubscribed';
            } elseif ($action === 'delete') {
                $stmt = $db->prepare("DELETE FROM subscribers WHERE id = ?");
                $stmt->execute([$subscriberId]);
                $message = 'Subscriber deleted';
            }
        } catch (PDOException $e) {
            $error = 'Operation failed';
        }
    }
}

$status = $_GET['status'] ?? '';
$page = intval($_GET['page'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($status) {
    $where[] = "confirmed = ?";
    $params[] = $status;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$countSql = "SELECT COUNT(*) FROM subscribers $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$sql = "SELECT * FROM subscribers $whereClause ORDER BY subscribed_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$subscribers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribers | Kayunga District Youth Council Admin</title>
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
                <h1>Subscribers</h1>
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
                    <select name="status">
                        <option value="">All Subscribers</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="unsubscribed" <?php echo $status === 'unsubscribed' ? 'selected' : ''; ?>>Unsubscribed</option>
                    </select>
                    <button type="submit" class="btn">Filter</button>
                </form>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Subscribed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subscribers as $sub): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sub['email']); ?></td>
                                    <td><span class="status-badge <?php echo $sub['confirmed']; ?>"><?php echo $sub['confirmed']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($sub['subscribed_at'])); ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="subscriber_id" value="<?php echo $sub['id']; ?>">
                                                <?php if ($sub['confirmed'] === 'pending'): ?>
                                                    <button type="submit" name="action" value="confirm" class="btn btn-sm">Confirm</button>
                                                <?php elseif ($sub['confirmed'] === 'confirmed'): ?>
                                                    <button type="submit" name="action" value="unsubscribe" class="btn btn-sm">Unsubscribe</button>
                                                <?php endif; ?>
                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($subscribers)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 40px; color: var(--gray-400);">No subscribers found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>