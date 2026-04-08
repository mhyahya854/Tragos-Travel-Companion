<?php
$page_title = 'Home';
include 'includes/header.php';

// Get featured groups
$database = new Database();
$db = $database->getConnection();

$query = "SELECT g.*, u.display_name as owner_name, u.username as owner_username 
          FROM groups_table g 
          JOIN users u ON g.owner_id = u.id 
          WHERE g.is_active = TRUE AND g.privacy = 'public'
          ORDER BY g.created_at DESC 
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM users WHERE is_active = TRUE) as total_users,
    (SELECT COUNT(*) FROM groups_table WHERE is_active = TRUE) as total_groups,
    (SELECT COUNT(*) FROM group_members) as total_members,
    (SELECT COUNT(DISTINCT destination) FROM groups_table WHERE destination IS NOT NULL) as destinations";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<main class="main-content">
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-background">
            <img src="assets/images/hero-bg.avif" alt="Travel Background" class="hero-bg-image">
            <div class="hero-overlay"></div>
        </div>
        <div class="hero-content">
            <div class="container">
                <div class="hero-text">
                    <h1 class="hero-title">
                        Connect. Travel. <span class="gradient-text">Explore.</span>
                    </h1>
                    <p class="hero-subtitle">
                        Join like-minded travelers, create unforgettable group adventures, and discover the world together. Your next great journey starts here.
                    </p>
                    <div class="hero-actions">
                        <?php if ($current_user): ?>
                            <a href="groups.php" class="btn btn-primary btn-large">
                                <i class="fas fa-search"></i> Explore Groups
                            </a>
                            <a href="create-group.php" class="btn btn-outline btn-large">
                                <i class="fas fa-plus"></i> Create Group
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-primary btn-large">
                                <i class="fas fa-user-plus"></i> Join TRAGOS
                            </a>
                            <a href="groups.php" class="btn btn-outline btn-large">
                                <i class="fas fa-search"></i> Browse Groups
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Active Travelers</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_groups']); ?></h3>
                        <p>Travel Groups</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_members']); ?></h3>
                        <p>Connections Made</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['destinations']); ?></h3>
                        <p>Destinations</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose TRAGOS?</h2>
                <p>Discover what makes our platform the perfect choice for group travel</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-image">
                        <img src="assets/images/feature-chat.png" alt="Connect with Travelers">
                    </div>
                    <div class="feature-content">
                        <h3>Connect with Like-minded Travelers</h3>
                        <p>Find and join groups of travelers who share your interests, budget, and travel style. Build lasting friendships on your journeys.</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-image">
                        <img src="assets/images/feature-discover.png" alt="Discover Destinations">
                    </div>
                    <div class="feature-content">
                        <h3>Discover Amazing Destinations</h3>
                        <p>Explore groups traveling to destinations worldwide. From backpacking adventures to luxury getaways, find your perfect trip.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section">
        <div class="container">
            <div class="section-header">
                <h2>Travel Categories</h2>
                <p>Find groups that match your travel style and interests</p>
            </div>
            <div class="categories-grid">
                <a href="groups.php?category=backpacking" class="category-card">
                    <img src="assets/images/backpacking.png" alt="Backpacking">
                    <div class="category-overlay">
                        <h3>Backpacking</h3>
                        <p>Budget-friendly adventures</p>
                    </div>
                </a>
                <a href="groups.php?category=luxury" class="category-card">
                    <img src="assets/images/luxury.png" alt="Luxury Travel">
                    <div class="category-overlay">
                        <h3>Luxury Travel</h3>
                        <p>Premium experiences</p>
                    </div>
                </a>
                <a href="groups.php?category=adventure" class="category-card">
                    <img src="assets/images/adventure.png" alt="Adventure">
                    <div class="category-overlay">
                        <h3>Adventure</h3>
                        <p>Thrilling expeditions</p>
                    </div>
                </a>
                <a href="groups.php?category=cultural" class="category-card">
                    <img src="assets/images/cultural.png" alt="Cultural">
                    <div class="category-overlay">
                        <h3>Cultural</h3>
                        <p>Immersive experiences</p>
                    </div>
                </a>
                <a href="groups.php?category=food" class="category-card">
                    <img src="assets/images/food.png" alt="Food Tours">
                    <div class="category-overlay">
                        <h3>Food Tours</h3>
                        <p>Culinary adventures</p>
                    </div>
                </a>
                <a href="groups.php?category=photography" class="category-card">
                    <img src="assets/images/photography.png" alt="Photography">
                    <div class="category-overlay">
                        <h3>Photography</h3>
                        <p>Capture memories</p>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Groups Section -->
    <section class="featured-groups-section">
        <div class="container">
            <div class="section-header">
                <h2>Featured Travel Groups</h2>
                <p>Join these popular groups and start your adventure today</p>
            </div>
            <div class="groups-grid">
                <?php 
                $latest_groups = array_slice($featured_groups, 0, 3);
                foreach ($latest_groups as $group): 
                ?>
                <div class="group-card">
                    <div class="group-image">
                        <img src="assets/images/<?php echo $group['group_image'] ?: 'other.png'; ?>" alt="<?php echo htmlspecialchars($group['name']); ?>">
                        <div class="group-category">
                            <span class="category-badge category-<?php echo $group['category']; ?>">
                                <?php echo ucfirst($group['category']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="group-content">
                        <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                        <p class="group-description"><?php echo htmlspecialchars(substr($group['description'], 0, 100)) . '...'; ?></p>
                        <div class="group-meta">
                            <div class="group-destination">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($group['destination']); ?>
                            </div>
                            <div class="group-members">
                                <i class="fas fa-users"></i>
                                <?php echo $group['current_members']; ?> members
                            </div>
                        </div>
                        <div class="group-owner">
                            <span>by <?php echo htmlspecialchars($group['owner_name'] ?: $group['owner_username']); ?></span>
                        </div>
                        <a href="group-details.php?id=<?php echo $group['id']; ?>" class="btn btn-primary btn-small">
                            View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="section-footer">
                <a href="groups.php" class="btn btn-outline">View All Groups</a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <?php if (!$current_user): ?>
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Your Adventure?</h2>
                <p>Join thousands of travelers who have found their perfect travel companions on TRAGOS</p>
                <div class="cta-actions">
                    <a href="register.php" class="btn btn-primary btn-large">
                        <i class="fas fa-user-plus"></i> Create Account
                    </a>
                    <a href="login.php" class="btn btn-outline btn-large">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>