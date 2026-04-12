<?php
$page_title = 'Create Group';
include 'includes/header.php';

requireLogin();

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
    if (empty($name)) {
        $errors['name'] = 'Group name is required';
    } elseif (strlen($name) < 3) {
        $errors['name'] = 'Group name must be at least 3 characters';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Description is required';
    } elseif (strlen($description) < 20) {
        $errors['description'] = 'Description must be at least 20 characters';
    }
    
    if (empty($destination)) {
        $errors['destination'] = 'Destination is required';
    }
    
    $valid_categories = ['backpacking', 'luxury', 'adventure', 'cultural', 'food', 'photography', 'solo', 'family', 'business', 'other'];
    if (!in_array($category, $valid_categories)) {
        $errors['category'] = 'Invalid category selected';
    }
    
    if (!in_array($privacy, ['public', 'private'])) {
        $errors['privacy'] = 'Invalid privacy setting';
    }
    
    if ($max_members < 2 || $max_members > 100) {
        $errors['max_members'] = 'Maximum members must be between 2 and 100';
    }
    
    // Handle image upload
    $group_image = $category . '.png'; // Default to category image
    if (isset($_FILES['group_image']) && $_FILES['group_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['group_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['group_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'group_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $upload_path = 'assets/images/' . $new_filename;
            
            if (move_uploaded_file($_FILES['group_image']['tmp_name'], $upload_path)) {
                $group_image = $new_filename;
            } else {
                $errors['group_image'] = 'Failed to upload image';
            }
        } else {
            $errors['group_image'] = 'Invalid image type. Please use JPG, PNG, or GIF';
        }
    }
    
    // Create group if no errors
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Insert group
            $insert_query = "INSERT INTO groups_table (name, description, destination, category, privacy, owner_id, max_members, group_image) 
                            VALUES (:name, :description, :destination, :category, :privacy, :owner_id, :max_members, :group_image)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':name', $name);
            $insert_stmt->bindParam(':description', $description);
            $insert_stmt->bindParam(':destination', $destination);
            $insert_stmt->bindParam(':category', $category);
            $insert_stmt->bindParam(':privacy', $privacy);
            $insert_stmt->bindParam(':owner_id', $current_user['id']);
            $insert_stmt->bindParam(':max_members', $max_members);
            $insert_stmt->bindParam(':group_image', $group_image);
            
            if ($insert_stmt->execute()) {
                $group_id = $db->lastInsertId();
                
                // Add creator as owner member
                $member_query = "INSERT INTO group_members (group_id, user_id, role) VALUES (:group_id, :user_id, 'owner')";
                $member_stmt = $db->prepare($member_query);
                $member_stmt->bindParam(':group_id', $group_id);
                $member_stmt->bindParam(':user_id', $current_user['id']);
                $member_stmt->execute();
                
                $db->commit();
                header('Location: group-details.php?id=' . $group_id);
                exit();
            } else {
                throw new Exception('Failed to create group');
            }
        } catch (Exception $e) {
            $db->rollBack();
            $errors['general'] = 'Failed to create group. Please try again.';
        }
    }
}

$categories = [
    'backpacking' => 'Backpacking',
    'luxury' => 'Luxury Travel',
    'adventure' => 'Adventure',
    'cultural' => 'Cultural',
    'food' => 'Food Tours',
    'photography' => 'Photography',
    'solo' => 'Solo Travel',
    'family' => 'Family',
    'business' => 'Business',
    'other' => 'Other'
];
?>

