<?php
// Admin Dashboard
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$db = getDB();

$stats = [
    'users' => 0,
    'pending_users' => 0,
    'subscribers' => 0,
    'confirmed_subscribers' => 0,
    'messages' => 0,
    'unread_messages' => 0,
    'news' => 0,
    'programmes' => 0,
    'team' => 0,
    'opportunities' => 0
];

if ($db) {
    $stats['users'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['pending_users'] = $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
    $stats['subscribers'] = $db->query("SELECT COUNT(*) FROM subscribers")->fetchColumn();
    $stats['confirmed_subscribers'] = $db->query("SELECT COUNT(*) FROM subscribers WHERE confirmed = 'confirmed'")->fetchColumn();
    $stats['messages'] = $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    $stats['unread_messages'] = $db->query("SELECT COUNT(*) FROM messages WHERE status = 'unread'")->fetchColumn();
    $stats['news'] = $db->query("SELECT COUNT(*) FROM news")->fetchColumn();
    $stats['programmes'] = $db->query("SELECT COUNT(*) FROM programmes")->fetchColumn();
    $stats['team'] = $db->query("SELECT COUNT(*) FROM team")->fetchColumn();
    $stats['opportunities'] = $db->query("SELECT COUNT(*) FROM opportunities")->fetchColumn();
}

$recentMessages = [];
$recentUsers = [];
if ($db) {
    $recentMessages = $db->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $recentUsers = $db->query("SELECT u.id, u.email, u.status, u.created_at, p.first_name, p.last_name FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id ORDER BY u.created_at DESC LIMIT 5")->fetchAll();
}

function getMessage($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Kayunga District Youth Council Admin</title>
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
                <h1>Dashboard</h1>
                <div class="admin-user">
                    <a href="../../index.html" class="btn btn-sm" style="margin-right:10px;"><i class="fas fa-home"></i> Home</a>
                    <a href="logout.php" class="btn btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </header>
            
            <div class="admin-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['users']; ?></h3>
                            <p>Total Users</p>
                            <span class="stat-badge"><?php echo $stats['pending_users']; ?> pending</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['subscribers']; ?></h3>
                            <p>Subscribers</p>
                            <span class="stat-badge"><?php echo $stats['confirmed_subscribers']; ?> confirmed</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-comments"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['messages']; ?></h3>
                            <p>Messages</p>
                            <span class="stat-badge warning"><?php echo $stats['unread_messages']; ?> unread</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-newspaper"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['news']; ?></h3>
                            <p>News Articles</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['programmes']; ?></h3>
                            <p>Programmes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['opportunities']; ?></h3>
                            <p>Opportunities</p>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2><i class="fas fa-comments"></i> Recent Messages</h2>
                            <a href="messages.php" class="btn btn-sm">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentMessages)): ?>
                                <p class="empty-state">No messages yet</p>
                            <?php else: ?>
                                <div class="items-list">
                                    <?php foreach ($recentMessages as $msg): ?>
                                        <div class="item-row">
                                            <div class="item-info">
                                                <h4><?php echo htmlspecialchars($msg['name']); ?></h4>
                                                <p><?php echo htmlspecialchars($msg['subject']); ?></p>
                                                <span class="item-date"><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></span>
                                            </div>
                                            <span class="status-badge <?php echo $msg['status']; ?>"><?php echo $msg['status']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2><i class="fas fa-user-plus"></i> Recent Users</h2>
                            <a href="users.php" class="btn btn-sm">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentUsers)): ?>
                                <p class="empty-state">No users yet</p>
                            <?php else: ?>
                                <div class="items-list">
                                    <?php foreach ($recentUsers as $user): ?>
                                        <div class="item-row">
                                            <div class="item-info">
                                                <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                                                <span class="item-date"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                                            </div>
                                            <span class="status-badge <?php echo $user['status']; ?>"><?php echo $user['status']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>