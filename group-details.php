<?php
$page_title = 'Group Details';
include 'includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: groups.php');
    exit();
}

$group_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Get group details
$query = "SELECT g.*, u.display_name as owner_name, u.username as owner_username, u.profile_picture as owner_picture 
          FROM groups_table g 
          JOIN users u ON g.owner_id = u.id 
          WHERE g.id = :group_id AND g.is_active = TRUE";
$stmt = $db->prepare($query);
$stmt->bindParam(':group_id', $group_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header('Location: groups.php');
    exit();
}

$group = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user is a member
$is_member = false;
$user_role = null;
$join_request_status = null;

if ($current_user) {
    // Check membership
    $member_query = "SELECT role FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
    $member_stmt = $db->prepare($member_query);
    $member_stmt->bindParam(':group_id', $group_id);
    $member_stmt->bindParam(':user_id', $current_user['id']);
    $member_stmt->execute();
    
    if ($member_stmt->rowCount() > 0) {
        $is_member = true;
        $user_role = $member_stmt->fetch(PDO::FETCH_ASSOC)['role'];
    } else {
        // Check if there's a pending join request
        $request_query = "SELECT status FROM join_requests WHERE group_id = :group_id AND user_id = :user_id ORDER BY requested_at DESC LIMIT 1";
        $request_stmt = $db->prepare($request_query);
        $request_stmt->bindParam(':group_id', $group_id);
        $request_stmt->bindParam(':user_id', $current_user['id']);
        $request_stmt->execute();
        
        if ($request_stmt->rowCount() > 0) {
            $join_request_status = $request_stmt->fetch(PDO::FETCH_ASSOC)['status'];
        }
    }
}

// Get group members
$members_query = "SELECT gm.role, gm.joined_at, u.id, u.username, u.display_name, u.profile_picture, u.location 
                  FROM group_members gm 
                  JOIN users u ON gm.user_id = u.id 
                  WHERE gm.group_id = :group_id 
                  ORDER BY 
                    CASE gm.role 
                        WHEN 'owner' THEN 1 
                        WHEN 'admin' THEN 2 
                        WHEN 'member' THEN 3 
                    END, gm.joined_at ASC";
