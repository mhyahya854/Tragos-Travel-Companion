<?php
$page_title = 'Notifications';
include 'includes/header.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Mark all notifications as read when viewing this page
$mark_read_query = "UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id";
$mark_read_stmt = $db->prepare($mark_read_query);
$mark_read_stmt->bindParam(':user_id', $current_user['id']);
$mark_read_stmt->execute();

// Get all notifications
$notifications_query = "SELECT n.*, g.name as group_name 
                       FROM notifications n 
                       LEFT JOIN groups_table g ON n.related_id = g.id AND n.type IN ('join_request', 'join_approved', 'new_message', 'member_left')
                       WHERE n.user_id = :user_id 
                       ORDER BY n.created_at DESC";
$notifications_stmt = $db->prepare($notifications_query);
$notifications_stmt->bindParam(':user_id', $current_user['id']);
$notifications_stmt->execute();
$notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group notifications by date
$grouped_notifications = [];
foreach ($notifications as $notification) {
    $date = date('Y-m-d', strtotime($notification['created_at']));
    $grouped_notifications[$date][] = $notification;
}
?>

<main class="main-content">
    <section class="notifications-section">
        <div class="container">
            <div class="notifications-header">
                <h1>Notifications</h1>
                <p>Stay updated with your travel groups and activities</p>
            </div>
            
            <?php if (empty($notifications)): ?>
                <div class="no-notifications">
                    <div class="no-notifications-content">
                        <i class="fas fa-bell"></i>
                        <h3>No notifications yet</h3>
                        <p>When you join groups, receive messages, or have group activity, you'll see notifications here.</p>
                        <a href="groups.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Browse Groups
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="notifications-container">
                    <div class="notifications-actions">
                        <button onclick="clearAllNotifications()" class="btn btn-outline btn-small">
                            <i class="fas fa-trash"></i> Clear All
                        </button>
                    </div>
                    
                    <div class="notifications-list">
                        <?php foreach ($grouped_notifications as $date => $date_notifications): ?>
                            <div class="notifications-date-group">
                                <div class="date-header">
                                    <h3><?php echo formatNotificationDate($date); ?></h3>
                                </div>
                                
                                <div class="notifications-items">
                                    <?php foreach ($date_notifications as $notification): ?>
                                        <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" 
                                             data-notification-id="<?php echo $notification['id']; ?>">
                                            <div class="notification-icon">
                                                <?php echo getNotificationIcon($notification['type']); ?>
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-header">
                                                    <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                                                    <span class="notification-time">
                                                        <?php echo date('g:i A', strtotime($notification['created_at'])); ?>
                                                    </span>
                                                </div>
                                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <?php if ($notification['related_id'] && $notification['group_name']): ?>
                                                    <div class="notification-actions">
                                                        <?php if ($notification['type'] === 'join_request'): ?>
                                                            <a href="manage-requests.php?id=<?php echo $notification['related_id']; ?>" class="btn btn-primary btn-small">
                                                                Review Request
                                                            </a>
                                                        <?php elseif (in_array($notification['type'], ['new_message', 'join_approved', 'member_left'])): ?>
                                                            <a href="group-details.php?id=<?php echo $notification['related_id']; ?>" class="btn btn-outline btn-small">
                                                                View Group
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <button class="notification-dismiss" onclick="dismissNotification(<?php echo $notification['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
.notifications-section {
    padding: 4rem 0;
    background: var(--bg-secondary);
    min-height: calc(100vh - 160px);
}

.notifications-header {
    text-align: center;
    margin-bottom: 3rem;
}

