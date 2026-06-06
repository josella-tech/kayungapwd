<?php
// Render Team HTML
header('Content-Type: text/html');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

try {
    $stmt = $db->query("SELECT id, name, position, bio, photo FROM team WHERE status = 'active' ORDER BY order_num ASC");
    $team = $stmt->fetchAll();
    
    if ($team) {
        foreach ($team as $m) {
            $img = $m['photo'] ? 'uploads/team/' . $m['photo'] : 'images/logo.png';
            $name = htmlspecialchars($m['name']);
            $pos = htmlspecialchars($m['position'] ?: '');
            $bio = htmlspecialchars($m['bio'] ?: '');
            
            echo '<div class="team-card">';
            echo '<div class="team-image" style="width:350px;height:350px;overflow:hidden;background:#f5f5f5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
            echo '<img src="' . $img . '" alt="' . $name . '" style="width:350px;height:350px;object-fit:fill;" onerror="this.src=\'images/logo.png\'">';
            echo '</div>';
            echo '<div class="team-info"><h3>' . $name . '</h3><p class="team-role">' . $pos . '</p><p class="team-bio">' . $bio . '</p></div>';
            echo '</div>';
        }
    } else {
        echo '<p style="color:#999;">No team members found</p>';
    }
} catch (PDOException $e) {
    echo '<p style="color:red;">Error loading team</p>';
}