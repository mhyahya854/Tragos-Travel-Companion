<?php
$page_title = 'Search';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

$search_query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
$search_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'all';

$groups_results = [];
$users_results = [];

if (!empty($search_query)) {
    // Search groups
    if ($search_type === 'all' || $search_type === 'groups') {
        $groups_search = "SELECT g.*, u.display_name as owner_name, u.username as owner_username 
                         FROM groups_table g 
                         JOIN users u ON g.owner_id = u.id 
                         WHERE g.is_active = TRUE AND (
                             g.name LIKE :search OR 
                             g.description LIKE :search OR 
                             g.destination LIKE :search OR
                             g.category LIKE :search
                         )
                         ORDER BY g.created_at DESC 
                         LIMIT 20";
        $groups_stmt = $db->prepare($groups_search);
        $search_param = '%' . $search_query . '%';
        $groups_stmt->bindParam(':search', $search_param);
        $groups_stmt->execute();
        $groups_results = $groups_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Search users (only if logged in)
    if ($current_user && ($search_type === 'all' || $search_type === 'users')) {
        $users_search = "SELECT id, username, display_name, profile_picture, bio, location 
                        FROM users 
                        WHERE (username LIKE :search OR display_name LIKE :search OR bio LIKE :search)
                        AND id != :current_user_id
                        ORDER BY created_at DESC 
                        LIMIT 20";
        $users_stmt = $db->prepare($users_search);
        $users_stmt->bindParam(':search', $search_param);
        $users_stmt->bindParam(':current_user_id', $current_user['id']);
        $users_stmt->execute();
        $users_results = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$total_results = count($groups_results) + count($users_results);
?>

<main class="main-content">
    <section class="search-section">
        <div class="container">
            <div class="search-header">
                <h1>Search TRAGOS</h1>
                <p>Find travel groups and fellow travelers</p>
            </div>
            
            <div class="search-container">
                <form method="GET" class="search-form">
                    <div class="search-input-group">
                        <input type="text" name="q" class="search-input" 
                               placeholder="Search for groups, destinations, or travelers..." 
                               value="<?php echo htmlspecialchars($search_query); ?>" autofocus>
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <div class="search-filters">
                        <div class="filter-group">
                            <label>Search in:</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="type" value="all" <?php echo $search_type === 'all' ? 'checked' : ''; ?>>
                                    <span>Everything</span>
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="type" value="groups" <?php echo $search_type === 'groups' ? 'checked' : ''; ?>>
                                    <span>Groups Only</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <?php if (!empty($search_query)): ?>
                <div class="search-results">
                    <div class="results-header">
                        <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
                        <p><?php echo $total_results; ?> result<?php echo $total_results !== 1 ? 's' : ''; ?> found</p>
                    </div>
                    
                    <?php if ($total_results === 0): ?>
                        <div class="no-results">
                            <i class="fas fa-search"></i>
                            <h3>No results found</h3>
                            <p>Try adjusting your search terms or browse all groups</p>
                            <a href="groups.php" class="btn btn-primary">Browse All Groups</a>
                        </div>
                    <?php else: ?>
                        
                        <?php if (!empty($groups_results)): ?>
                            <div class="results-section">
                                <h3>Groups (<?php echo count($groups_results); ?>)</h3>
                                <div class="groups-grid">
                                    <?php foreach ($groups_results as $group): ?>
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
                                                <h4><?php echo htmlspecialchars($group['name']); ?></h4>
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
                                                    <span>by <?php echo htmlspecialchars($group['owner_name'] ?: $group['owner_username']); ?></span>
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
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($users_results)): ?>
                            <div class="results-section">
                                <h3>Travelers (<?php echo count($users_results); ?>)</h3>
                                <div class="users-grid">
                                    <?php foreach ($users_results as $user): ?>
                                        <div class="user-card">
                                            <div class="user-avatar">
                                                <img src="assets/images/<?php echo $user['profile_picture']; ?>" 
                                                     alt="<?php echo htmlspecialchars($user['display_name'] ?: $user['username']); ?>">
                                            </div>
                                            <div class="user-info">
                                                <h4><?php echo htmlspecialchars($user['display_name'] ?: $user['username']); ?></h4>
                                                <p class="username">@<?php echo htmlspecialchars($user['username']); ?></p>
                                                <?php if ($user['location']): ?>
                                                    <p class="location">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <?php echo htmlspecialchars($user['location']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($user['bio']): ?>
                                                    <p class="bio"><?php echo htmlspecialchars(substr($user['bio'], 0, 100)) . '...'; ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="search-suggestions">
                    <h3>Popular Searches</h3>
                    <div class="suggestions-grid">
                        <a href="?q=Europe&type=groups" class="suggestion-card">
                            <i class="fas fa-map"></i>
                            <span>Europe Travel</span>
                        </a>
                        <a href="?q=backpacking&type=groups" class="suggestion-card">
                            <i class="fas fa-hiking"></i>
                            <span>Backpacking</span>
                        </a>
                        <a href="?q=Asia&type=groups" class="suggestion-card">
                            <i class="fas fa-globe-asia"></i>
                            <span>Asia Adventures</span>
                        </a>
                        <a href="?q=photography&type=groups" class="suggestion-card">
                            <i class="fas fa-camera"></i>
                            <span>Photography Tours</span>
                        </a>
                        <a href="?q=solo&type=groups" class="suggestion-card">
                            <i class="fas fa-user"></i>
                            <span>Solo Travel</span>
                        </a>
                        <a href="?q=luxury&type=groups" class="suggestion-card">
                            <i class="fas fa-gem"></i>
                            <span>Luxury Travel</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
.search-section {
    padding: 4rem 0;
    background: var(--bg-secondary);
    min-height: calc(100vh - 160px);
}

.search-header {
    text-align: center;
    margin-bottom: 3rem;
}

.search-header h1 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.search-header p {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

.search-container {
    max-width: 800px;
    margin: 0 auto 3rem;
}

.search-form {
    background: var(--bg-primary);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
}

.search-input-group {
    display: flex;
    margin-bottom: 1.5rem;
}

.search-input {
    flex: 1;
    padding: 1rem 1.5rem;
    border: 2px solid var(--border-color);
    border-right: none;
    border-radius: var(--border-radius) 0 0 var(--border-radius);
    font-size: 1.1rem;
    font-family: var(--font-family);
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.search-button {
    padding: 1rem 1.5rem;
    background: var(--primary-color);
    color: var(--text-light);
    border: 2px solid var(--primary-color);
    border-radius: 0 var(--border-radius) var(--border-radius) 0;
    cursor: pointer;
    transition: var(--transition);
}

.search-button:hover {
    background: var(--primary-light);
    border-color: var(--primary-light);
}

.search-filters {
    display: flex;
    gap: 2rem;
}

.filter-group label {
    font-weight: 500;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    display: block;
}

.radio-group {
    display: flex;
    gap: 1rem;
}

.radio-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-weight: normal;
    margin-bottom: 0;
}

.radio-option input[type="radio"] {
    margin: 0;
}

.results-header {
    margin-bottom: 2rem;
}

.results-header h2 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.results-header p {
    color: var(--text-secondary);
}

.no-results {
    text-align: center;
    background: var(--bg-primary);
    padding: 4rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-sm);
}

.no-results i {
    font-size: 4rem;
    color: var(--text-muted);
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.no-results h3 {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.no-results p {
    color: var(--text-muted);
    margin-bottom: 2rem;
}

.results-section {
    margin-bottom: 3rem;
}

.results-section h3 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border-color);
}

.groups-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.user-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    text-align: center;
    transition: var(--transition);
}

.user-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.user-avatar {
    margin-bottom: 1rem;
}

.user-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--border-color);
}

