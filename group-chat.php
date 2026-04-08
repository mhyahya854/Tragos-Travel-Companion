<?php
$page_title = 'Group Chat';
include 'includes/header.php';

requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: groups.php');
    exit();
}

$group_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Check if user is a member of this group
$member_query = "SELECT gm.role, g.name as group_name 
                 FROM group_members gm 
                 JOIN groups_table g ON gm.group_id = g.id 
                 WHERE gm.group_id = :group_id AND gm.user_id = :user_id";
$member_stmt = $db->prepare($member_query);
$member_stmt->bindParam(':group_id', $group_id);
$member_stmt->bindParam(':user_id', $current_user['id']);
$member_stmt->execute();

if ($member_stmt->rowCount() == 0) {
    header('Location: group-details.php?id=' . $group_id);
    exit();
}

$membership = $member_stmt->fetch(PDO::FETCH_ASSOC);
$page_title = $membership['group_name'] . ' - Chat';

// Handle new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = sanitizeInput($_POST['message']);
    
    if (!empty($message)) {
        $insert_query = "INSERT INTO chat_messages (group_id, user_id, message) VALUES (:group_id, :user_id, :message)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':group_id', $group_id);
        $insert_stmt->bindParam(':user_id', $current_user['id']);
        $insert_stmt->bindParam(':message', $message);
        $insert_stmt->execute();
        
        // Create notifications for other group members
        $notify_query = "INSERT INTO notifications (user_id, type, title, message, related_id)
                        SELECT gm.user_id, 'new_message', 'New message in group', :notification_message, :group_id
                        FROM group_members gm 
                        WHERE gm.group_id = :group_id AND gm.user_id != :current_user_id";
        $notify_stmt = $db->prepare($notify_query);
        $notification_message = $current_user['display_name'] . ' posted in ' . $membership['group_name'];
        $notify_stmt->bindParam(':notification_message', $notification_message);
        $notify_stmt->bindParam(':group_id', $group_id);
        $notify_stmt->bindParam(':current_user_id', $current_user['id']);
        $notify_stmt->execute();
    }
    
    // Redirect to prevent resubmission
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// Get chat messages
$messages_query = "SELECT cm.*, u.display_name, u.username, u.profile_picture 
                   FROM chat_messages cm 
                   JOIN users u ON cm.user_id = u.id 
                   WHERE cm.group_id = :group_id AND cm.is_deleted = FALSE 
                   ORDER BY cm.created_at ASC";
$messages_stmt = $db->prepare($messages_query);
$messages_stmt->bindParam(':group_id', $group_id);
$messages_stmt->execute();
$messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get group members for sidebar
$members_query = "SELECT gm.role, u.id, u.username, u.display_name, u.profile_picture, u.location 
                  FROM group_members gm 
                  JOIN users u ON gm.user_id = u.id 
                  WHERE gm.group_id = :group_id 
                  ORDER BY 
                    CASE gm.role 
                        WHEN 'owner' THEN 1 
                        WHEN 'admin' THEN 2 
                        WHEN 'member' THEN 3 
                    END, u.display_name ASC";
