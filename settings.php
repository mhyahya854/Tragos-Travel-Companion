<?php
$page_title = 'Account Settings';
include 'includes/header.php';

requireLogin();

$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        if (empty($current_password)) {
            $errors['current_password'] = 'Current password is required';
        } elseif (!password_verify($current_password, $current_user['password'])) {
            $errors['current_password'] = 'Current password is incorrect';
        }
        
        if (empty($new_password)) {
            $errors['new_password'] = 'New password is required';
        } elseif (strlen($new_password) < 6) {
            $errors['new_password'] = 'Password must be at least 6 characters';
        }
        
        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = :password WHERE id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':password', $hashed_password);
            $update_stmt->bindParam(':user_id', $current_user['id']);
            
            if ($update_stmt->execute()) {
                $success = 'Password updated successfully!';
            } else {
                $errors['general'] = 'Failed to update password. Please try again.';
            }
        }
    }
    
    if (isset($_POST['update_email'])) {
        $new_email = sanitizeInput($_POST['new_email']);
        $password = $_POST['email_password'];
        
        // Validation
        if (empty($new_email)) {
            $errors['new_email'] = 'Email is required';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors['new_email'] = 'Invalid email format';
        }
        
        if (empty($password)) {
            $errors['email_password'] = 'Password is required to change email';
        } elseif (!password_verify($password, $current_user['password'])) {
            $errors['email_password'] = 'Password is incorrect';
        }
        
        // Check if email already exists
        if (empty($errors)) {
            $check_query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':email', $new_email);
            $check_stmt->bindParam(':user_id', $current_user['id']);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $errors['new_email'] = 'Email already exists';
            }
        }
        
        if (empty($errors)) {
            $update_query = "UPDATE users SET email = :email WHERE id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':email', $new_email);
            $update_stmt->bindParam(':user_id', $current_user['id']);
            
            if ($update_stmt->execute()) {
                $success = 'Email updated successfully!';
                // Refresh current user data
                $current_user = getCurrentUser();
            } else {
                $errors['general'] = 'Failed to update email. Please try again.';
            }
        }
    }
    
    if (isset($_POST['update_privacy'])) {
        $profile_visibility = $_POST['profile_visibility'];
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
        
        if (!in_array($profile_visibility, ['public', 'private'])) {
            $errors['profile_visibility'] = 'Invalid privacy setting';
        }
        
        if (empty($errors)) {
            $update_query = "UPDATE users SET profile_visibility = :visibility, email_notifications = :email_notif, push_notifications = :push_notif WHERE id = :user_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':visibility', $profile_visibility);
            $update_stmt->bindParam(':email_notif', $email_notifications);
            $update_stmt->bindParam(':push_notif', $push_notifications);
            $update_stmt->bindParam(':user_id', $current_user['id']);
            
            if ($update_stmt->execute()) {
                $success = 'Privacy settings updated successfully!';
                // Refresh current user data
                $current_user = getCurrentUser();
            } else {
                $errors['general'] = 'Failed to update privacy settings. Please try again.';
            }
        }
    }
}

// Get user statistics
$database = new Database();
$db = $database->getConnection();

$stats_query = "SELECT 
    (SELECT COUNT(*) FROM group_members WHERE user_id = :user_id) as groups_joined,
    (SELECT COUNT(*) FROM groups_table WHERE owner_id = :user_id AND is_active = TRUE) as groups_created,
    (SELECT COUNT(*) FROM chat_messages WHERE user_id = :user_id AND is_deleted = FALSE) as messages_sent,
    (SELECT COUNT(*) FROM notifications WHERE user_id = :user_id) as total_notifications";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':user_id', $current_user['id']);