<main class="main-content">
    <section class="create-group-section">
        <div class="container">
            <div class="create-group-header">
                <h1>Create a New Travel Group</h1>
                <p>Start your own travel community and connect with like-minded adventurers</p>
            </div>
            
            <div class="form-container large">
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-error">
                        <?php echo $errors['general']; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" class="create-group-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Group Name *</label>
                            <input type="text" id="name" name="name" class="form-control <?php echo isset($errors['name']) ? 'error' : ''; ?>" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   placeholder="e.g., Europe Backpackers 2024" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="error-message"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="destination">Destination *</label>
                            <input type="text" id="destination" name="destination" class="form-control <?php echo isset($errors['destination']) ? 'error' : ''; ?>" 
                                   value="<?php echo isset($_POST['destination']) ? htmlspecialchars($_POST['destination']) : ''; ?>" 
                                   placeholder="e.g., Europe, Southeast Asia, Japan" required>
                            <?php if (isset($errors['destination'])): ?>
                                <div class="error-message"><?php echo $errors['destination']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" class="form-control <?php echo isset($errors['description']) ? 'error' : ''; ?>" 
                                  rows="5" placeholder="Describe your travel plans, what kind of travelers you're looking for, activities you want to do..." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="error-message"><?php echo $errors['description']; ?></div>
                        <?php endif; ?>
                        <div class="form-help">Minimum 20 characters. Be specific about your travel plans and expectations.</div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category *</label>
                            <select id="category" name="category" class="form-control <?php echo isset($errors['category']) ? 'error' : ''; ?>" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo (isset($_POST['category']) && $_POST['category'] === $key) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category'])): ?>
                                <div class="error-message"><?php echo $errors['category']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="privacy">Privacy Setting *</label>
                            <select id="privacy" name="privacy" class="form-control <?php echo isset($errors['privacy']) ? 'error' : ''; ?>" required>
                                <option value="">Select privacy</option>
                                <option value="public" <?php echo (isset($_POST['privacy']) && $_POST['privacy'] === 'public') ? 'selected' : ''; ?>>
                                    Public - Anyone can join instantly
                                </option>
                                <option value="private" <?php echo (isset($_POST['privacy']) && $_POST['privacy'] === 'private') ? 'selected' : ''; ?>>
                                    Private - Requires approval to join
                                </option>
                            </select>
                            <?php if (isset($errors['privacy'])): ?>
                                <div class="error-message"><?php echo $errors['privacy']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="max_members">Maximum Members *</label>
                            <input type="number" id="max_members" name="max_members" class="form-control <?php echo isset($errors['max_members']) ? 'error' : ''; ?>" 
                                   value="<?php echo isset($_POST['max_members']) ? $_POST['max_members'] : '20'; ?>" 
                                   min="2" max="100" required>
                            <?php if (isset($errors['max_members'])): ?>
                                <div class="error-message"><?php echo $errors['max_members']; ?></div>
                            <?php endif; ?>
                            <div class="form-help">Between 2 and 100 members</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="group_image">Group Image (optional)</label>
                            <input type="file" id="group_image" name="group_image" class="form-control <?php echo isset($errors['group_image']) ? 'error' : ''; ?>" 
                                   accept="image/jpeg,image/png,image/gif">
                            <?php if (isset($errors['group_image'])): ?>
                                <div class="error-message"><?php echo $errors['group_image']; ?></div>
                            <?php endif; ?>
                            <div class="form-help">JPG, PNG, or GIF. Max 5MB. If not provided, a default image will be used.</div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-large">
                            <i class="fas fa-plus"></i> Create Group
                        </button>
                        <a href="groups.php" class="btn btn-outline btn-large">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="create-group-tips">
                <h3>Tips for Creating a Successful Group</h3>
                <div class="tips-grid">
                    <div class="tip-card">
                        <i class="fas fa-bullseye"></i>
                        <h4>Be Specific</h4>
                        <p>Clearly describe your travel plans, dates, budget range, and what kind of travelers you're looking for.</p>
                    </div>
                    <div class="tip-card">
                        <i class="fas fa-users"></i>
                        <h4>Set Expectations</h4>
                        <p>Mention your travel style, accommodation preferences, and activity interests to attract compatible members.</p>
                    </div>
                    <div class="tip-card">
                        <i class="fas fa-comments"></i>
                        <h4>Stay Active</h4>
                        <p>Regularly engage with your group members, share updates, and keep the conversation going.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.create-group-section {
    padding: 4rem 0;
    background: var(--bg-secondary);
}

.create-group-header {
    text-align: center;
    margin-bottom: 3rem;
}

.create-group-header h1 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.create-group-header p {
    font-size: 1.1rem;
    color: var(--text-secondary);
}

.form-container.large {
    max-width: 800px;
    margin-bottom: 4rem;
}

.create-group-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-help {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-top: 2rem;
}

.create-group-tips {
    max-width: 1000px;
    margin: 0 auto;
}

.create-group-tips h3 {
    text-align: center;
    color: var(--primary-color);
    margin-bottom: 2rem;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.tip-card {
    background: var(--bg-primary);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
}

.tip-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.tip-card i {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.tip-card h4 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.tip-card p {
    color: var(--text-secondary);
    font-size: 0.9rem;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/footer.php'; ?>