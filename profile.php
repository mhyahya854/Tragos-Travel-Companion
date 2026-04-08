<?php
$page_title = 'Profile';
include 'includes/header.php';

// Check if viewing another user's profile
$viewing_user_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$is_own_profile = !$viewing_user_id || ($current_user && $viewing_user_id == $current_user['id']);

if ($is_own_profile) {
    requireLogin();
    $profile_user = $current_user;
} else {
    // Get the user being viewed
    $database = new Database();
    $db = $database->getConnection();
    
    $user_query = "SELECT * FROM users WHERE id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->bindParam(':user_id', $viewing_user_id);
    $user_stmt->execute();
    
    if ($user_stmt->rowCount() == 0) {
        header('Location: index.php');
        exit();
    }
    
    $profile_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check privacy settings
    if ($profile_user['profile_visibility'] === 'private' && !$current_user) {
        header('Location: login.php');
        exit();
    }
}

$errors = [];
$success = '';

// Handle form submissions (only for own profile)
if ($is_own_profile && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $display_name = sanitizeInput($_POST['display_name']);
    $bio = sanitizeInput($_POST['bio']);
    $phone = sanitizeInput($_POST['phone']);
    $location = sanitizeInput($_POST['location']);

    // Validation
    if (empty($display_name)) {
        $errors['display_name'] = 'Display name is required';
    }

    if (!empty($phone) && !preg_match('/^[\+]?[0-9\s\-$$$$]+$/', $phone)) {
        $errors['phone'] = 'Invalid phone number format';
    }

    // Handle profile picture upload
    $profile_picture = $current_user['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $current_user['id'] . '_' . time() . '.' . $file_extension;
            $upload_path = 'assets/images/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Delete old profile picture if it's not the default
                if ($current_user['profile_picture'] !== 'default-avatar.png') {
                    $old_file = 'assets/images/' . $current_user['profile_picture'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                $profile_picture = $new_filename;
            } else {
                $errors['profile_picture'] = 'Failed to upload profile picture';
            }
        } else {
            $errors['profile_picture'] = 'Invalid image type. Please use JPG, PNG, or GIF';
        }
    }

    // Update profile if no errors
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        
        $update_query = "UPDATE users SET display_name = :display_name, bio = :bio, phone = :phone, 
                        location = :location, profile_picture = :profile_picture, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = :user_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':display_name', $display_name);
        $update_stmt->bindParam(':bio', $bio);
        $update_stmt->bindParam(':phone', $phone);
        $update_stmt->bindParam(':location', $location);
        $update_stmt->bindParam(':profile_picture', $profile_picture);
        $update_stmt->bindParam(':user_id', $current_user['id']);
        
        if ($update_stmt->execute()) {
            $success = '<div style="background-color:rgb(175, 231, 175); padding: 10px; border-radius: 5px;">' . 'Profile updated successfully!' . '</div>';
            // Refresh current user data
            $current_user = getCurrentUser();
            $profile_user = $current_user;
        } else {
            $errors['general'] = 'Failed to update profile. Please try again.';
        }
    }
}

// Get user's groups and statistics
$database = new Database();
$db = $database->getConnection();

// Get user's groups (only public groups if viewing another user's profile)
if ($is_own_profile) {
    $groups_query = "SELECT g.*, gm.role, gm.joined_at 
                FROM groups_table g 
                JOIN group_members gm ON g.id = gm.group_id 
                WHERE gm.user_id = :user_id AND g.is_active = TRUE 
                ORDER BY gm.joined_at DESC";
} else {
    $groups_query = "SELECT g.*, gm.role, gm.joined_at 
                FROM groups_table g 
                JOIN group_members gm ON g.id = gm.group_id 
                WHERE gm.user_id = :user_id AND g.is_active = TRUE AND g.privacy = 'public'
                ORDER BY gm.joined_at DESC";
}