$stats_stmt->execute();
$user_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<main class="main-content">
    <section class="settings-section">
        <div class="container">
            <div class="settings-layout">
                <div class="settings-sidebar">
                    <div class="settings-nav">
                        <h2>Account Settings</h2>
                        <nav class="nav-menu">
                            <button class="nav-item active" onclick="showSection('account')">
                                <i class="fas fa-user"></i> Account Info
                            </button>
                            <button class="nav-item" onclick="showSection('security')">
                                <i class="fas fa-shield-alt"></i> Security
                            </button>
                            <button class="nav-item" onclick="showSection('privacy')">
                                <i class="fas fa-eye"></i> Privacy
                            </button>
                            <button class="nav-item" onclick="showSection('notifications')">
                                <i class="fas fa-bell"></i> Notifications
                            </button>
                            <button class="nav-item" onclick="showSection('data')">
                                <i class="fas fa-download"></i> Data & Export
                            </button>
                        </nav>
                        
                        <div class="account-summary">
                            <h3>Account Summary</h3>
                            <div class="summary-stats">
                                <div class="stat">
                                    <span class="stat-number"><?php echo $user_stats['groups_joined']; ?></span>
                                    <span class="stat-label">Groups Joined</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-number"><?php echo $user_stats['groups_created']; ?></span>
                                    <span class="stat-label">Groups Created</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-number"><?php echo $user_stats['messages_sent']; ?></span>
                                    <span class="stat-label">Messages Sent</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="settings-main">
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
                    
                    <!-- Account Info Section -->
                    <div class="settings-section-content active" id="account">
                        <div class="section-header">
                            <h3>Account Information</h3>
                            <p>Manage your basic account details</p>
                        </div>
                        
                        <div class="settings-card">
                            <div class="account-info">
                                <div class="info-row">
                                    <label>Username</label>
                                    <span><?php echo htmlspecialchars($current_user['username']); ?></span>
                                </div>
                                <div class="info-row">
                                    <label>Display Name</label>
                                    <span><?php echo htmlspecialchars($current_user['display_name'] ?: 'Not set'); ?></span>
                                </div>
                                <div class="info-row">
                                    <label>Email</label>
                                    <span><?php echo htmlspecialchars($current_user['email']); ?></span>
                                </div>
                                <div class="info-row">
                                    <label>Member Since</label>
                                    <span><?php echo date('F j, Y', strtotime($current_user['created_at'])); ?></span>
                                </div>
                                <div class="info-row">
                                    <label>Last Updated</label>
                                    <span><?php echo date('F j, Y g:i A', strtotime($current_user['updated_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="account-actions">
                                <a href="profile.php" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Security Section -->
                    <div class="settings-section-content" id="security">
                        <div class="section-header">
                            <h3>Security Settings</h3>
                            <p>Manage your password and account security</p>
                        </div>
                        
                        <div class="settings-card">
                            <h4>Change Password</h4>
                            <form method="POST" class="security-form">
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" 
                                           class="form-control <?php echo isset($errors['current_password']) ? 'error' : ''; ?>" required>
                                    <?php if (isset($errors['current_password'])): ?>
                                        <div class="error-message"><?php echo $errors['current_password']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" id="new_password" name="new_password" 
                                               class="form-control <?php echo isset($errors['new_password']) ? 'error' : ''; ?>" 
                                               minlength="6" required>
                                        <?php if (isset($errors['new_password'])): ?>
                                            <div class="error-message"><?php echo $errors['new_password']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" 
                                               class="form-control <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>" 
                                               minlength="6" required>
                                        <?php if (isset($errors['confirm_password'])): ?>
                                            <div class="error-message"><?php echo $errors['confirm_password']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Update Password
                                </button>
                            </form>
                        </div>
                        
                        <div class="settings-card">
                            <h4>Change Email Address</h4>
                            <form method="POST" class="security-form">
                                <div class="form-group">
                                    <label for="new_email">New Email Address</label>
                                    <input type="email" id="new_email" name="new_email" 
                                           class="form-control <?php echo isset($errors['new_email']) ? 'error' : ''; ?>" 
                                           value="<?php echo htmlspecialchars($current_user['email']); ?>" required>
                                    <?php if (isset($errors['new_email'])): ?>
                                        <div class="error-message"><?php echo $errors['new_email']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email_password">Confirm Password</label>
                                    <input type="password" id="email_password" name="email_password" 
                                           class="form-control <?php echo isset($errors['email_password']) ? 'error' : ''; ?>" required>
                                    <?php if (isset($errors['email_password'])): ?>
                                        <div class="error-message"><?php echo $errors['email_password']; ?></div>
                                    <?php endif; ?>
                                    <div class="form-help">Enter your current password to confirm this change</div>
                                </div>
                                
                                <button type="submit" name="update_email" class="btn btn-primary">
                                    <i class="fas fa-envelope"></i> Update Email
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Privacy Section -->
                    <div class="settings-section-content" id="privacy">
                        <div class="section-header">
                            <h3>Privacy Settings</h3>
                            <p>Control who can see your information and how you appear to others</p>
                        </div>
                        
                        <div class="settings-card">
                            <form method="POST" class="privacy-form">
                                <div class="form-group">
                                    <label for="profile_visibility">Profile Visibility</label>
                                    <select id="profile_visibility" name="profile_visibility" class="form-control">
                                        <option value="public" <?php echo ($current_user['profile_visibility'] ?? 'public') === 'public' ? 'selected' : ''; ?>>
                                            Public - Anyone can see your profile
                                        </option>
                                        <option value="private" <?php echo ($current_user['profile_visibility'] ?? 'public') === 'private' ? 'selected' : ''; ?>>
                                            Private - Only group members can see your profile
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="checkbox-group">
                                    <h4>Communication Preferences</h4>
                                    
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="email_notifications" name="email_notifications" 
                                               <?php echo ($current_user['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                        <label for="email_notifications">
                                            <span class="checkbox-label">Email Notifications</span>
                                            <span class="checkbox-description">Receive email notifications for group activities and messages</span>
                                        </label>
                                    </div>
                                    
                                    <div class="checkbox-item">
                                        <input type="checkbox" id="push_notifications" name="push_notifications" 
                                               <?php echo ($current_user['push_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                        <label for="push_notifications">
                                            <span class="checkbox-label">Push Notifications</span>
                                            <span class="checkbox-description">Receive browser notifications for real-time updates</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_privacy" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Privacy Settings
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Notifications Section -->
                    <div class="settings-section-content" id="notifications">
                        <div class="section-header">
                            <h3>Notification Preferences</h3>
                            <p>Choose what notifications you want to receive</p>
                        </div>
                        
                        <div class="settings-card">
                            <div class="notification-settings">
                                <div class="notification-category">
                                    <h4>Group Activities</h4>
                                    <div class="notification-options">
                                        <div class="notification-item">
                                            <div class="notification-info">
                                                <span class="notification-title">New Messages</span>
                                                <span class="notification-desc">When someone posts in your groups</span>
                                            </div>
                                            <div class="notification-controls">
                                                <label class="toggle-switch">
                                                    <input type="checkbox" checked>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="notification-item">
                                            <div class="notification-info">
                                                <span class="notification-title">Join Requests</span>
                                                <span class="notification-desc">When someone wants to join your groups</span>
                                            </div>
                                            <div class="notification-controls">
                                                <label class="toggle-switch">
                                                    <input type="checkbox" checked>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="notification-item">
                                            <div class="notification-info">
                                                <span class="notification-title">Member Updates</span>
                                                <span class="notification-desc">When members join or leave your groups</span>
                                            </div>
                                            <div class="notification-controls">
                                                <label class="toggle-switch">
                                                    <input type="checkbox" checked>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="notification-category">
                                    <h4>System Updates</h4>
                                    <div class="notification-options">
                                        <div class="notification-item">
                                            <div class="notification-info">
                                                <span class="notification-title">Security Alerts</span>
                                                <span class="notification-desc">Important security and account updates</span>
                                            </div>
                                            <div class="notification-controls">
                                                <label class="toggle-switch">
                                                    <input type="checkbox" checked disabled>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="notification-item">
                                            <div class="notification-info">
                                                <span class="notification-title">Feature Updates</span>
                                                <span class="notification-desc">New features and platform improvements</span>
                                            </div>
                                            <div class="notification-controls">
                                                <label class="toggle-switch">
                                                    <input type="checkbox">
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Data & Export Section -->
                    <div class="settings-section-content" id="data">
                        <div class="section-header">
                            <h3>Data & Export</h3>
                            <p>Download your data or delete your account</p>
                        </div>
                        
                        <div class="settings-card">
                            <h4>Export Your Data</h4>
                            <p>Download a copy of your TRAGOS data including your profile, groups, and messages.</p>
                            <button class="btn btn-outline" onclick="exportData()">
                                <i class="fas fa-download"></i> Download My Data
                            </button>
                        </div>
                        
                        <div class="settings-card danger-zone">
                            <h4>Danger Zone</h4>
                            <div class="danger-actions">
                                <div class="danger-item">
                                    <div class="danger-info">
                                        <h5>Clear All Notifications</h5>
                                        <p>Remove all your notifications permanently</p>
                                    </div>
                                    <button class="btn btn-outline" onclick="clearAllNotifications()">
                                        Clear Notifications
                                    </button>
                                </div>
                                
                                <div class="danger-item">
                                    <div class="danger-info">
                                        <h5>Leave All Groups</h5>
                                        <p>Leave all groups you're a member of (except ones you own)</p>
                                    </div>
                                    <button class="btn btn-outline" onclick="leaveAllGroups()">
                                        Leave All Groups
                                    </button>
                                </div>
                                
                                <div class="danger-item">
                                    <div class="danger-info">
                                        <h5>Delete Account</h5>
                                        <p>Permanently delete your account and all associated data</p>
                                    </div>
                                    <button class="btn btn-danger" onclick="deleteAccount()">
                                        Delete Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.settings-section {
    padding: 3rem 0;
    background: var(--bg-secondary);
    min-height: calc(100vh - 160px);
}

.settings-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 3rem;
}

.settings-sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.settings-nav {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
}

.settings-nav h2 {
    color: var(--primary-color);
    margin-bottom: 2rem;
    font-size: 1.5rem;
}

.nav-menu {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: none;
    border: none;
    border-radius: var(--border-radius);
    color: var(--text-secondary);
    font-family: var(--font-family);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    text-align: left;
    width: 100%;
}

.nav-item:hover,
.nav-item.active {
    background: var(--bg-secondary);
    color: var(--primary-color);
}

.account-summary {
    border-top: 1px solid var(--border-color);
    padding-top: 2rem;
}

.account-summary h3 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.summary-stats {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.stat {
    text-align: center;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
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

.settings-main {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.settings-section-content {
    display: none;
    padding: 2rem;
}

.settings-section-content.active {
    display: block;
}

.section-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.section-header h3 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.section-header p {
    color: var(--text-secondary);
}

.settings-card {
    background: var(--bg-secondary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border-color);
}

.settings-card h4 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
}

.account-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 2rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--bg-primary);
    border-radius: var(--border-radius);
}

.info-row label {
    font-weight: 500;
    color: var(--text-secondary);
}

.info-row span {
    color: var(--text-primary);
}

.security-form,
.privacy-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.checkbox-group {
    margin: 1.5rem 0;
}

.checkbox-group h4 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.checkbox-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
}

.checkbox-item input[type="checkbox"] {
    margin-top: 0.25rem;
}

.checkbox-item label {
    flex: 1;
    cursor: pointer;
}

.checkbox-label {
    display: block;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.checkbox-description {
    display: block;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.notification-settings {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.notification-category h4 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.notification-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--bg-primary);
    border-radius: var(--border-radius);
}

.notification-info {
    flex: 1;
}

.notification-title {
    display: block;
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.notification-desc {
    display: block;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--border-color);
    transition: var(--transition);
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: var(--transition);
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: var(--primary-color);
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

input:disabled + .toggle-slider {
    opacity: 0.5;
    cursor: not-allowed;
}

.danger-zone {
    border-color: var(--error-color);
    background: rgba(220, 20, 60, 0.05);
}

.danger-zone h4 {
    color: var(--error-color);
}

.danger-actions {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.danger-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--bg-primary);
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.danger-info h5 {
    color: var(--text-primary);
    margin: 0 0 0.25rem 0;
}

.danger-info p {
    color: var(--text-muted);
    margin: 0;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .settings-layout {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .settings-sidebar {
        position: static;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .danger-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .notification-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}
</style>

<script>
function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.settings-section-content').forEach(section => {
        section.classList.remove('active');
    });
    
    // Remove active class from all nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(sectionName).classList.add('active');
    
    // Add active class to clicked nav item
    event.target.classList.add('active');
}

function exportData() {
    if (confirm('This will generate a download of all your TRAGOS data. Continue?')) {
        // Create a temporary link to download data
        window.location.href = 'api/export-data.php';
    }
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
                TRAGOS.showToast('All notifications cleared successfully', 'success');
            } else {
                TRAGOS.showToast('Error clearing notifications: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            TRAGOS.showToast('An error occurred while clearing notifications', 'error');
        });
    }
}

function leaveAllGroups() {
    if (confirm('Are you sure you want to leave all groups? You will lose access to group chats and content. Groups you own will not be affected.')) {
        if (confirm('This action cannot be undone. Are you absolutely sure?')) {
            fetch('api/leave-all-groups.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    TRAGOS.showToast('Left all groups successfully', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    TRAGOS.showToast('Error leaving groups: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                TRAGOS.showToast('An error occurred while leaving groups', 'error');
            });
        }
    }
}

function deleteAccount() {
    if (confirm('⚠️ WARNING: This will permanently delete your account and ALL associated data including groups you own, messages, and profile information. This action CANNOT be undone.')) {
        if (confirm('Are you absolutely certain you want to delete your account? Type "DELETE" in the next prompt to confirm.')) {
            const confirmation = prompt('Type "DELETE" to confirm account deletion:');
            if (confirmation === 'DELETE') {
                const password = prompt('Enter your password to confirm:');
                if (password) {
                    fetch('api/delete-account.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ password: password })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Your account has been deleted. You will be redirected to the homepage.');
                            window.location.href = 'index.php';
                        } else {
                            TRAGOS.showToast('Error deleting account: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        TRAGOS.showToast('An error occurred while deleting account', 'error');
                    });
                }
            } else {
                TRAGOS.showToast('Account deletion cancelled - confirmation text did not match', 'info');
            }
        }
    }
}

// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (newPassword && confirmPassword) {
        function validatePasswords() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
    }
});
</script>

<?php include 'includes/footer.php'; ?>