$members_stmt = $db->prepare($members_query);
$members_stmt->bindParam(':group_id', $group_id);
$members_stmt->execute();
$members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent chat messages (if user is a member)
$recent_messages = [];
if ($is_member) {
    $messages_query = "SELECT cm.message, cm.created_at, u.display_name, u.username, u.profile_picture 
                       FROM chat_messages cm 
                       JOIN users u ON cm.user_id = u.id 
                       WHERE cm.group_id = :group_id AND cm.is_deleted = FALSE 
                       ORDER BY cm.created_at DESC 
                       LIMIT 5";
    $messages_stmt = $db->prepare($messages_query);
    $messages_stmt->bindParam(':group_id', $group_id);
    $messages_stmt->execute();
    $recent_messages = array_reverse($messages_stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Handle join request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if (!$current_user) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
    
    if ($_POST['action'] == 'join' && !$is_member && !$join_request_status) {
        if ($group['privacy'] == 'public') {
            // Join immediately for public groups
            $join_query = "INSERT INTO group_members (group_id, user_id, role) VALUES (:group_id, :user_id, 'member')";
            $join_stmt = $db->prepare($join_query);
            $join_stmt->bindParam(':group_id', $group_id);
            $join_stmt->bindParam(':user_id', $current_user['id']);
            
            if ($join_stmt->execute()) {
                // Update member count
                $update_count = "UPDATE groups_table SET current_members = current_members + 1 WHERE id = :group_id";
                $update_stmt = $db->prepare($update_count);
                $update_stmt->bindParam(':group_id', $group_id);
                $update_stmt->execute();
                
                // Create notification for group owner
                $notif_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                               VALUES (:owner_id, 'join_approved', 'New Member Joined', :message, :group_id)";
                $notif_stmt = $db->prepare($notif_query);
                $notif_stmt->bindParam(':owner_id', $group['owner_id']);
                $message = $current_user['display_name'] . ' joined your group "' . $group['name'] . '"';
                $notif_stmt->bindParam(':message', $message);
                $notif_stmt->bindParam(':group_id', $group_id);
                $notif_stmt->execute();
                
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit();
            }
        } else {
            // Send join request for private groups
            $request_message = sanitizeInput($_POST['message'] ?? '');
            $request_query = "INSERT INTO join_requests (group_id, user_id, message) VALUES (:group_id, :user_id, :message)";
            $request_stmt = $db->prepare($request_query);
            $request_stmt->bindParam(':group_id', $group_id);
            $request_stmt->bindParam(':user_id', $current_user['id']);
            $request_stmt->bindParam(':message', $request_message);
            
            if ($request_stmt->execute()) {
                // Create notification for group owner
                $notif_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                               VALUES (:owner_id, 'join_request', 'New Join Request', :message, :group_id)";
                $notif_stmt = $db->prepare($notif_query);
                $notif_stmt->bindParam(':owner_id', $group['owner_id']);
                $message = $current_user['display_name'] . ' wants to join "' . $group['name'] . '"';
                $notif_stmt->bindParam(':message', $message);
                $notif_stmt->bindParam(':group_id', $group_id);
                $notif_stmt->execute();
                
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit();
            }
        }
    }
}
?>

<main class="main-content">
    <section class="group-hero-section">
        <div class="group-hero-bg">
            <img src="assets/images/<?php echo $group['group_image'] ?: 'other.png'; ?>" alt="<?php echo htmlspecialchars($group['name']); ?>">
            <div class="group-hero-overlay"></div>
        </div>
        <div class="container">
            <div class="group-hero-content">
                <div class="group-badges">
                    <span class="category-badge category-<?php echo $group['category']; ?>">
                        <?php echo ucfirst($group['category']); ?>
                    </span>
                    <?php if ($group['privacy'] === 'private'): ?>
                        <span class="privacy-badge">
                            <i class="fas fa-lock"></i> Private
                        </span>
                    <?php endif; ?>
                </div>
                <h1><?php echo htmlspecialchars($group['name']); ?></h1>
                <div class="group-meta">
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($group['destination']); ?>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-users"></i>
                        <?php echo $group['current_members']; ?>/<?php echo $group['max_members']; ?> members
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        Created <?php echo date('M j, Y', strtotime($group['created_at'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="group-content-section">
        <div class="container">
            <div class="group-layout">
                <div class="group-main">
                    <div class="group-description-card">
                        <h3>About This Group</h3>
                        <p><?php echo nl2br(htmlspecialchars($group['description'])); ?></p>
                    </div>

                    <?php if ($is_member): ?>
                        <div class="group-chat-preview">
                            <div class="chat-header">
                                <h3>Recent Messages</h3>
                                <a href="group-chat.php?id=<?php echo $group_id; ?>" class="btn btn-outline btn-small">
                                    View Full Chat
                                </a>
                            </div>
                            <div class="chat-messages-preview">
                                <?php if (empty($recent_messages)): ?>
                                    <p class="no-messages">No messages yet. Be the first to start the conversation!</p>
                                <?php else: ?>
                                    <?php foreach ($recent_messages as $message): ?>
                                        <div class="message-preview">
                                            <img src="assets/images/<?php echo $message['profile_picture']; ?>" alt="Profile" class="message-avatar">
                                            <div class="message-content">
                                                <div class="message-header">
                                                    <span class="message-author"><?php echo htmlspecialchars($message['display_name'] ?: $message['username']); ?></span>
                                                    <span class="message-time"><?php echo date('M j, g:i A', strtotime($message['created_at'])); ?></span>
                                                </div>
                                                <p><?php echo htmlspecialchars($message['message']); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="group-sidebar">
                    <div class="group-actions-card">
                        <?php if (!$current_user): ?>
                            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary w-full">
                                <i class="fas fa-sign-in-alt"></i> Login to Join
                            </a>
                        <?php elseif ($current_user['id'] == $group['owner_id']): ?>
                            <div class="owner-actions">
                                <a href="edit-group.php?id=<?php echo $group_id; ?>" class="btn btn-primary w-full">
                                    <i class="fas fa-edit"></i> Edit Group
                                </a>
                                <a href="manage-requests.php?id=<?php echo $group_id; ?>" class="btn btn-outline w-full">
                                    <i class="fas fa-user-check"></i> Manage Requests
                                </a>
                                <a href="group-chat.php?id=<?php echo $group_id; ?>" class="btn btn-secondary w-full">
                                    <i class="fas fa-comments"></i> Group Chat
                                </a>
                            </div>
                        <?php elseif ($is_member): ?>
                            <div class="member-actions">
                                <a href="group-chat.php?id=<?php echo $group_id; ?>" class="btn btn-primary w-full">
                                    <i class="fas fa-comments"></i> Group Chat
                                </a>
                                <button onclick="leaveGroup(<?php echo $group_id; ?>)" class="btn btn-outline w-full">
                                    <i class="fas fa-sign-out-alt"></i> Leave Group
                                </button>
                            </div>
                        <?php elseif ($join_request_status == 'pending'): ?>
                            <div class="request-pending">
                                <i class="fas fa-clock"></i>
                                <p>Your join request is pending approval</p>
                            </div>
                        <?php elseif ($join_request_status == 'rejected'): ?>
                            <div class="request-rejected">
                                <i class="fas fa-times-circle"></i>
                                <p>Your join request was declined</p>
                            </div>
                        <?php elseif ($join_request_status == 'blocked'): ?>
                            <div class="request-blocked">
                                <i class="fas fa-ban"></i>
                                <p>You are blocked from joining this group</p>
                            </div>
                        <?php else: ?>
                            <?php if ($group['privacy'] == 'public'): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="join">
                                    <button type="submit" class="btn btn-primary w-full">
                                        <i class="fas fa-user-plus"></i> Join Group
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="join-request-form">
                                    <h4>Request to Join</h4>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="join">
                                        <div class="form-group">
                                            <label for="message">Message (optional)</label>
                                            <textarea name="message" id="message" class="form-control" rows="3" 
                                                    placeholder="Tell the group owner why you'd like to join..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-full">
                                            <i class="fas fa-paper-plane"></i> Send Request
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="group-owner-card">
                        <h4>Group Owner</h4>
                        <div class="owner-info">
                            <img src="assets/images/<?php echo $group['owner_picture']; ?>" alt="Owner" class="owner-avatar">
                            <div class="owner-details">
                                <h5><?php echo htmlspecialchars($group['owner_name'] ?: $group['owner_username']); ?></h5>
                                <p>@<?php echo htmlspecialchars($group['owner_username']); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="group-members-card">
                        <div class="members-header">
                            <h4>Members (<?php echo count($members); ?>)</h4>
                            <?php if ($is_member): ?>
                                <a href="group-members.php?id=<?php echo $group_id; ?>" class="view-all-link">View All</a>
                            <?php endif; ?>
                        </div>
                        <div class="members-list">
                            <?php foreach (array_slice($members, 0, 6) as $member): ?>
                                <div class="member-item">
                                    <img src="assets/images/<?php echo $member['profile_picture']; ?>" alt="Member" class="member-avatar">
                                    <div class="member-info">
                                        <span class="member-name"><?php echo htmlspecialchars($member['display_name'] ?: $member['username']); ?></span>
                                        <?php if ($member['role'] != 'member'): ?>
                                            <span class="member-role"><?php echo ucfirst($member['role']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($members) > 6): ?>
                                <div class="more-members">
                                    <span>+<?php echo count($members) - 6; ?> more members</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.group-hero-section {
    position: relative;
    height: 400px;
    display: flex;
    align-items: center;
    overflow: hidden;
}

.group-hero-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -2;
}

.group-hero-bg img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.group-hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(44, 24, 16, 0.8) 0%, rgba(139, 69, 19, 0.6) 100%);
    z-index: -1;
}

.group-hero-content {
    color: var(--text-light);
    position: relative;
    z-index: 1;
}

.group-badges {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.group-hero-content h1 {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--text-light);
}

.group-meta {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.9);
}