$groups_stmt = $db->prepare($groups_query);
$groups_stmt->bindParam(':user_id', $profile_user['id']);
$groups_stmt->execute();
$user_groups = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM group_members WHERE user_id = :user_id) as groups_joined,
    (SELECT COUNT(*) FROM groups_table WHERE owner_id = :user_id AND is_active = TRUE) as groups_created,
    (SELECT COUNT(*) FROM chat_messages WHERE user_id = :user_id AND is_deleted = FALSE) as messages_sent";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':user_id', $profile_user['id']);
$stats_stmt->execute();
$user_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Check if current user can message this user (if they share a group)
$can_message = false;
if ($current_user && !$is_own_profile) {
    $shared_groups_query = "SELECT COUNT(*) as shared_count 
                           FROM group_members gm1 
                           JOIN group_members gm2 ON gm1.group_id = gm2.group_id 
                           WHERE gm1.user_id = :current_user_id AND gm2.user_id = :profile_user_id";
    $shared_stmt = $db->prepare($shared_groups_query);
    $shared_stmt->bindParam(':current_user_id', $current_user['id']);
    $shared_stmt->bindParam(':profile_user_id', $profile_user['id']);
    $shared_stmt->execute();
    $shared_result = $shared_stmt->fetch(PDO::FETCH_ASSOC);
    $can_message = $shared_result['shared_count'] > 0;
}
?>