$members_stmt = $db->prepare($members_query);
$members_stmt->bindParam(':group_id', $group_id);
$members_stmt->execute();
$members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main-content">
    <div class="chat-container">
        <div class="chat-header">
            <div class="chat-header-info">
                <a href="group-details.php?id=<?php echo $group_id; ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="group-info">
                    <h2><?php echo htmlspecialchars($membership['group_name']); ?></h2>
                    <span class="member-count"><?php echo count($members); ?> members</span>
                </div>
            </div>
            <div class="chat-header-actions">
                <button class="btn btn-outline btn-small" onclick="toggleMembersList()">
                    <i class="fas fa-users"></i> Members
                </button>
                <a href="group-details.php?id=<?php echo $group_id; ?>" class="btn btn-outline btn-small">
                    <i class="fas fa-info-circle"></i> Info
                </a>
            </div>
        </div>
        
        <div class="chat-layout">
            <div class="chat-main">
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($messages)): ?>
                        <div class="no-messages">
                            <i class="fas fa-comments"></i>
                            <h3>No messages yet</h3>
                            <p>Be the first to start the conversation!</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $current_date = '';
                        foreach ($messages as $message): 
                            $message_date = date('Y-m-d', strtotime($message['created_at']));
                            if ($message_date !== $current_date):
                                $current_date = $message_date;
                        ?>
                            <div class="date-separator">
                                <span><?php echo date('F j, Y', strtotime($message['created_at'])); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="chat-message <?php echo $message['user_id'] == $current_user['id'] ? 'own-message' : ''; ?>">
                            <div class="message-avatar">
                                <img src="assets/images/<?php echo $message['profile_picture']; ?>" alt="Profile">
                            </div>
                            <div class="message-content">
                                <div class="message-header">
                                    <span class="message-author"><?php echo htmlspecialchars($message['display_name'] ?: $message['username']); ?></span>
                                    <span class="message-time"><?php echo date('g:i A', strtotime($message['created_at'])); ?></span>
                                </div>
                                <div class="message-text">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input-container">
                    <form method="POST" class="chat-form" id="chatForm">
                        <div class="input-group">
                            <input type="text" name="message" class="message-input" placeholder="Type your message..." 
                                   autocomplete="off" maxlength="1000" required>
                            <button type="submit" class="send-button">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="chat-sidebar" id="chatSidebar">
                <div class="sidebar-header">
                    <h3>Members (<?php echo count($members); ?>)</h3>
                    <button class="close-sidebar" onclick="toggleMembersList()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="members-list">
                    <?php foreach ($members as $member): ?>
                        <div class="member-item">
                            <img src="assets/images/<?php echo $member['profile_picture']; ?>" alt="Member" class="member-avatar">
                            <div class="member-info">
                                <div class="member-name">
                                    <?php echo htmlspecialchars($member['display_name'] ?: $member['username']); ?>
                                    <?php if ($member['id'] == $current_user['id']): ?>
                                        <span class="you-badge">You</span>
                                    <?php endif; ?>
                                </div>
                                <div class="member-details">
                                    <?php if ($member['role'] != 'member'): ?>
                                        <span class="member-role role-<?php echo $member['role']; ?>">
                                            <?php echo ucfirst($member['role']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($member['location']): ?>
                                        <span class="member-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($member['location']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.chat-container {
    height: calc(100vh - 80px);
    display: flex;
    flex-direction: column;
    background: var(--bg-primary);
}

.chat-header {
    background: var(--bg-primary);
    border-bottom: 1px solid var(--border-color);
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow-sm);
}

.chat-header-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.back-link {
    color: var(--primary-color);
    font-size: 1.2rem;
    padding: 0.5rem;
    border-radius: 50%;
    transition: var(--transition);
}

.back-link:hover {
    background: var(--bg-secondary);
}

.group-info h2 {
    margin: 0;
    color: var(--primary-color);
    font-size: 1.5rem;
}

