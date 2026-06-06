<?php
// Create missing tables
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

$queries = [
    "CREATE TABLE IF NOT EXISTS social_media (
        id INT PRIMARY KEY AUTO_INCREMENT,
        facebook VARCHAR(255),
        twitter VARCHAR(255),
        instagram VARCHAR(255),
        linkedin VARCHAR(255),
        youtube VARCHAR(255),
        whatsapp VARCHAR(50),
        tiktok VARCHAR(255),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS message_replies (
        id INT PRIMARY KEY AUTO_INCREMENT,
        message_id INT NOT NULL,
        reply_text TEXT NOT NULL,
        admin_email VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS reply_notifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(100) NOT NULL,
        message_id INT NOT NULL,
        reply_text TEXT NOT NULL,
        read_status ENUM('unread', 'read') DEFAULT 'unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS team (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        position VARCHAR(100) NOT NULL,
        bio TEXT,
        photo VARCHAR(255),
        email VARCHAR(100),
        phone VARCHAR(20),
        order_num INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS opportunities (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(200) NOT NULL,
        type ENUM('job', 'internship', 'volunteer', 'training', 'scholarship') NOT NULL,
        organization VARCHAR(200),
        description TEXT NOT NULL,
        requirements TEXT,
        location VARCHAR(100),
        deadline DATE,
        link VARCHAR(500),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
];

$results = [];
foreach ($queries as $sql) {
    try {
        $db->exec($sql);
        $results[] = ['success' => true, 'sql' => substr($sql, 0, 50) . '...'];
    } catch (PDOException $e) {
        $results[] = ['success' => false, 'error' => $e->getMessage()];
    }
}

echo "<h2>Database Setup Results</h2>";
echo "<pre>";
foreach ($results as $r) {
    if ($r['success']) {
        echo "✓ Created successfully\n";
    } else {
        echo "✗ Error: " . $r['error'] . "\n";
    }
}
echo "</pre>";
echo "<p><a href='index.php'>Go to Dashboard</a></p>";
?>