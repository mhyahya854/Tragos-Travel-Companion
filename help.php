<?php
$page_title = 'Help & Support';
include 'includes/header.php';
?>

<main class="main-content">
    <div class="help-container">
        <h1>Help Center</h1>
        <p class="help-subtitle">How can we help you today?</p>

        <div class="help-grid">
            <aside class="help-nav">
                <nav>
                    <button class="nav-bton active" onclick="showSection('basics')">
                        <i class="fas fa-star"></i>
                        Basics
                    </button>
                    <button class="nav-bton" onclick="showSection('account')">
                        <i class="fas fa-user"></i>
                        Account
                    </button>
                    <button class="nav-bton" onclick="showSection('groups')">
                        <i class="fas fa-users"></i>
                        Groups
                    </button>
                    <button class="nav-bton" onclick="showSection('safety')">
                        <i class="fas fa-shield-alt"></i>
                        Safety
                    </button>
                </nav>

                <div class="help-contact">
                    <h3>Need more help?</h3>
                    <a href="contact.php" class="contact-btn">Contact Support</a>
                </div>
            </aside>

            <div class="help-content">
                <section id="basics" class="help-section active">
                    <h2>Getting Started</h2>
                    <div class="faq">
                        <details>
                            <summary>How do I create an account?</summary>
                            <div class="faq-content">
                                <p>Click "Sign Up", enter your details, and you're ready to go!</p>
                            </div>
                        </details>
                        <details>
                            <summary>How do I find travel groups?</summary>
                            <div class="faq-content">
                                <p>Browse the Groups page or use search to find your perfect travel match.</p>
                            </div>
                        </details>
                    </div>
                </section>

                <section id="account" class="help-section">
                    <h2>Account Settings</h2>
                    <div class="faq">
                        <details>
                            <summary>How do I edit my profile?</summary>
                            <div class="faq-content">
                                <p>Visit your profile and click "Edit" to update your information.</p>
                            </div>
                        </details>
                    </div>
                </section>

                <section id="groups" class="help-section">
                    <h2>Travel Groups</h2>
                    <div class="faq">
                        <details>
                            <summary>How do I create a group?</summary>
                            <div class="faq-content">
                                <p>Click "Create Group" and follow the simple setup process.</p>
                            </div>
                        </details>
                    </div>
                </section>

                <section id="safety" class="help-section">
                    <h2>Safety Tips</h2>
                    <div class="faq">
                        <details>
                            <summary>Travel Safety Guidelines</summary>
                            <div class="faq-content">
                                <ul>
                                    <li>Meet in public places</li>
                                    <li>Share travel plans with friends</li>
                                    <li>Keep documents secure</li>
                                </ul>
                            </div>
                        </details>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<style>

.help-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.help-container h1 {
    font-size: 2.5rem;
    text-align: center;
    color: var(--primary-color);
}

.help-subtitle {
    text-align: center;
    color: var(--text-secondary);
    margin-bottom: 3rem;
}

.help-grid {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 2rem;
}

.help-nav {
    margin-bottom: 3.5rem;
    top: 2rem;
    height: fit-content;
}
.help-nav i {
    font-size: 1.25rem;
    margin-right: 0.5rem;
    margin-bottom: 0.25rem;

}

.help-nav nav {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 2rem;
}

.nav-bton {
    padding: 0.75rem 1rem;
    border: none;
    border-radius: 6px;
    background: var(--bg-secondary);
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.nav-bton:hover,
.nav-bton.active {
    background: var(--primary-color);
    color: white;
}


.help-contact {
    background: var(--bg-secondary);
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
}

.contact-btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: var(--primary-color);
    color: white;
    border-radius: 6px;
    text-decoration: none;
    margin-top: 1rem;
    transition: opacity 0.3s;
}

.contact-btn:hover {
    opacity: 0.9;
}

.help-section {
    display: none;
}

.help-section.active {
    display: block;
}

.help-section h2 {
    margin-bottom: 2rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border-color);
}

.faq details {
    margin-bottom: 1rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
}

.faq summary {
    padding: 1rem;
    cursor: pointer;
    font-weight: 500;
    background: var(--bg-secondary);
    border-radius: 8px;
}

.faq summary:hover {
    background: var(--primary-color);
    color: white;
}

.faq-content {
    padding: 1rem;
    color: var(--text-secondary);
}

.faq-content ul {
    list-style: disc;
    margin-left: 1.5rem;
}

@media (max-width: 768px) {
    .help-grid {
        grid-template-columns: 1fr;
    }

    .help-nav {
        position: static;
    }

    .help-nav nav {
        flex-direction: row;
        flex-wrap: wrap;
    }

    .nav-bton {
        flex: 1;
        min-width: 150px;
    }
}
</style>

<script>
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.help-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.nav-bton').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(sectionId).classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>