<main class="main-content">
    <section class="profile-section">
        <div class="container">
            <div class="profile-layout">
                <div class="profile-sidebar">
                    <div class="profile-card">
                        <div class="profile-avatar-container">
                            <img src="assets/images/<?php echo $profile_user['profile_picture']; ?>" alt="Profile Picture" class="profile-avatar">
                            <?php if ($is_own_profile): ?>
                                <div class="avatar-overlay">
                                    <i class="fas fa-camera"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h2><?php echo htmlspecialchars($profile_user['display_name'] ?: $profile_user['username']); ?></h2>
                        <p class="profile-username">@<?php echo htmlspecialchars($profile_user['username']); ?></p>
                        <?php if ($profile_user['location']): ?>
                            <p class="profile-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($profile_user['location']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($profile_user['bio']): ?>
                            <p class="profile-bio"><?php echo nl2br(htmlspecialchars($profile_user['bio'])); ?></p>
                        <?php endif; ?>
                        
                        <div class="profile-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $user_stats['groups_joined']; ?></span>
                                <span class="stat-label">Groups Joined</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $user_stats['groups_created']; ?></span>
                                <span class="stat-label">Groups Created</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?php echo $user_stats['messages_sent']; ?></span>
                                <span class="stat-label">Messages Sent</span>
                            </div>
                        </div>
                        
                        <div class="profile-actions">
                            <?php if ($is_own_profile): ?>
                                <a href="settings.php" class="btn btn-outline w-full">
                                    <i class="fas fa-cog"></i> Account Settings
                                </a>
                            <?php else: ?>
                                <?php if ($can_message): ?>
                                    <button class="btn btn-primary w-full" onclick="startConversation(<?php echo $profile_user['id']; ?>)">
                                        <i class="fas fa-comment"></i> Send Message
                                    </button>
                                <?php endif; ?>
                                <p class="member-since">Member since <?php echo date('F Y', strtotime($profile_user['created_at'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="profile-main">
                    <?php if ($is_own_profile): ?>
                        <div class="profile-tabs">
                            <button class="tab-button active" onclick="showTab('edit-profile')">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                            <button class="tab-button" onclick="showTab('my-groups')">
                                <i class="fas fa-users"></i> My Groups
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="profile-tabs">
                            <button class="tab-button active" onclick="showTab('my-groups')">
                                <i class="fas fa-users"></i> Groups (<?php echo count($user_groups); ?>)
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($is_own_profile): ?>
                        <div class="tab-content active" id="edit-profile">
                            <div class="content-card">
                                <h3>Edit Profile Information</h3>
                                
                                <?php if (!empty($errors['general'])): ?>
                                    <div class="alert alert-error">
                                        <?php echo $errors['general']; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($success): ?>
                                    <div class="alert alert-success">
                                        <?php echo $success; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" enctype="multipart/form-data" class="profile-form">
                                    <div class="form-group">
                                        <label for="profile_picture">Profile Picture</label>
                                        <div class="file-upload-container">
                                            <input type="file" id="profile_picture" name="profile_picture" 
                                                   class="file-input <?php echo isset($errors['profile_picture']) ? 'error' : ''; ?>" 
                                                   accept="image/jpeg,image/png,image/gif">
                                            <label for="profile_picture" class="file-upload-label">
                                                <i class="fas fa-upload"></i>
                                                Choose New Picture
                                            </label>
                                        </div>
                                        <?php if (isset($errors['profile_picture'])): ?>
                                            <div class="error-message"><?php echo $errors['profile_picture']; ?></div>
                                        <?php endif; ?>
                                        <div class="form-help">JPG, PNG, or GIF. Max 5MB.</div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="display_name">Display Name *</label>
                                            <input type="text" id="display_name" name="display_name" 
                                                   class="form-control <?php echo isset($errors['display_name']) ? 'error' : ''; ?>" 
                                                   value="<?php echo htmlspecialchars($profile_user['display_name']); ?>" required>
                                            <?php if (isset($errors['display_name'])): ?>
                                                <div class="error-message"><?php echo $errors['display_name']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="location">Location</label>
                                            <input type="text" id="location" name="location" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($profile_user['location']); ?>" 
                                                   placeholder="e.g., New York, USA">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" 
                                               class="form-control <?php echo isset($errors['phone']) ? 'error' : ''; ?>" 
                                               value="<?php echo htmlspecialchars($profile_user['phone']); ?>" 
                                               placeholder="+1 (555) 123-4567">
                                        <?php if (isset($errors['phone'])): ?>
                                            <div class="error-message"><?php echo $errors['phone']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="bio">Bio</label>
                                        <textarea id="bio" name="bio" class="form-control" rows="4" 
                                                  placeholder="Tell other travelers about yourself, your travel experiences, and what you're looking for..."><?php echo htmlspecialchars($profile_user['bio']); ?></textarea>
                                        <div class="form-help">Share your travel interests, experiences, and what makes you a great travel companion.</div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="tab-content <?php echo !$is_own_profile ? 'active' : ''; ?>" id="my-groups">
                        <div class="content-card">
                            <div class="groups-header">
                                <h3><?php echo $is_own_profile ? 'My Travel Groups' : htmlspecialchars($profile_user['display_name'] ?: $profile_user['username']) . "'s Groups"; ?></h3>
                                <?php if ($is_own_profile): ?>
                                    <a href="create-group.php" class="btn btn-primary btn-small">
                                        <i class="fas fa-plus"></i> Create New Group
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (empty($user_groups)): ?>
                                <div class="no-groups">
                                    <i class="fas fa-users"></i>
                                    <h4><?php echo $is_own_profile ? 'No groups yet' : 'No public groups'; ?></h4>
                                    <p><?php echo $is_own_profile ? "You haven't joined any travel groups yet. Start by browsing available groups or creating your own!" : "This user hasn't joined any public groups yet."; ?></p>
                                    <?php if ($is_own_profile): ?>
                                        <div class="no-groups-actions">
                                            <a href="groups.php" class="btn btn-primary">Browse Groups</a>
                                            <a href="create-group.php" class="btn btn-outline">Create Group</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="groups-grid">
                                    <?php foreach ($user_groups as $group): ?>
                                        <div class="group-card">
                                            <div class="group-image">
                                                <img src="assets/images/<?php echo $group['group_image'] ?: 'other.png'; ?>" 
                                                     alt="<?php echo htmlspecialchars($group['name']); ?>">
                                                <div class="group-role-badge role-<?php echo $group['role']; ?>">
                                                    <?php echo ucfirst($group['role']); ?>
                                                </div>
                                            </div>
                                            <div class="group-content">
                                                <h4><?php echo htmlspecialchars($group['name']); ?></h4>
                                                <p class="group-destination">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php echo htmlspecialchars($group['destination']); ?>
                                                </p>
                                                <p class="group-description">
                                                    <?php echo htmlspecialchars(substr($group['description'], 0, 100)) . '...'; ?>
                                                </p>
                                                <div class="group-meta">
                                                    <span class="category-badge category-<?php echo $group['category']; ?>">
                                                        <?php echo ucfirst($group['category']); ?>
                                                    </span>
                                                    <span class="member-count">
                                                        <i class="fas fa-users"></i>
                                                        <?php echo $group['current_members']; ?> members
                                                    </span>
                                                </div>
                                                <div class="group-actions">
                                                    <a href="group-details.php?id=<?php echo $group['id']; ?>" class="btn btn-outline btn-small">
                                                        View Details
                                                    </a>
                                                    <?php if ($is_own_profile): ?>
                                                        <a href="group-chat.php?id=<?php echo $group['id']; ?>" class="btn btn-primary btn-small">
                                                            <i class="fas fa-comments"></i> Chat
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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
.profile-section {
    padding: 3rem 0;
    background: var(--bg-secondary);
}

.profile-layout {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 3rem;
}

.profile-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
    text-align: center;
    /* position: sticky; */
    top: 100px;
}

.profile-avatar-container {
    position: relative;
    display: inline-block;
    margin-bottom: 1.5rem;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--border-color);
}

.avatar-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    background: var(--primary-color);
    color: var(--text-light);
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
}

