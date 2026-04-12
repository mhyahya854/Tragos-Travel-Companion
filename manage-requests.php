<?php
$page_title = 'Manage Join Requests';
include 'includes/header.php';

requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my-groups.php');
    exit();
}

$group_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Check if user is the owner or admin of this group
$auth_query = "SELECT g.name, gm.role 
               FROM groups_table g 
               JOIN group_members gm ON g.id = gm.group_id 
               WHERE g.id = :group_id AND gm.user_id = :user_id AND gm.role IN ('owner', 'admin')";
$auth_stmt = $db->prepare($auth_query);
$auth_stmt->bindParam(':group_id', $group_id);
$auth_stmt->bindParam(':user_id', $current_user['id']);
$auth_stmt->execute();

if ($auth_stmt->rowCount() == 0) {
    header('Location: group-details.php?id=' . $group_id);
    exit();
}

$group_info = $auth_stmt->fetch(PDO::FETCH_ASSOC);

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    
    if (in_array($action, ['approve', 'reject', 'block'])) {
        try {
            $db->beginTransaction();
            
            // Get request details
            $request_query = "SELECT jr.*, u.display_name, u.username 
                             FROM join_requests jr 
                             JOIN users u ON jr.user_id = u.id 
                             WHERE jr.id = :request_id AND jr.group_id = :group_id AND jr.status = 'pending'";
            $request_stmt = $db->prepare($request_query);
            $request_stmt->bindParam(':request_id', $request_id);
            $request_stmt->bindParam(':group_id', $group_id);
            $request_stmt->execute();
            
            if ($request_stmt->rowCount() > 0) {
                $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($action === 'approve') {
                    // Add user to group
                    $add_member_query = "INSERT INTO group_members (group_id, user_id, role) VALUES (:group_id, :user_id, 'member')";
                    $add_member_stmt = $db->prepare($add_member_query);
                    $add_member_stmt->bindParam(':group_id', $group_id);
                    $add_member_stmt->bindParam(':user_id', $request_data['user_id']);
                    $add_member_stmt->execute();
                    
                    // Update member count
                    $update_count = "UPDATE groups_table SET current_members = current_members + 1 WHERE id = :group_id";
                    $update_stmt = $db->prepare($update_count);
                    $update_stmt->bindParam(':group_id', $group_id);
                    $update_stmt->execute();
                    
                    // Create notification for user
                    $notif_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                                   VALUES (:user_id, 'join_approved', 'Join Request Approved', :message, :group_id)";
                    $notif_stmt = $db->prepare($notif_query);
                    $notif_stmt->bindParam(':user_id', $request_data['user_id']);
                    $message = 'Your request to join "' . $group_info['name'] . '" has been approved!';
                    $notif_stmt->bindParam(':message', $message);
                    $notif_stmt->bindParam(':group_id', $group_id);
                    $notif_stmt->execute();
                    
                    $status = 'approved';
                } else {
                    $status = $action === 'reject' ? 'rejected' : 'blocked';
                    
                    // Create notification for user
                    $notif_query = "INSERT INTO notifications (user_id, type, title, message, related_id) 
                                   VALUES (:user_id, 'join_rejected', 'Join Request " . ucfirst($status) . "', :message, :group_id)";
                    $notif_stmt = $db->prepare($notif_query);
                    $notif_stmt->bindParam(':user_id', $request_data['user_id']);
                    $message = 'Your request to join "' . $group_info['name'] . '" has been ' . $status . '.';
                    $notif_stmt->bindParam(':message', $message);
                    $notif_stmt->bindParam(':group_id', $group_id);
                    $notif_stmt->execute();
                }
                
                // Update request status
                $update_request = "UPDATE join_requests SET status = :status, responded_at = CURRENT_TIMESTAMP WHERE id = :request_id";
                $update_stmt = $db->prepare($update_request);
                $update_stmt->bindParam(':status', $status);
                $update_stmt->bindParam(':request_id', $request_id);
                $update_stmt->execute();
                
                $db->commit();
                $success = 'Request ' . $status . ' successfully!';
            }
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to process request. Please try again.';
        }
    }
}

