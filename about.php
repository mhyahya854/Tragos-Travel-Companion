<?php
$page_title = 'About TRAGOS';
include 'includes/header.php';
?>

<main class="main-content">
    <div class="page-container">
        <section class="about-hero">
            <div class="container">
                <div class="hero-content">
                    <h1>About TRAGOS</h1>
                    <p class="hero-subtitle">Connecting travelers worldwide through shared adventures and meaningful experiences</p>
                </div>
            </div>
        </section>
        
        <section class="content-section">
            <div class="container">
                <div class="content-grid">
                    <div class="content-main">
                        <div class="content-card">
                            <h2>Our Story</h2>
                            <p>TRAGOS was born from a simple idea - travel is better when shared. Founded by passionate travelers who understood the challenges of finding like-minded companions for adventures, we set out to create a platform that would connect people with similar travel dreams and styles.</p>
                            <p>Our mission is to make travel more accessible, enjoyable, and meaningful by connecting travelers with shared interests and creating a global community of explorers. </p>
                            <p>Since our launch, we've helped thousands of travelers connect, form lasting friendships, and create unforgettable memories across the globe. Our community spans every continent and includes travelers of all ages, backgrounds, and experience levels.</p>
                        </div>
                        
                        <div class="content-card">
                            <h2>What Makes TRAGOS Special</h2>
                            <div class="features-grid">
                                <div class="feature-item">
                                    <i class="fas fa-search"></i>
                                    <h4>Smart Matching</h4>
                                    <p>Advanced filtering system to find travelers who match your style and preferences.</p>
                                </div>
                                
                                <div class="feature-item">
                                    <i class="fas fa-shield-alt"></i>
                                    <h4>Safety First</h4>
                                    <p>Verified profiles, secure messaging, and safety guidelines for confident connections.</p>
                                </div>
                                
                                <div class="feature-item">
                                    <i class="fas fa-comments"></i>
                                    <h4>Real-time Chat</h4>
                                    <p>Seamless group communication tools for planning and coordination.</p>
                                </div>
                                
                                <div class="feature-item">
                                    <i class="fas fa-globe"></i>
                                    <h4>Global Community</h4>
                                    <p>Connect with travelers worldwide and discover destinations through local insights.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-sidebar">
                        <div class="stats-card">
                            <h3>TRAGOS by Numbers</h3>
                            <div class="stats-list">
                                <div class="stat-item">
                                    <span class="stat-number">10,000+</span>
                                    <span class="stat-label">Active Travelers</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">1,200+</span>
                                    <span class="stat-label">Groups Created</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">50,000+</span>
                                    <span class="stat-label">Msg Exchange</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">100+</span>
                                    <span class="stat-label">Countries Covered</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="cta-card">
                            <div class="cta-labels">
                                <i class="fas fa-users"></i>
                                <i class="fas fa-globe-americas"></i>
                                <i class="fas fa-route"></i>
                            </div>
                            <h3>Ready to Start?</h3>
                            <p>Join thousands of travelers and find your perfect travel companions today.</p>
                            <?php if (!$current_user): ?>
                                <a href="register.php" class="btn btn-primary">Join TRAGOS</a>
                            <?php else: ?>
                                <a href="groups.php" class="btn btn-primary">Browse Groups</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<style>
.page-container {
    min-height: calc(100vh - 70px);
    background: var(--bg-secondary);
}

.about-hero {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    color: var(--text-light);
    padding: 4rem 0;
    text-align: center;
}

.hero-content h1 {
    font-size: 3rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.hero-subtitle {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

.content-section {
    padding: 4rem 0;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 3rem;
}

.content-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
}

.content-card h2 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
}

.content-card p {
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.mission-grid {
    display: grid;
    gap: 2rem;
}

.mission-item {
    text-align: center;
    padding: 1.5rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.mission-icon {
    width: 60px;
    height: 60px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: var(--text-light);
    font-size: 1.5rem;
}

.mission-item h3 {
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.feature-item {
    text-align: center;
    padding: 1.5rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.feature-item i {
    font-size: 2rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.feature-item h4 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.feature-item p {
    font-size: 0.9rem;
    margin: 0;
}

.content-sidebar {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.stats-card,
.cta-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-sm);
}

.stats-card h3,
.cta-card h3 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    text-align: center;
    font-size: 2.1rem;
}

.cta-labels {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
    font-size: 1.5rem;    
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
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: var(--border-radius);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.stat-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.cta-card {
    text-align: center;
}

.cta-card p {
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
}

@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 2rem;
    }

    .content-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .features-grid {
        grid-template-columns: 1fr;
    }

    .content-card {
        padding: 1.5rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
