<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar</title>
</head>
<body>
<aside class="admin-sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Kayunga Youth Council</h2>
        <p>Admin Panel</p>
    </div>
    <nav class="nav-links">
        <a href="index.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        
        <div class="nav-section">User Management</div>
        <a href="users.php" class="nav-item"><i class="fas fa-users"></i> All Users</a>
        <a href="leaders.php" class="nav-item"><i class="fas fa-user-tie"></i> Leaders</a>
        
        <div class="nav-section">Communication</div>
        <a href="subscribers.php" class="nav-item"><i class="fas fa-envelope"></i> Subscribers</a>
        <a href="messages-manage.php" class="nav-item"><i class="fas fa-comments"></i> Messages</a>
        
        <div class="nav-section">Content</div>
        <a href="news-manage.php" class="nav-item"><i class="fas fa-newspaper"></i> News</a>
        <a href="district-history.php" class="nav-item"><i class="fas fa-landmark"></i> District History</a>
        <a href="leadership.php" class="nav-item"><i class="fas fa-building"></i> Administration</a>
        <a href="programmes-manage.php" class="nav-item"><i class="fas fa-graduation-cap"></i> Programmes</a>
        <a href="team.php" class="nav-item"><i class="fas fa-user-friends"></i> Team</a>
        <a href="opportunities.php" class="nav-item"><i class="fas fa-briefcase"></i> Opportunities</a>
        
        <div class="nav-section">Settings</div>
        <a href="social-media.php" class="nav-item"><i class="fas fa-share-alt"></i> Social Media</a>
        <?php if (isSuperAdmin()): ?>
        <a href="admins.php" class="nav-item"><i class="fas fa-user-shield"></i> Admins</a>
        <?php endif; ?>
    </nav>
</aside>
</body>
</html>