.group-content-section {
    padding: 3rem 0;
}

.group-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 3rem;
}

.group-main {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.group-description-card,
.group-chat-preview,
.group-actions-card,
.group-owner-card,
.group-members-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
}

.group-description-card h3,
.group-owner-card h4,
.group-members-card h4 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.group-sidebar {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.owner-actions,
.member-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.request-pending,
.request-rejected,
.request-blocked {
    text-align: center;
    padding: 2rem;
    border-radius: var(--border-radius);
}

.request-pending {
    background: rgba(255, 140, 0, 0.1);
    color: var(--warning-color);
}

.request-rejected {
    background: rgba(220, 20, 60, 0.1);
    color: var(--error-color);
}

.request-blocked {
    background: rgba(128, 128, 128, 0.1);
    color: var(--text-muted);
}

.request-pending i,
.request-rejected i,
.request-blocked i {
    font-size: 2rem;
    margin-bottom: 1rem;
    display: block;
}

.join-request-form h4 {
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.owner-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.owner-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.owner-details h5 {
    margin: 0;
    color: var(--primary-color);
}

.owner-details p {
    margin: 0;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.members-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.view-all-link {
    color: var(--primary-color);
    font-size: 0.9rem;
    text-decoration: none;
}

.members-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.member-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.member-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.member-info {
    flex: 1;
}

.member-name {
    display: block;
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.9rem;
}

.member-role {
    font-size: 0.8rem;
    color: var(--primary-color);
    background: var(--bg-secondary);
    padding: 0.2rem 0.5rem;
    border-radius: var(--border-radius-sm);
}

.more-members {
    text-align: center;
    padding: 0.5rem;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.chat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.chat-messages-preview {
    max-height: 300px;
    overflow-y: auto;
}

.message-preview {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-light);
}

.message-preview:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.message-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.message-content {
    flex: 1;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
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

.message-content p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.no-messages {
    text-align: center;
    color: var(--text-muted);
    font-style: italic;
    padding: 2rem;
}

@media (max-width: 768px) {
    .group-hero-content h1 {
        font-size: 2rem;
    }
    
    .group-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .group-layout {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .group-sidebar {
        order: -1;
    }
}
</style>

<script>
function leaveGroup(groupId) {
    if (confirm('Are you sure you want to leave this group?')) {
        fetch('api/leave-group.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ group_id: groupId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'groups.php';
            } else {
                alert('Error leaving group: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while leaving the group.');
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>