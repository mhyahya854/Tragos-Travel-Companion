<?php
$page_title = 'My Groups';
include 'includes/header.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get user's groups with additional details
$groups_query = "SELECT g.*, gm.role, gm.joined_at,
                 (SELECT COUNT(*) FROM join_requests jr WHERE jr.group_id = g.id AND jr.status = 'pending') as pending_requests,
                 (SELECT COUNT(*) FROM chat_messages cm WHERE cm.group_id = g.id AND cm.created_at > gm.joined_at) as total_messages,
                 (SELECT cm.created_at FROM chat_messages cm WHERE cm.group_id = g.id ORDER BY cm.created_at DESC LIMIT 1) as last_message_time
                 FROM groups_table g 
                 JOIN group_members gm ON g.id = gm.group_id 
                 WHERE gm.user_id = :user_id AND g.is_active = TRUE 
                 ORDER BY gm.joined_at DESC";
$groups_stmt = $db->prepare($groups_query);
$groups_stmt->bindParam(':user_id', $current_user['id']);
$groups_stmt->execute();
$user_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate groups by role
$owned_groups = array_filter($user_groups, function($group) {
    return $group['role'] === 'owner';
});

$joined_groups = array_filter($user_groups, function($group) {
    return $group['role'] !== 'owner';
});
?>

<main class="main-content">
    <section class="my-groups-section">
        <div class="container">
            <div class="my-groups-header">
                <h1>My Travel Groups</h1>
                <p>Manage your travel groups and stay connected with your travel companions</p>
                <a href="create-group.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Group
                </a>
            </div>
            
            <?php if (empty($user_groups)): ?>
                <div class="no-groups-container">
                    <div class="no-groups">
                        <i class="fas fa-users"></i>
                        <h3>No groups yet</h3>
                        <p>You haven't joined any travel groups yet. Start your journey by creating a group or joining existing ones!</p>
                        <div class="no-groups-actions">
                            <a href="create-group.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Your First Group
                            </a>
                            <a href="groups.php" class="btn btn-outline">
                                <i class="fas fa-search"></i> Browse Groups
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                
                <?php if (!empty($owned_groups)): ?>
                <div class="groups-section">
                    <div class="section-header">
                        <h2>Groups I Own (<?php echo count($owned_groups); ?>)</h2>
                        <p>Groups you created and manage</p>
                    </div>
                    
                    <div class="groups-grid">
                        <?php foreach ($owned_groups as $group): ?>
                            <div class="group-card owner-card">
                                <div class="group-image">
                                    <img src="assets/images/<?php echo $group['group_image'] ?: 'other.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($group['name']); ?>">
                                    <div class="group-badges">
                                        <span class="role-badge role-owner">Owner</span>
                                        <?php if ($group['pending_requests'] > 0): ?>
                                            <span class="notification-badge">
                                                <?php echo $group['pending_requests']; ?> pending
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="group-content">
                                    <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                                    <div class="group-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($group['destination']); ?>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-users"></i>
                                            <?php echo $group['current_members']; ?>/<?php echo $group['max_members']; ?> members
                                        </div>
                                    </div>
                                    
                                    <div class="group-stats">
                                        <div class="stat">
                                            <span class="stat-number"><?php echo $group['total_messages']; ?></span>
                                            <span class="stat-label">Messages</span>
                                        </div>
                                        <div class="stat">
                                            <span class="stat-number"><?php echo $group['pending_requests']; ?></span>
                                            <span class="stat-label">Pending</span>
                                        </div>
                                        <?php if ($group['last_message_time']): ?>
                                            <div class="stat">
                                                <span class="stat-number"><?php echo date('M j', strtotime($group['last_message_time'])); ?></span>
                                                <span class="stat-label">Last Activity</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="group-actions">
                                        <a href="group-chat.php?id=<?php echo $group['id']; ?>" class="btn btn-primary btn-small">
                                            <i class="fas fa-comments"></i> Chat
                                        </a>
                                        <a href="manage-requests.php?id=<?php echo $group['id']; ?>" class="btn btn-secondary btn-small">
                                            <i class="fas fa-user-check"></i> Manage
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($joined_groups)): ?>
                <div class="groups-section">
                    <div class="section-header">
                        <h2>Groups I Joined (<?php echo count($joined_groups); ?>)</h2>
                        <p>Groups you're a member of</p>
                    </div>
                    
                    <div class="groups-grid">
                        <?php foreach ($joined_groups as $group): ?>
                            <div class="group-card member-card">
                                <div class="group-image">
                                    <img src="assets/images/<?php echo $group['group_image'] ?: 'other.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($group['name']); ?>">
                                    <div class="group-badges">
                                        <span class="role-badge role-<?php echo $group['role']; ?>">
                                            <?php echo ucfirst($group['role']); ?>
                                        </span>
                                        <span class="category-badge category-<?php echo $group['category']; ?>">
                                            <?php echo ucfirst($group['category']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="group-content">
                                    <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                                    <div class="group-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($group['destination']); ?>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-users"></i>
                                            <?php echo $group['current_members']; ?> members
                                        </div>
                                    </div>
                                    
                                    <div class="group-info">
                                        <div class="info-item">
                                            <span class="info-label">Joined:</span>
                                            <span class="info-value"><?php echo date('M j, Y', strtotime($group['joined_at'])); ?></span>
                                        </div>
                                        <?php if ($group['last_message_time']): ?>
                                            <div class="info-item">
                                                <span class="info-label">Last Activity:</span>
                                                <span class="info-value"><?php echo date('M j, g:i A', strtotime($group['last_message_time'])); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="group-actions">
                                        <a href="group-chat.php?id=<?php echo $group['id']; ?>" class="btn btn-primary btn-small">
                                            <i class="fas fa-comments"></i> Chat
                                        </a>
                                        <a href="group-details.php?id=<?php echo $group['id']; ?>" class="btn btn-outline btn-small">
                                            <i class="fas fa-eye"></i> Details
                                        </a>
                                        <button onclick="leaveGroup(<?php echo $group['id']; ?>)" class="btn btn-outline btn-small text-danger">
                                            <i class="fas fa-sign-out-alt"></i> Leave
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
.my-groups-section {
    padding: 4rem 0;
    background: var(--bg-secondary);
}

.my-groups-header {
    text-align: center;
    margin-bottom: 4rem;
}

.my-groups-header h1 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.my-groups-header p {
    color: var(--text-secondary);
    font-size: 1.1rem;
    margin-bottom: 2rem;
}

.no-groups-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

.no-groups {
    text-align: center;
    background: var(--bg-primary);
    padding: 4rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    max-width: 500px;
}

.no-groups i {
    font-size: 2rem;
    margin-bottom: auto;
    opacity: 0.5;
}

.no-groups h3 {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.no-groups p {
    color: var(--text-muted);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.no-groups-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}
.no-groups-actions .btn {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    border-radius: var(--border-radius);
    width: 100%;
    max-width: 100%;
}

.groups-section {
    margin-bottom: 4rem;
}

.section-header {
    margin-bottom: 2rem;
}

.section-header h2 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.section-header p {
    color: var(--text-secondary);
}

.groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
}

.group-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.group-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.owner-card {
    border-left: 4px solid var(--secondary-color);
}

.member-card {
    border-left: 4px solid var(--success-color);
}

.group-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.group-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.group-badges {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: var(--border-radius);
    font-size: 0.8rem;
    font-weight: 500;
    color: white;
}

.role-owner {
    background: var(--secondary-color);
    color: var(--primary-color);
}

.role-admin {
    background: var(--info-color);
}

.role-member {
    background: var(--success-color);
}

.notification-badge {
    background: var(--error-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: var(--border-radius);
    font-size: 0.75rem;
    font-weight: 500;
}

.group-content {
    padding: 1.5rem;
}

.group-content h3 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    font-size: 1.25rem;
}

.group-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.group-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.stat {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--primary-color);
}

.stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.group-info {
    margin-bottom: 1.5rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.info-label {
    color: var(--text-muted);
}

.info-value {
    color: var(--text-secondary);
    font-weight: 500;
}

.group-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.dropdown {
    position: relative;
}

.dropdown-toggle {
    background: none;
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    min-width: 180px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: var(--transition);
    z-index: 100;
}

.dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-secondary);
    text-decoration: none;
    border-bottom: 1px solid var(--border-light);
    transition: var(--transition);
}

.dropdown-menu a:hover {
    background: var(--bg-secondary);
    color: var(--primary-color);
}

.dropdown-menu a.text-danger {
    color: var(--error-color);
}

.dropdown-menu a.text-danger:hover {
    background: rgba(220, 20, 60, 0.1);
    color: var(--error-color);
}

.dropdown-divider {
    height: 1px;
    background: var(--border-color);
    margin: 0.5rem 0;
}

@media (max-width: 768px) {
    .groups-grid {
        grid-template-columns: 1fr;
    }
    
    .group-actions {
        flex-direction: column;
    }
    
    .group-stats {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: left;
    }
    
    .no-groups-actions {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<script>
function leaveGroup(groupId) {
    if (confirm('Are you sure you want to leave this group? You will lose access to the group chat and content.')) {
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
                location.reload();
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

function deleteGroup(groupId) {
    if (confirm('Are you sure you want to delete this group? This action cannot be undone and all group data will be permanently lost.')) {
        if (confirm('This will permanently delete the group and all its messages. Are you absolutely sure?')) {
            fetch('api/delete-group.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ group_id: groupId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error deleting group: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the group.');
            });
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>