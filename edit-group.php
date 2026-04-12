<?php
$page_title = 'Edit Group';
include 'includes/header.php';

requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: my-groups.php');
    exit();
}

$group_id = (int)$_GET['id'];
$database = new Database();
$db = $database->getConnection();

// Check if user is the owner
$auth_query = "SELECT * FROM groups_table WHERE id = :group_id AND owner_id = :user_id";
$auth_stmt = $db->prepare($auth_query);
$auth_stmt->bindParam(':group_id', $group_id);
$auth_stmt->bindParam(':user_id', $current_user['id']);
$auth_stmt->execute();

if ($auth_stmt->rowCount() == 0) {
    header('Location: group-details.php?id=' . $group_id);
    exit();
}

$group = $auth_stmt->fetch(PDO::FETCH_ASSOC);
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $destination = sanitizeInput($_POST['destination']);
    $category = sanitizeInput($_POST['category']);
    $privacy = sanitizeInput($_POST['privacy']);
    $max_members = (int)$_POST['max_members'];
    
    // Validation
    if (empty($name)) $errors['name'] = 'Group name is required';
    if (empty($description)) $errors['description'] = 'Description is required';
    if (empty($destination)) $errors['destination'] = 'Destination is required';
    if (!in_array($category, ['backpacking', 'luxury', 'adventure', 'cultural', 'food', 'photography', 'solo', 'family', 'business', 'other'])) {
        $errors['category'] = 'Invalid category';
    }  'solo', 'family', 'business', 'other'])) {
        $errors['category'] = 'Invalid category';
    }
    
    if (!in_array($privacy, ['public', 'private'])) {
        $errors['privacy'] = 'Invalid privacy setting';
    }
    
    if ($max_members < $group['current_members']) {
        $errors['max_members'] = 'Maximum members cannot be less than current members (' . $group['current_members'] . ')';
    } elseif ($max_members < 2 || $max_members > 50) {
        $errors['max_members'] = 'Maximum members must be between 2 and 50';
    }
    
    // Handle image upload
    $group_image = $group['group_image'];
    if (isset($_FILES['group_image']) && $_FILES['group_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['group_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['group_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'group_' . $group_id . '_' . time() . '.' . $file_extension;
            $upload_path = 'assets/images/' . $new_filename;
            
            if (move_uploaded_file($_FILES['group_image']['tmp_name'], $upload_path)) {
                if ($group['group_image'] && !in_array($group['group_image'], ['backpacking.png', 'luxury.png', 'adventure.png', 'cultural.png', 'food.png', 'photography.png', 'solo.png', 'family.png', 'business.png', 'other.png'])) {
                    $old_file = 'assets/images/' . $group['group_image'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }
                $group_image = $new_filename;
            } else {
                $errors['group_image'] = 'Failed to upload group image';
            }
        } else {
            $errors['group_image'] = 'Invalid image type. Please use JPG, PNG, or GIF';
        }
    }
    
    // Update group if no errors
    if (empty($errors)) {
        $update_query = "UPDATE groups_table SET 
                        name = :name, 
                        description = :description, 
                        destination = :destination, 
                        category = :category, 
                        privacy = :privacy, 
                        max_members = :max_members, 
                        group_image = :group_image,
                        updated_at = CURRENT_TIMESTAMP 
                        WHERE id = :group_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':name', $name);
        $update_stmt->bindParam(':description', $description);
        $update_stmt->bindParam(':destination', $destination);
        $update_stmt->bindParam(':category', $category);
        $update_stmt->bindParam(':privacy', $privacy);
        $update_stmt->bindParam(':max_members', $max_members);
        $update_stmt->bindParam(':group_image', $group_image);
        $update_stmt->bindParam(':group_id', $group_id);
        
        if ($update_stmt->execute()) {
            $success = 'Group updated successfully!';
            // Refresh group data
            $auth_stmt->execute();
            $group = $auth_stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $errors['general'] = 'Failed to update group. Please try again.';
        }
    }
}
?>

