<?php
// User Profile Get/Update API
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please login first'], 401);
}

$userId = getCurrentUserId();
$db = getDB();

if (!$db) {
    jsonResponse(['success' => false, 'message' => 'Database connection failed'], 500);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $db->prepare("SELECT u.email, u.role, u.status, u.created_at,
            p.first_name, p.last_name, p.gender, p.date_of_birth, p.phone,
            p.address, p.district, p.subcounty, p.parish, p.village,
            p.bio, p.photo, p.position, p.organization, p.skills, p.interests,
            p.facebook, p.twitter, p.instagram, p.linkedin
            FROM users u 
            LEFT JOIN user_profiles p ON u.id = p.user_id 
            WHERE u.id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(['success' => false, 'message' => 'User not found'], 404);
        }
        
        $user['has_profile'] = !empty($user['first_name']);
        $user['created_at'] = date('Y-m-d H:i:s', strtotime($user['created_at']));
        
        jsonResponse(['success' => true, 'user' => $user]);
        
    } catch (PDOException $e) {
        error_log("Profile fetch error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to fetch profile'], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $firstName = sanitize($input['first_name'] ?? '');
    $lastName = sanitize($input['last_name'] ?? '');
    
    if (empty($firstName) || empty($lastName)) {
        jsonResponse(['success' => false, 'message' => 'First name and last name are required'], 400);
    }
    
    $fields = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'gender' => sanitize($input['gender'] ?? ''),
        'date_of_birth' => sanitize($input['date_of_birth'] ?? ''),
        'phone' => sanitize($input['phone'] ?? ''),
        'address' => sanitize($input['address'] ?? ''),
        'district' => sanitize($input['district'] ?? ''),
        'subcounty' => sanitize($input['subcounty'] ?? ''),
        'parish' => sanitize($input['parish'] ?? ''),
        'village' => sanitize($input['village'] ?? ''),
        'bio' => sanitize($input['bio'] ?? ''),
        'position' => sanitize($input['position'] ?? ''),
        'organization' => sanitize($input['organization'] ?? ''),
        'skills' => sanitize($input['skills'] ?? ''),
        'interests' => sanitize($input['interests'] ?? ''),
        'facebook' => sanitize($input['facebook'] ?? ''),
        'twitter' => sanitize($input['twitter'] ?? ''),
        'instagram' => sanitize($input['instagram'] ?? ''),
        'linkedin' => sanitize($input['linkedin'] ?? '')
    ];
    
    try {
        $checkStmt = $db->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
        $checkStmt->execute([$userId]);
        $existingProfile = $checkStmt->fetch();
        
        if ($existingProfile) {
            $setClauses = [];
            $params = [];
            foreach ($fields as $key => $value) {
                $setClauses[] = "$key = ?";
                $params[] = $value;
            }
            $params[] = $userId;
            
            $sql = "UPDATE user_profiles SET " . implode(', ', $setClauses) . " WHERE user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        } else {
            $columns = array_keys($fields);
            $columns[] = 'user_id';
            $placeholders = array_fill(0, count($fields), '?');
            $placeholders[] = '?';
            
            $sql = "INSERT INTO user_profiles (" . implode(', ', $columns) . ", user_id) VALUES (" . implode(', ', $placeholders) . ")";
            $params = array_values($fields);
            $params[] = $userId;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        $logStmt = $db->prepare("INSERT INTO activity_log (user_id, action, ip_address) VALUES (?, 'profile_update', ?)");
        $logStmt->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        jsonResponse(['success' => true, 'message' => 'Profile updated successfully!']);
        
    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to update profile. Please try again.'], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $stmt = $db->prepare("DELETE FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        jsonResponse(['success' => true, 'message' => 'Profile deleted successfully']);
        
    } catch (PDOException $e) {
        error_log("Profile delete error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to delete profile'], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);