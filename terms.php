<?php
$page_title = 'Terms of Service';
include 'includes/header.php';
?>

<main class="main-content">
    <div class="page-container">
        <section class="page-hero">
            <div class="container head">
                <h1>Terms of Service</h1>
                <p class="last-updated">Last updated: January 2025</p>
            </div>
        </section>
        
        <section class="content-section">
            <div class="container">
                <div class="legal-content">
                    <div class="content-card">
                        <h2>1. Acceptance of Terms</h2>
                        <p>By using TRAGOS, you agree to these Terms of Service. If you don't agree, please don't use our service.</p>
                    </div>
                    
                    <div class="content-card">
                        <h2>2. Service Description</h2>
                        <p>TRAGOS is a travel companion platform that connects travelers with similar interests. We provide tools for creating profiles, joining groups, and communicating with other travelers.</p>
                    </div>
                    
                    <div class="content-card">
                        <h2>3. User Accounts</h2>
                        <p>You must be at least 18 years old to use TRAGOS. You're responsible for:</p>
                        <ul>
                            <li>Providing accurate information</li>
                            <li>Keeping your password secure</li>
                            <li>All activities under your account</li>
                        </ul>
                    </div>
                    
                    <div class="content-card">
                        <h2>4. User Conduct</h2>
                        <p>You agree not to:</p>
                        <ul>
                            <li>Use the service for illegal purposes</li>
                            <li>Harass or harm other users</li>
                            <li>Post false or misleading information</li>
                            <li>Spam or send unsolicited messages</li>
                        </ul>
                    </div>
                    
                    <div class="content-card">
                        <h2>5. Safety & Responsibility</h2>
                        <p>While we provide tools to help users connect safely, you are responsible for your own safety when meeting or traveling with other users. Always meet in public places and trust your instincts.</p>
                    </div>
                    
                    <div class="content-card">
                        <h2>6. Limitation of Liability</h2>
                        <p>TRAGOS is provided "as is" without warranties. We are not liable for any damages arising from your use of the service or interactions with other users.</p>
                    </div>
                    
                    <div class="content-card">
                        <h2>7. Contact Information</h2>
                        <p>For questions about these Terms, contact us at <a href="mailto:legal@tragos.com">legal@tragos.com</a></p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<style>
.legal-content {
    max-width: 800px;
    margin: 0 auto;
}
.head {
    text-align: center;
}

.content-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: var(--shadow-sm);
}

.content-card h2 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.content-card p {
    color: var(--text-secondary);
    line-height: 1.5;
    margin-bottom: 0.5rem;
}

.content-card ul {
    color: var(--text-secondary);
    line-height: 1.5;
    margin-bottom: 0.5rem;
    padding-left: 1.5rem;
}

.content-card a {
    color: var(--primary-color);
    text-decoration: none;
}

.last-updated {
    color: var(--text-muted);
    font-style: italic;
    margin-bottom: 0.5rem;
}

.page-hero {
    padding-bottom: 1rem;
}
</style>

<?php include 'includes/footer.php'; ?>
