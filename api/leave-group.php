<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['group_id']) || !is_numeric($input['group_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
    exit();
}

$group_id = (int)$input['group_id'];
$user_id = $_SESSION['user_id'];

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Check if user is a member and not the owner
    $check_query = "SELECT gm.role, g.name as group_name, g.owner_id 
                    FROM group_members gm 
                    JOIN groups_table g ON gm.group_id = g.id 
                    WHERE gm.group_id = :group_id AND gm.user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':group_id', $group_id);
    $check_stmt->bindParam(':user_id', $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        throw new Exception('You are not a member of this group');
    }
    
    $membership = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($membership['role'] === 'owner') {
        throw new Exception('Group owners cannot leave their own group. Transfer ownership or delete the group instead.');
    }
    
    // Remove user from group
    $leave_query = "DELETE FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
    $leave_stmt = $db->prepare($leave_query);
    $leave_stmt->bindParam(':group_id', $group_id);
    $leave_stmt->bindParam(':user_id', $user_id);
    $leave_stmt->execute();
    
    // Update member count
    $update_count = "UPDATE groups_table SET current_members = current_members - 1 WHERE id = :group_id";
    $update_stmt = $db->prepare($update_count);
    $update_stmt->bindParam(':group_id', $group_id);
    $update_stmt->execute();
    
    // Get user info for notification
    $user_query = "SELECT display_name, username FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    $user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Create notification for group owner
    $notif_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                   VALUES (:owner_id, 'member_left', 'Member Left Group', :message, :group_id)";
    $notif_stmt = $db->prepare($notif_query);
    $notif_stmt->bindParam(':owner_id', $membership['owner_id']);
    $message = ($user_info['display_name'] ?: $user_info['username']) . ' left the group "' . $membership['group_name'] . '"';
    $notif_stmt->bindParam(':message', $message);
    $notif_stmt->bindParam(':group_id', $group_id);
    $notif_stmt->execute();
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Successfully left the group'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>