<main class="main-content">
    <div class="page-container">
        <section class="page-header">
            <div class="container">
                <div class="header-content">
                    <a href="group-details.php?id=<?php echo $group_id; ?>" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div class="header-info">
                        <h1>Edit Group</h1>
                        <p><?php echo htmlspecialchars($group['name']); ?></p>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="content-section">
            <div class="container">
                <div class="edit-layout">
                    <div class="form-container">
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
                        
                        <form method="POST" enctype="multipart/form-data" class="edit-form">
                            <div class="form-section">
                                <h3>Basic Information</h3>
                                
                                <div class="form-group">
                                    <label for="name">Group Name *</label>
                                    <input type="text" id="name" name="name" 
                                           class="form-control <?php echo isset($errors['name']) ? 'error' : ''; ?>" 
                                           value="<?php echo htmlspecialchars($group['name']); ?>" required>
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="error-message"><?php echo $errors['name']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Description *</label>
                                    <textarea id="description" name="description" 
                                              class="form-control <?php echo isset($errors['description']) ? 'error' : ''; ?>" 
                                              rows="4" required><?php echo htmlspecialchars($group['description']); ?></textarea>
                                    <?php if (isset($errors['description'])): ?>
                                        <div class="error-message"><?php echo $errors['description']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="destination">Destination *</label>
                                        <input type="text" id="destination" name="destination" 
                                               class="form-control <?php echo isset($errors['destination']) ? 'error' : ''; ?>" 
                                               value="<?php echo htmlspecialchars($group['destination']); ?>" required>
                                        <?php if (isset($errors['destination'])): ?>
                                            <div class="error-message"><?php echo $errors['destination']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="category">Category *</label>
                                        <select id="category" name="category" 
                                                class="form-control <?php echo isset($errors['category']) ? 'error' : ''; ?>" required>
                                            <option value="backpacking" <?php echo $group['category'] === 'backpacking' ? 'selected' : ''; ?>>Backpacking</option>
                                            <option value="luxury" <?php echo $group['category'] === 'luxury' ? 'selected' : ''; ?>>Luxury Travel</option>
                                            <option value="adventure" <?php echo $group['category'] === 'adventure' ? 'selected' : ''; ?>>Adventure</option>
                                            <option value="cultural" <?php echo $group['category'] === 'cultural' ? 'selected' : ''; ?>>Cultural</option>
                                            <option value="food" <?php echo $group['category'] === 'food' ? 'selected' : ''; ?>>Food & Culinary</option>
                                            <option value="photography" <?php echo $group['category'] === 'photography' ? 'selected' : ''; ?>>Photography</option>
                                            <option value="solo" <?php echo $group['category'] === 'solo' ? 'selected' : ''; ?>>Solo Travel</option>
                                            <option value="family" <?php echo $group['category'] === 'family' ? 'selected' : ''; ?>>Family Travel</option>
                                            <option value="business" <?php echo $group['category'] === 'business' ? 'selected' : ''; ?>>Business Travel</option>
                                            <option value="other" <?php echo $group['category'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <?php if (isset($errors['category'])): ?>
                                            <div class="error-message"><?php echo $errors['category']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Group Settings</h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="privacy">Privacy *</label>
                                        <select id="privacy" name="privacy" 
                                                class="form-control <?php echo isset($errors['privacy']) ? 'error' : ''; ?>" required>
                                            <option value="public" <?php echo $group['privacy'] === 'public' ? 'selected' : ''; ?>>Public - Anyone can join</option>
                                            <option value="private" <?php echo $group['privacy'] === 'private' ? 'selected' : ''; ?>>Private - Requires approval</option>
                                        </select>
                                        <?php if (isset($errors['privacy'])): ?>
                                            <div class="error-message"><?php echo $errors['privacy']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="max_members">Maximum Members *</label>
                                        <input type="number" id="max_members" name="max_members" 
                                               class="form-control <?php echo isset($errors['max_members']) ? 'error' : ''; ?>" 
                                               value="<?php echo $group['max_members']; ?>" min="<?php echo $group['current_members']; ?>" max="50" required>
                                        <?php if (isset($errors['max_members'])): ?>
                                            <div class="error-message"><?php echo $errors['max_members']; ?></div>
                                        <?php endif; ?>
                                        <div class="form-help">Current members: <?php echo $group['current_members']; ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3>Group Image</h3>
                                
                                <div class="current-image">
                                    <img src="assets/images/<?php echo $group['group_image'] ?: 'other.png'; ?>" 
                                         alt="Current group image" class="current-group-image" id="imagePreview">
                                </div>
                                
                                <div class="form-group">
                                    <label for="group_image">Upload New Image</label>
                                    <input type="file" id="group_image" name="group_image" 
                                           class="form-control <?php echo isset($errors['group_image']) ? 'error' : ''; ?>" 
                                           accept="image/jpeg,image/png,image/gif">
                                    <?php if (isset($errors['group_image'])): ?>
                                        <div class="error-message"><?php echo $errors['group_image']; ?></div>
                                    <?php endif; ?>
                                    <div class="form-help">JPG, PNG, or GIF. Max 5MB. Leave empty to keep current image.</div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Group
                                </button>
                                <a href="group-details.php?id=<?php echo $group_id; ?>" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <div class="sidebar">
                        <div class="info-card">
                            <h4>Group Statistics</h4>
                            <div class="stats-list">
                                <div class="stat-item">
                                    <span class="stat-label">Current Members</span>
                                    <span class="stat-number"><?php echo $group['current_members']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Max Members</span>
                                    <span class="stat-number"><?php echo $group['max_members']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Created</span>
                                    <span class="stat-number"><?php echo date('M j, Y', strtotime($group['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h4>Quick Actions</h4>
                            <div class="quick-actions">
                                <a href="group-details.php?id=<?php echo $group_id; ?>" class="action-link">
                                    <i class="fas fa-eye"></i> View Group
                                </a>
                                <a href="group-chat.php?id=<?php echo $group_id; ?>" class="action-link">
                                    <i class="fas fa-comments"></i> Group Chat
                                </a>
                                <a href="manage-requests.php?id=<?php echo $group_id; ?>" class="action-link">
                                    <i class="fas fa-user-check"></i> Manage Requests
                                </a>
                            </div>
                        </div>
                        
                        <div class="info-card danger-zone">
                            <h4>Danger Zone</h4>
                            <p>This action cannot be undone.</p>
                            <button onclick="deleteGroup(<?php echo $group_id; ?>)" class="btn btn-danger btn-small">
                                <i class="fas fa-trash"></i> Delete Group
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<style>
.page-header {
    background: var(--bg-primary);
    border-bottom: 1px solid var(--border-color);
    padding: 2rem 0;
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
    background: var(--bg-secondary);
}

.header-info h1 {
    color: var(--primary-color);
    margin: 0 0 0.5rem 0;
}

.header-info p {
    color: var(--text-secondary);
    margin: 0;
}

.content-section {
    padding: 3rem 0;
}

.edit-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 3rem;
}

.form-container {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
}

.edit-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.form-section {
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--border-color);
}

.form-section:last-of-type {
    border-bottom: none;
    padding-bottom: 0;
}

.form-section h3 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.current-image {
    margin-bottom: 1.5rem;
    text-align: center;
}

.current-group-image {
    width: 200px;
    height: 150px;
    object-fit: cover;
    border-radius: var(--border-radius);
    border: 2px solid var(--border-color);
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
}

.sidebar {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.info-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
}

.info-card h4 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.stat-number {
    color: var(--primary-color);
    font-weight: 600;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.action-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.action-link:hover {
    background: var(--bg-secondary);
    color: var(--primary-color);
}

.danger-zone {
    border: 1px solid var(--error-color);
    background: rgba(220, 20, 60, 0.05);
}

.danger-zone h4 {
    color: var(--error-color);
}

.danger-zone p {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .edit-layout {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
// Handle image preview
document.getElementById('group_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

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
                    alert('Group deleted successfully.');
                    window.location.href = 'my-groups.php';
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
