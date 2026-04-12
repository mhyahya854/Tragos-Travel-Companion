<?php
// Helper functions for the TRAGOS application

function getCurrentUser() {
    global $current_user;
    return $current_user;
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfLoggedIn($redirect_to = 'index.php') {
    if (isset($_SESSION['user_id'])) {
        header('Location: ' . $redirect_to);
        exit();
    }
}

function formatTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

function generateNotification($db, $user_id, $type, $message, $related_id = null) {
    $query = "INSERT INTO notifications (user_id, type, message, related_id, created_at) 
              VALUES (:user_id, :type, :message, :related_id, CURRENT_TIMESTAMP)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':related_id', $related_id);
    return $stmt->execute();
}

function uploadImage($file, $upload_dir = 'assets/images/', $prefix = 'img_') {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Please use JPG, PNG, or GIF.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = $prefix . time() . '_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

function deleteImage($filename, $upload_dir = 'assets/images/') {
    $default_images = [
        'backpacking.png', 'luxury.png', 'adventure.png', 'cultural.png', 
        'food.png', 'photography.png', 'solo.png', 'family.png', 
        'business.png', 'other.png', 'default-avatar.png'
    ];
    
    if (!in_array($filename, $default_images)) {
        $file_path = $upload_dir . $filename;
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
    }
    return false;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function isGroupMember($db, $user_id, $group_id) {
    $query = "SELECT id FROM group_members WHERE user_id = :user_id AND group_id = :group_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':group_id', $group_id);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

function isGroupOwner($db, $user_id, $group_id) {
    $query = "SELECT id FROM groups_table WHERE id = :group_id AND owner_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':group_id', $group_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

function getGroupMemberCount($db, $group_id) {
    $query = "SELECT COUNT(*) as count FROM group_members WHERE group_id = :group_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':group_id', $group_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

function updateGroupMemberCount($db, $group_id) {
    $count = getGroupMemberCount($db, $group_id);
    $query = "UPDATE groups_table SET current_members = :count WHERE id = :group_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':count', $count);
    $stmt->bindParam(':group_id', $group_id);
    return $stmt->execute();
}
?>
