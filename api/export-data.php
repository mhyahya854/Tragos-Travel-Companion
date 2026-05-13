<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

try {
    // Get user data
    $user_query = "SELECT * FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Remove sensitive data
    unset($user_data['password']);
    
    // Get user's groups
    $groups_query = "SELECT g.*, gm.role, gm.joined_at 
                    FROM groups_table g 
                    JOIN group_members gm ON g.id = gm.group_id 
                    WHERE gm.user_id = :user_id";
    $groups_stmt = $db->prepare($groups_query);
    $groups_stmt->bindParam(':user_id', $user_id);
    $groups_stmt->execute();
    $groups_data = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's messages
    $messages_query = "SELECT cm.*, g.name as group_name 
                      FROM chat_messages cm 
                      JOIN groups_table g ON cm.group_id = g.id 
                      WHERE cm.user_id = :user_id AND cm.is_deleted = FALSE 
                      ORDER BY cm.created_at DESC";
    $messages_stmt = $db->prepare($messages_query);
    $messages_stmt->bindParam(':user_id', $user_id);
    $messages_stmt->execute();
    $messages_data = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user's notifications
    $notifications_query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC";
    $notifications_stmt = $db->prepare($notifications_query);
    $notifications_stmt->bindParam(':user_id', $user_id);
    $notifications_stmt->execute();
    $notifications_data = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compile all data
    $export_data = [
        'export_info' => [
            'exported_at' => date('Y-m-d H:i:s'),
            'user_id' => $user_id,
            'username' => $user_data['username']
        ],
        'profile' => $user_data,
        'groups' => $groups_data,
        'messages' => $messages_data,
        'notifications' => $notifications_data,
        'statistics' => [
            'total_groups' => count($groups_data),
            'total_messages' => count($messages_data),
            'total_notifications' => count($notifications_data)
        ]
    ];
    
    // Set headers for download
    $filename = 'tragos_data_' . $user_data['username'] . '_' . date('Y-m-d') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen(json_encode($export_data, JSON_PRETTY_PRINT)));
    
    // Output the data
    echo json_encode($export_data, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    header('Content-Type: text/html');
    echo '<script>alert("Error exporting data: ' . $e->getMessage() . '"); window.history.back();</script>';
}
?>