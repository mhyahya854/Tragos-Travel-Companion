<?php
$page_title = 'Privacy Policy';
include 'includes/header.php';
?>

<main class="main-content">
    <div class="page-container">
        <section class="page-hero bg-primary">
            <div class="container text-center py-5">
                <h1 class="display-4 fw-bold">Privacy Policy</h1>
                <p class="last-updated mt-3">Last Updated: January 2025</p>
            </div>
        </section>
        
        <section class="content-section py-5">
            <div class="container">
                <div class="legal-content">
                    <article class="content-card one-card">
                        <h2 class="section-heading">1. Information Collection</h2>
                        <p class="section-description">At TRAGOS, we collect the following information when you:</p>
                        <ul class="feature-list">
                            <li>Register an account (including username, email address, and encrypted password)</li>
                            <li>Complete your user profile (optional biographical information, location, and profile image)</li>
                            <li>Engage with our platform services (communication data and group participation records)</li>
                        </ul>
                    </article>
                    
                    <article class="content-card">
                        <h2 class="section-heading">2. Information Usage</h2>
                        <p class="section-description">Your information enables us to:</p>
                        <ul class="feature-list">
                            <li>Deliver and enhance our platform services</li>
                            <li>Facilitate connections between fellow travelers</li>
                            <li>Maintain platform security and user safety</li>
                            <li>Deliver essential service notifications and updates</li>
                        </ul>
                    </article>
                    
                    <article class="content-card">
                        <h2 class="section-heading">3. Information Disclosure</h2>
                        <p class="section-description">We share information in the following circumstances:</p>
                        <ul class="feature-list">
                            <li>With other platform users according to your privacy preferences</li>
                            <li>With authorized service providers supporting TRAGOS operations</li>
                            <li>When mandated by legal requirements or regulations</li>
                        </ul>
                        <p class="privacy-guarantee">We maintain a strict policy against selling personal information to third parties.</p>
                    </article>
                    
                    <article class="content-card">
                        <h2 class="section-heading">4. Security Measures</h2>
                        <p class="section-description">We employ industry-standard security protocols, including advanced encryption and access control systems, to safeguard your information. While we implement comprehensive security measures, please note that no digital system can guarantee absolute security.</p>
                    </article>
                    
                    <article class="content-card">
                        <h2 class="section-heading">5. User Rights</h2>
                        <p class="section-description">As a TRAGOS user, you are entitled to:</p>
                        <ul class="feature-list">
                            <li>Request access to your personal data</li>
                            <li>Submit corrections to inaccurate information</li>
                            <li>Request complete data deletion</li>
                            <li>Manage privacy and visibility settings</li>
                        </ul>
                    </article>
                    
                    <article class="content-card">
                        <h2 class="section-heading">6. Contact Information</h2>
                        <p class="section-description">For privacy-related inquiries, please contact our dedicated privacy team at <a href="mailto:privacy@tragos.com" class="contact-link">privacy@tragos.com</a></p>
                    </article>
                </div>
            </div>
        </section>
    </div>
</main>

<style>
.legal-content {
    max-width: 900px;
    margin: 0 auto;
    font-family: 'Inter', sans-serif;
}
.py-5 h1 {
    color: var(--primary-color);
    font-weight: 700;
}

.content-card {
    background: var(--bg-primary);
    border-radius: 12px;
    padding: 2.5rem;
    margin-bottom: 2.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease;
}

.content-card:hover {
    transform: translateY(-2px);
}

.section-heading {
    color: var(--primary-color);
    font-size: 1.75rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 0.5rem;
}

.section-description {
    color: var(--text-secondary);
    line-height: 1.8;
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
}

.feature-list {
    color: var(--text-secondary);
    line-height: 1.8;
    margin-bottom: 1.5rem;
    padding-left: 2.5rem;
}

.feature-list li {
    margin-bottom: 0.75rem;
}

.contact-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s ease;
}

.contact-link:hover {
    color: var(--primary-dark);
    text-decoration: underline;
}

.last-updated {
    color: var(--text-muted);
    font-style: italic;
    font-size: 1.1rem;
}

.privacy-guarantee {
    color: var(--text-secondary);
    font-weight: 500;
    padding: 1rem;
    background: var(--bg-secondary);
    border-radius: 8px;
    margin-top: 1.5rem;
}

.bg-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
}
</style>

<?php include 'includes/footer.php'; ?>
