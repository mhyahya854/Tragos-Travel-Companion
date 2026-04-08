<?php
$page_title = 'Browse Groups';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$privacy = isset($_GET['privacy']) ? sanitizeInput($_GET['privacy']) : '';

// Build query
$where_conditions = ["g.is_active = TRUE"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(g.name LIKE :search OR g.description LIKE :search OR g.destination LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($category)) {
    $where_conditions[] = "g.category = :category";
    $params[':category'] = $category;
}

if (!empty($privacy)) {
    $where_conditions[] = "g.privacy = :privacy";
    $params[':privacy'] = $privacy;
}

$where_clause = implode(' AND ', $where_conditions);

$query = "SELECT g.*, u.display_name as owner_name, u.username as owner_username 
          FROM groups_table g 
          JOIN users u ON g.owner_id = u.id 
          WHERE $where_clause
          ORDER BY g.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
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
    <section class="groups-header-section">
        <div class="container">
            <div class="groups-header">
                <h1>Discover Travel Groups</h1>
                <p>Find your perfect travel companions and join amazing adventures worldwide</p>
            </div>
        </div>
    </section>

    <section class="groups-filter-section">
        <div class="container">
            <div class="filters-container">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Search groups, destinations..." 
                               value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                    </div>
                    
                    <div class="filter-group">
                        <select name="category" class="filter-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $category === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <select name="privacy" class="filter-select">
                            <option value="">All Types</option>
                            <option value="public" <?php echo $privacy === 'public' ? 'selected' : ''; ?>>Public</option>
                            <option value="private" <?php echo $privacy === 'private' ? 'selected' : ''; ?>>Private</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    
                    <?php if ($search || $category || $privacy): ?>
                        <a href="groups.php" class="btn btn-outline">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
                
                <?php if ($current_user): ?>
                    <a href="create-group.php" class="btn btn-secondary">
                        <i class="fas fa-plus"></i> Create Group
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="groups-listing-section">
        <div class="container">
            <?php if (empty($groups)): ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>No groups found</h3>
                    <p>Try adjusting your search criteria or browse all groups</p>
                    <?php if ($search || $category || $privacy): ?>
                        <a href="groups.php" class="btn btn-primary">View All Groups</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="results-header">
                    <h3><?php echo count($groups); ?> group<?php echo count($groups) !== 1 ? 's' : ''; ?> found</h3>
                </div>
                
                <div class="groups-grid">
                    <?php foreach ($groups as $group): ?>
                        <div class="group-card">
                            <div class="group-image">
                                <img src="assets/images/<?php echo $group['group_image'] ?: 'other.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($group['name']); ?>">
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
                            </div>
                            
                            <div class="group-content">
                                <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                                <p class="group-description">
                                    <?php echo htmlspecialchars(substr($group['description'], 0, 120)) . '...'; ?>
                                </p>
                                
                                <div class="group-meta">
                                    <div class="group-destination">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($group['destination']); ?>
                                    </div>
                                    <div class="group-members">
                                        <i class="fas fa-users"></i>
                                        <?php echo $group['current_members']; ?>/<?php echo $group['max_members']; ?> members
                                    </div>
                                </div>
                                
                                <div class="group-owner">
                                    <span>Created by <?php echo htmlspecialchars($group['owner_name'] ?: $group['owner_username']); ?></span>
                                </div>
                                
                                <div class="group-actions">
                                    <a href="group-details.php?id=<?php echo $group['id']; ?>" class="btn btn-primary btn-small">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
.groups-header-section {
    background: var(--gradient-primary);
    color: var(--text-light);
    padding: 4rem 0 2rem;
    text-align: center;
}

.groups-header h1 {
    color: var(--text-light);
    margin-bottom: 1rem;
}

.groups-header p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
}

.groups-filter-section {
    background: var(--bg-secondary);
    padding: 2rem 0;
    border-bottom: 1px solid var(--border-color);
}

.filters-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.filters-form {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    flex: 1;
}

.filter-group {
    min-width: 200px;
}

.search-input,
.filter-select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    font-family: var(--font-family);
    background: var(--bg-primary);
}

.search-input:focus,
.filter-select:focus {
    outline: none;
    border-color: var(--primary-color);
}

.groups-listing-section {
    padding: 3rem 0;
}

.results-header {
    margin-bottom: 2rem;
}

.results-header h3 {
    color: var(--text-secondary);
    font-weight: 500;
}

.no-results {
    text-align: center;
    padding: 4rem 2rem;
}

.no-results-icon {
    font-size: 4rem;
    color: var(--text-muted);
    margin-bottom: 1rem;
}

.no-results h3 {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.group-badges {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.privacy-badge {
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: var(--border-radius);
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.group-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .filters-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filters-form {
        flex-direction: column;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .group-actions {
        flex-direction: column;
    }
}
</style>

<?php include 'includes/footer.php'; ?>