// Get pending requests
$pending_query = "SELECT jr.*, u.display_name, u.username, u.profile_picture, u.bio, u.location, u.created_at as user_joined 
                  FROM join_requests jr 
                  JOIN users u ON jr.user_id = u.id 
                  WHERE jr.group_id = :group_id AND jr.status = 'pending' 
                  ORDER BY jr.requested_at ASC";
$pending_stmt = $db->prepare($pending_query);
$pending_stmt->bindParam(':group_id', $group_id);
$pending_stmt->execute();
$pending_requests = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get processed requests (recent)
$processed_query = "SELECT jr.*, u.display_name, u.username, u.profile_picture 
                   FROM join_requests jr 
                   JOIN users u ON jr.user_id = u.id 
                   WHERE jr.group_id = :group_id AND jr.status != 'pending' 
                   ORDER BY jr.responded_at DESC 
                   LIMIT 20";
$processed_stmt = $db->prepare($processed_query);
$processed_stmt->bindParam(':group_id', $group_id);
$processed_stmt->execute();
$processed_requests = $processed_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main-content">
    <section class="manage-requests-section">
        <div class="container">
            <div class="page-header">
                <div class="header-content">
                    <a href="group-details.php?id=<?php echo $group_id; ?>" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="header-info">
                        <h1>Manage Join Requests</h1>
                        <p><?php echo htmlspecialchars($group_info['name']); ?></p>
                    </div>
                </div>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="requests-tabs">
                <button class="tab-button active" onclick="showTab('pending')">
                    Pending Requests (<?php echo count($pending_requests); ?>)
                </button>
                <button class="tab-button" onclick="showTab('processed')">
                    Recent Activity (<?php echo count($processed_requests); ?>)
                </button>
            </div>
            
            <div class="tab-content active" id="pending">
                <?php if (empty($pending_requests)): ?>
                    <div class="no-requests">
                        <i class="fas fa-user-check"></i>
                        <h3>No pending requests</h3>
                        <p>All join requests have been processed. New requests will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="requests-grid">
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="request-card">
                                <div class="request-header">
                                    <div class="user-info">
                                        <img src="assets/images/<?php echo $request['profile_picture']; ?>" alt="Profile" class="user-avatar">
                                        <div class="user-details">
                                            <h3><?php echo htmlspecialchars($request['display_name'] ?: $request['username']); ?></h3>
                                            <p class="username">@<?php echo htmlspecialchars($request['username']); ?></p>
                                            <?php if ($request['location']): ?>
                                                <p class="location">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php echo htmlspecialchars($request['location']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="request-time">
                                        <span><?php echo date('M j, Y g:i A', strtotime($request['requested_at'])); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($request['message']): ?>
                                    <div class="request-message">
                                        <h4>Message:</h4>
                                        <p><?php echo nl2br(htmlspecialchars($request['message'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($request['bio']): ?>
                                    <div class="user-bio">
                                        <h4>About:</h4>
                                        <p><?php echo nl2br(htmlspecialchars($request['bio'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="user-stats">
                                    <div class="stat">
                                        <span class="stat-label">Member since:</span>
                                        <span class="stat-value"><?php echo date('M Y', strtotime($request['user_joined'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="request-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success btn-small" onclick="return confirm('Approve this join request?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-outline btn-small" onclick="return confirm('Reject this join request?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <input type="hidden" name="action" value="block">
                                        <button type="submit" class="btn btn-outline btn-small text-danger" onclick="return confirm('Block this user from joining? This action can be undone later.')">
                                            <i class="fas fa-ban"></i> Block
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="processed">
                <?php if (empty($processed_requests)): ?>
                    <div class="no-requests">
                        <i class="fas fa-history"></i>
                        <h3>No recent activity</h3>
                        <p>Processed join requests will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="processed-list">
                        <?php foreach ($processed_requests as $request): ?>
                            <div class="processed-item">
                                <img src="assets/images/<?php echo $request['profile_picture']; ?>" alt="Profile" class="user-avatar-small">
                                <div class="processed-info">
                                    <div class="processed-details">
                                        <span class="user-name"><?php echo htmlspecialchars($request['display_name'] ?: $request['username']); ?></span>
                                        <span class="action-text">
                                            <?php if ($request['status'] === 'approved'): ?>
                                                <span class="status-approved">was approved</span>
                                            <?php elseif ($request['status'] === 'rejected'): ?>
                                                <span class="status-rejected">was rejected</span>
                                            <?php else: ?>
                                                <span class="status-blocked">was blocked</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="processed-time">
                                        <?php echo date('M j, Y g:i A', strtotime($request['responded_at'])); ?>
                                    </div>
                                </div>
                                <div class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<style>
.manage-requests-section {
    padding: 3rem 0;
    background: var(--bg-secondary);
    min-height: calc(100vh - 160px);
}

.page-header {
    margin-bottom: 3rem;
}

.header-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.back-link {
    color: var(--primary-color);
    font-size: 1.2rem;
    padding: 0.75rem;
    border-radius: 50%;
    transition: var(--transition);
}

.back-link:hover {
    background: var(--bg-primary);
}

.header-info h1 {
    color: var(--primary-color);
    margin: 0 0 0.5rem 0;
}

.header-info p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 1.1rem;
}

.requests-tabs {
    display: flex;
    margin-bottom: 2rem;
    border-bottom: 1px solid var(--border-color);
}

.tab-button {
    padding: 1rem 2rem;
    background: none;
    border: none;
    color: var(--text-secondary);
    font-family: var(--font-family);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    border-bottom: 3px solid transparent;
}

.tab-button:hover,
.tab-button.active {
    color: var(--primary-color);
    border-bottom-color: var(--primary-color);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.no-requests {
    text-align: center;
    background: var(--bg-primary);
    padding: 4rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
}

.no-requests i {
    font-size: 3rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-requests h3 {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.no-requests p {
    color: var(--text-muted);
}

.requests-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 2rem;
}

.request-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.request-card:hover {
    box-shadow: var(--shadow-md);
}

.request-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.user-info {
    display: flex;
    gap: 1rem;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
}

.user-details h3 {
    color: var(--primary-color);
    margin: 0 0 0.25rem 0;
}

.username {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin: 0 0 0.5rem 0;
}

.location {
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.request-time {
    text-align: right;
    color: var(--text-muted);
    font-size: 0.85rem;
}

.request-message,
.user-bio {
    margin-bottom: 1.5rem;
}

.request-message h4,
.user-bio h4 {
    color: var(--primary-color);
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
}

.request-message p,
.user-bio p {
    color: var(--text-secondary);
    margin: 0;
    line-height: 1.5;
    font-size: 0.9rem;
}

.user-stats {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.stat {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
}

.stat-label {
    color: var(--text-muted);
}

.stat-value {
    color: var(--text-secondary);
    font-weight: 500;
}

.request-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.processed-list {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.processed-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--border-light);
    transition: var(--transition);
}

.processed-item:last-child {
    border-bottom: none;
}

.processed-item:hover {
    background: var(--bg-secondary);
}

.user-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.processed-info {
    flex: 1;
}

.processed-details {
    margin-bottom: 0.25rem;
}

.user-name {
    color: var(--primary-color);
    font-weight: 500;
    margin-right: 0.5rem;
}

.status-approved {
    color: var(--success-color);
}

.status-rejected {
    color: var(--error-color);
}

.status-blocked {
    color: var(--warning-color);
}

.processed-time {
    color: var(--text-muted);
    font-size: 0.85rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: var(--border-radius);
    font-size: 0.8rem;
    font-weight: 500;
    color: white;
}

.status-badge.status-approved {
    background: var(--success-color);
}

.status-badge.status-rejected {
    background: var(--error-color);
}

.status-badge.status-blocked {
    background: var(--warning-color);
}

@media (max-width: 768px) {
    .requests-grid {
        grid-template-columns: 1fr;
    }
    
    .request-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .request-time {
        text-align: left;
    }
    
    .request-actions {
        flex-direction: column;
    }
    
    .processed-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .processed-info {
        width: 100%;
    }
}
</style>

<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>