.user-info h4 {
    color: var(--primary-color);
    margin-bottom: 0.25rem;
}

.user-info .username {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.user-info .location {
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}

.user-info .bio {
    color: var(--text-secondary);
    font-size: 0.9rem;
    line-height: 1.4;
}

.search-suggestions {
    max-width: 800px;
    margin: 0 auto;
}

.search-suggestions h3 {
    color: var(--primary-color);
    margin-bottom: 2rem;
    text-align: center;
}

.suggestions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.suggestion-card {
    background: var(--bg-primary);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    text-align: center;
    text-decoration: none;
    color: var(--text-secondary);
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.suggestion-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
    color: var(--primary-color);
}

.suggestion-card i {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: var(--primary-color);
}

.suggestion-card span {
    display: block;
    font-weight: 500;
}

@media (max-width: 768px) {
    .search-input-group {
        flex-direction: column;
    }
    
    .search-input {
        border-radius: var(--border-radius);
        border-right: 2px solid var(--border-color);
        margin-bottom: 1rem;
    }
    
    .search-button {
        border-radius: var(--border-radius);
    }
    
    .radio-group {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .groups-grid,
    .users-grid {
        grid-template-columns: 1fr;
    }
    
    .suggestions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
// Auto-submit form when radio buttons change
document.addEventListener('DOMContentLoaded', function() {
    const radioButtons = document.querySelectorAll('input[name="type"]');
    const searchInput = document.querySelector('.search-input');
    
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            if (searchInput.value.trim()) {
                document.querySelector('.search-form').submit();
            }
        });
    });
    
    // Focus search input
    if (searchInput) {
        searchInput.focus();
    }
});
</script>

<?php include 'includes/footer.php'; ?>