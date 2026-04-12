<?php
header('Content-Type: application/json');
require_once '../config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

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
    
    // Check if user is the owner
    $owner_check = "SELECT * FROM groups_table WHERE id = :group_id AND owner_id = :user_id";
    $owner_stmt = $db->prepare($owner_check);
    $owner_stmt->bindParam(':group_id', $group_id);
    $owner_stmt->bindParam(':user_id', $user_id);
    $owner_stmt->execute();
    
    if ($owner_stmt->rowCount() == 0) {
        throw new Exception('You are not authorized to delete this group');
    }
    
    $group = $owner_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete related data in order
    
    // 1. Delete join requests
    $delete_requests = "DELETE FROM join_requests WHERE group_id = :group_id";
    $requests_stmt = $db->prepare($delete_requests);
    $requests_stmt->bindParam(':group_id', $group_id);
    $requests_stmt->execute();
    
    // 2. Delete chat messages
    $delete_messages = "DELETE FROM chat_messages WHERE group_id = :group_id";
    $messages_stmt = $db->prepare($delete_messages);
    $messages_stmt->bindParam(':group_id', $group_id);
    $messages_stmt->execute();
    
    // 3. Delete group members
    $delete_members = "DELETE FROM group_members WHERE group_id = :group_id";
    $members_stmt = $db->prepare($delete_members);
    $members_stmt->bindParam(':group_id', $group_id);
    $members_stmt->execute();
    
    // 4. Delete notifications related to this group
    $delete_notifications = "DELETE FROM notifications WHERE related_id = :group_id AND type IN ('join_request', 'member_joined', 'member_left', 'group_update')";
    $notifications_stmt = $db->prepare($delete_notifications);
    $notifications_stmt->bindParam(':group_id', $group_id);
    $notifications_stmt->execute();
    
    // 5. Delete the group itself
    $delete_group = "DELETE FROM groups_table WHERE id = :group_id";
    $group_stmt = $db->prepare($delete_group);
    $group_stmt->bindParam(':group_id', $group_id);
    $group_stmt->execute();
    
    // 6. Delete group image if it's not a default one
    if ($group['group_image'] && !in_array($group['group_image'], ['backpacking.png', 'luxury.png', 'adventure.png', 'cultural.png', 'food.png', 'photography.png', 'solo.png', 'family.png', 'business.png', 'other.png'])) {
        $image_path = '../assets/images/' . $group['group_image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Group deleted successfully'
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
