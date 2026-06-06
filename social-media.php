<?php
// Social Media Settings Admin Page
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$db = getDB();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facebook = sanitize($_POST['facebook'] ?? '');
    $twitter = sanitize($_POST['twitter'] ?? '');
    $instagram = sanitize($_POST['instagram'] ?? '');
    $linkedin = sanitize($_POST['linkedin'] ?? '');
    $youtube = sanitize($_POST['youtube'] ?? '');
    $whatsapp = sanitize($_POST['whatsapp'] ?? '');
    $tiktok = sanitize($_POST['tiktok'] ?? '');
    
    try {
        $stmt = $db->query("SELECT id FROM social_media LIMIT 1");
        $exists = $stmt->fetch();
        
        if ($exists) {
            $updateSql = "UPDATE social_media SET facebook=?, twitter=?, instagram=?, linkedin=?, youtube=?, whatsapp=?, tiktok=? WHERE id=?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([$facebook, $twitter, $instagram, $linkedin, $youtube, $whatsapp, $tiktok, $exists['id']]);
        } else {
            $insertSql = "INSERT INTO social_media (facebook, twitter, instagram, linkedin, youtube, whatsapp, tiktok) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $db->prepare($insertSql);
            $insertStmt->execute([$facebook, $twitter, $instagram, $linkedin, $youtube, $whatsapp, $tiktok]);
        }
        $message = 'Social media links saved successfully!';
    } catch (PDOException $e) {
        $error = 'Database table missing. <a href="setup-tables.php">Click here to setup database</a>';
    }
}

// Get current values
try {
    $stmt = $db->query("SELECT * FROM social_media LIMIT 1");
    $social = $stmt->fetch();
} catch (PDOException $e) {
    $social = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Media Settings | Kayunga Admin</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-card { background: white; border-radius: 12px; padding: 30px; max-width: 600px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-weight: 500; }
        .form-group label i { width: 24px; color: #666; }
        .form-group input { width: 100%; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 1rem; }
        .form-group input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .social-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; }
        .facebook { background: #1877F2; }
        .twitter { background: #1DA1F2; }
        .instagram { background: #E4405F; }
        .linkedin { background: #0A66C2; }
        .youtube { background: #FF0000; }
        .whatsapp { background: #25D366; }
        .tiktok { background: #000000; }
        .preview-section { margin-top: 30px; padding-top: 30px; border-top: 1px solid #e5e7eb; }
        .preview-section h3 { margin-bottom: 15px; }
        .social-links { display: flex; gap: 15px; flex-wrap: wrap; }
        .social-link { display: flex; align-items: center; gap: 8px; padding: 10px 15px; background: #f5f7fa; border-radius: 8px; text-decoration: none; color: #333; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-main">
            <header class="admin-header">
                <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <h1>Social Media Settings</h1>
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
                
                <div class="settings-card">
                    <p style="margin-bottom: 20px; color: #666;">Enter your social media profile links below. Leave empty to hide.</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fab fa-facebook social-icon facebook"></i> Facebook</label>
                            <input type="url" name="facebook" value="<?php echo htmlspecialchars($social['facebook'] ?? ''); ?>" placeholder="https://facebook.com/yourpage">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-twitter social-icon twitter"></i> Twitter / X</label>
                            <input type="url" name="twitter" value="<?php echo htmlspecialchars($social['twitter'] ?? ''); ?>" placeholder="https://x.com/yourpage">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-instagram social-icon instagram"></i> Instagram</label>
                            <input type="url" name="instagram" value="<?php echo htmlspecialchars($social['instagram'] ?? ''); ?>" placeholder="https://instagram.com/yourpage">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-linkedin social-icon linkedin"></i> LinkedIn</label>
                            <input type="url" name="linkedin" value="<?php echo htmlspecialchars($social['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/in/yourpage">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-youtube social-icon youtube"></i> YouTube</label>
                            <input type="url" name="youtube" value="<?php echo htmlspecialchars($social['youtube'] ?? ''); ?>" placeholder="https://youtube.com/@yourchannel">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-whatsapp social-icon whatsapp"></i> WhatsApp</label>
                            <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($social['whatsapp'] ?? ''); ?>" placeholder="+256700000000">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fab fa-tiktok social-icon tiktok"></i> TikTok</label>
                            <input type="url" name="tiktok" value="<?php echo htmlspecialchars($social['tiktok'] ?? ''); ?>" placeholder="https://tiktok.com/@yourpage">
                        </div>
                        
                        <button type="submit" class="btn" style="margin-top: 10px;">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>