<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

try {
    // Get unread count
    $count_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = :user_id AND is_read = FALSE";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':user_id', $user_id);
    $count_stmt->execute();
    $unread_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
    
    // Get recent notifications
    $notifications_query = "SELECT * FROM notifications 
                           WHERE user_id = :user_id 
                           ORDER BY created_at DESC 
                           LIMIT 10";
    $notifications_stmt = $db->prepare($notifications_query);
    $notifications_stmt->bindParam(':user_id', $user_id);
    $notifications_stmt->execute();
    $notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'unread_count' => (int)$unread_count,
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to fetch notifications'
    ]);
}
?>