.notifications-header h1 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.notifications-header p {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

.no-notifications {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.no-notifications-content {
    text-align: center;
    background: var(--bg-primary);
    padding: 4rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    max-width: 500px;
}

.no-notifications-content i {
    font-size: 3rem;
    color: var(--text-muted);
    margin-bottom: auto;
    opacity: 0.5;
}

.no-notifications-content h3 {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.no-notifications-content p {
    color: var(--text-muted);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.notifications-container {
    max-width: 800px;
    margin: 0 auto;
}

.notifications-actions {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 2rem;
}

.notifications-date-group {
    margin-bottom: 3rem;
}

.date-header {
    margin-bottom: 1rem;
}

.date-header h3 {
    color: var(--primary-color);
    font-size: 1.1rem;
    font-weight: 600;
}

.notifications-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-item {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    display: flex;
    gap: 1rem;
    transition: var(--transition);
    position: relative;
}

.notification-item:hover {
    box-shadow: var(--shadow-md);
}

.notification-item.unread {
    border-left: 4px solid var(--primary-color);
    background: linear-gradient(90deg, rgba(139, 69, 19, 0.05) 0%, var(--bg-primary) 10%);
}

.notification-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    flex-shrink: 0;
}

.notification-icon.join-request {
    background: var(--info-color);
}

.notification-icon.join-approved {
    background: var(--success-color);
}

.notification-icon.new-message {
    background: var(--primary-color);
}

.notification-icon.member-left {
    background: var(--warning-color);
}

.notification-icon.group-deleted {
    background: var(--error-color);
}

.notification-content {
    flex: 1;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.notification-header h4 {
    color: var(--primary-color);
    margin: 0;
    font-size: 1rem;
}

.notification-time {
    color: var(--text-muted);
    font-size: 0.85rem;
    flex-shrink: 0;
    margin-left: 1rem;
}

.notification-content p {
    color: var(--text-secondary);
    margin: 0 0 1rem 0;
    line-height: 1.5;
}

.notification-actions {
    display: flex;
    gap: 0.5rem;
}

.notification-dismiss {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: var(--transition);
    opacity: 0;
}

.notification-item:hover .notification-dismiss {
    opacity: 1;
}

.notification-dismiss:hover {
    background: var(--bg-secondary);
    color: var(--error-color);
}

@media (max-width: 768px) {
    .notification-item {
        padding: 1rem;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .notification-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
        align-self: flex-start;
    }
    
    .notification-header {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .notification-time {
        margin-left: 0;
    }
    
    .notification-actions {
        flex-direction: column;
    }
}
</style>

<script>
function dismissNotification(notificationId) {
    fetch('api/delete-notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.style.opacity = '0';
                notificationElement.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notificationElement.remove();
                    
                    // Check if date group is empty
                    const dateGroup = notificationElement.closest('.notifications-date-group');
                    if (dateGroup && dateGroup.querySelectorAll('.notification-item').length === 0) {
                        dateGroup.remove();
                    }
                    
                    // Check if all notifications are gone
                    if (document.querySelectorAll('.notification-item').length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        } else {
            alert('Error dismissing notification: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while dismissing the notification.');
    });
}

function clearAllNotifications() {
    if (confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
        fetch('api/clear-notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error clearing notifications: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while clearing notifications.');
        });
    }
}
</script>

<?php 
include 'includes/footer.php';

function formatNotificationDate($date) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    if ($date === $today) {
        return 'Today';
    } elseif ($date === $yesterday) {
        return 'Yesterday';
    } else {
        return date('F j, Y', strtotime($date));
    }
}

function getNotificationIcon($type) {
    $icons = [
        'join_request' => '<i class="fas fa-user-plus"></i>',
        'join_approved' => '<i class="fas fa-check-circle"></i>',
        'new_message' => '<i class="fas fa-comment"></i>',
        'member_left' => '<i class="fas fa-user-minus"></i>',
        'group_deleted' => '<i class="fas fa-trash"></i>'
    ];
    
    $icon_html = $icons[$type] ?? '<i class="fas fa-bell"></i>';
    return '<div class="notification-icon ' . str_replace('_', '-', $type) . '">' . $icon_html . '</div>';
}
?>