.avatar-overlay:hover {
    background: var(--primary-light);
}

.profile-card h2 {
    margin: 0 0 0.5rem 0;
    color: var(--primary-color);
}

.profile-username {
    color: var(--text-muted);
    margin: 0 0 1rem 0;
    font-size: 0.9rem;
}

.profile-location {
    color: var(--text-secondary);
    margin: 0 0 1rem 0;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.profile-bio {
    color: var(--text-secondary);
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 2rem;
}

.profile-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem 0;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.member-since {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-top: 1rem;
    text-align: center;
}

.profile-main {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.profile-tabs {
    display: flex;
    border-bottom: 1px solid var(--border-color);
}

.tab-button {
    flex: 1;
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
    background: var(--bg-secondary);
    border-bottom-color: var(--primary-color);
}

.tab-content {
    display: none;
    padding: 2rem;
}

.tab-content.active {
    display: block;
}

.content-card h3 {
    color: var(--primary-color);
    margin-bottom: 2rem;
}

.profile-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.file-upload-container {
    position: relative;
}

.file-input {
    position: absolute;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-upload-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border: 2px dashed var(--border-color);
    border-radius: var(--border-radius);
    background: var(--bg-secondary);
    color: var(--text-secondary);
    cursor: pointer;
    transition: var(--transition);
}

.file-upload-label:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.groups-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.groups-header h3 {
    margin: 0;
}

.no-groups {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-muted);
}

.no-groups i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-groups h4 {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.no-groups-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.group-role-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
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

.group-content h4 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.group-destination {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.group-description {
    color: var(--text-secondary);
    font-size: 0.9rem;
    line-height: 1.4;
    margin-bottom: 1rem;
}

.group-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.member-count {
    font-size: 0.85rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.group-actions {
    display: flex;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .profile-layout {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .profile-card {
        position: static;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .profile-stats {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }

    .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-align: left;
    }

    .groups-grid {
        grid-template-columns: 1fr;
    }

    .no-groups-actions {
        flex-direction: column;
        align-items: center;
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

// Handle profile picture preview
const profilePictureInput = document.getElementById('profile_picture');
if (profilePictureInput) {
    profilePictureInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.querySelector('.profile-avatar').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
}

function startConversation(userId) {
    // This would typically open a messaging interface
    // For now, we'll show an alert
    alert('Messaging feature coming soon! You can find this user in your shared groups to communicate.');
}
</script>

<?php include 'includes/footer.php'; ?>