.member-count {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.chat-header-actions {
    display: flex;
    gap: 0.5rem;
}

.chat-layout {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.chat-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 2rem;
    background: var(--bg-secondary);
}

.no-messages {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.no-messages i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.date-separator {
    text-align: center;
    margin: 2rem 0 1rem;
}

.date-separator span {
    background: var(--bg-primary);
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    font-size: 0.85rem;
    color: var(--text-muted);
    border: 1px solid var(--border-color);
}

.chat-message {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    max-width: 70%;
}

.chat-message.own-message {
    margin-left: auto;
    flex-direction: row-reverse;
}

.chat-message.own-message .message-content {
    background: var(--primary-color);
    color: var(--text-light);
}

.chat-message.own-message .message-author {
    color: rgba(255, 255, 255, 0.9);
}

.chat-message.own-message .message-time {
    color: rgba(255, 255, 255, 0.7);
}

.message-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.message-content {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 1rem;
    box-shadow: var(--shadow-sm);
    flex: 1;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.message-author {
    font-weight: 500;
    color: var(--primary-color);
    font-size: 0.9rem;
}

.message-time {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.message-text {
    line-height: 1.5;
    word-wrap: break-word;
}

.chat-input-container {
    background: var(--bg-primary);
    border-top: 1px solid var(--border-color);
    padding: 1rem 2rem;
}

.input-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.message-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    font-family: var(--font-family);
    font-size: 1rem;
    resize: none;
    transition: var(--transition);
}

.message-input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.send-button {
    background: var(--primary-color);
    color: var(--text-light);
    border: none;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
}

.send-button:hover {
    background: var(--primary-light);
    transform: scale(1.05);
}

.chat-sidebar {
    width: 300px;
    background: var(--bg-primary);
    border-left: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: var(--transition);
}

.chat-sidebar.active {
    transform: translateX(0);
}

.sidebar-header {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-header h3 {
    margin: 0;
    color: var(--primary-color);
}

.close-sidebar {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: var(--transition);
}

.close-sidebar:hover {
    background: var(--bg-secondary);
    color: var(--primary-color);
}

.members-list {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.member-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border-radius: var(--border-radius);
    transition: var(--transition);
    margin-bottom: 0.5rem;
}

.member-item:hover {
    background: var(--bg-secondary);
}

.member-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
}

.member-info {
    flex: 1;
}

.member-name {
    font-weight: 500;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.you-badge {
    background: var(--secondary-color);
    color: var(--primary-color);
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: var(--border-radius-sm);
    font-weight: 500;
}

.member-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.member-role {
    font-size: 0.8rem;
    padding: 0.2rem 0.5rem;
    border-radius: var(--border-radius-sm);
    font-weight: 500;
    width: fit-content;
}

.role-owner {
    background: var(--secondary-color);
    color: var(--primary-color);
}

.role-admin {
    background: var(--info-color);
    color: white;
}

.member-location {
    font-size: 0.8rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

@media (max-width: 768px) {
    .chat-header {
        padding: 1rem;
    }
    
    .chat-messages {
        padding: 1rem;
    }
    
    .chat-input-container {
        padding: 1rem;
    }
    
    .chat-message {
        max-width: 85%;
    }
    
    .chat-sidebar {
        position: fixed;
        top: 80px;
        right: 0;
        height: calc(100vh - 80px);
        z-index: 1000;
        box-shadow: var(--shadow-lg);
    }
    
    .chat-header-actions .btn {
        padding: 0.5rem;
    }
    
    .chat-header-actions .btn span {
        display: none;
    }
}
</style>

<script>
function toggleMembersList() {
    const sidebar = document.getElementById('chatSidebar');
    sidebar.classList.toggle('active');
}

// Auto-scroll to bottom
function scrollToBottom() {
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Scroll to bottom on page load
document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
    
    // Focus on input
    const messageInput = document.querySelector('.message-input');
    if (messageInput) {
        messageInput.focus();
    }
    
    // Handle form submission
    const chatForm = document.getElementById('chatForm');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            const messageInput = this.querySelector('.message-input');
            if (!messageInput.value.trim()) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            const sendButton = this.querySelector('.send-button');
            sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            sendButton.disabled = true;
        });
    }
});

// Auto-refresh messages every 5 seconds
setInterval(function() {
    // This would typically fetch new messages via AJAX
    // For now, we'll just reload the page if there are new messages
    // In a real implementation, you'd use WebSockets or polling
}, 5000);

// Handle Enter key to send message
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        const messageInput = document.querySelector('.message-input');
        if (document.activeElement === messageInput) {
            e.preventDefault();
            document.getElementById('chatForm').submit();
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>