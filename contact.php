<?php
$page_title = 'Contact Us';
include 'includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);
    
    if (empty($name)) $errors['name'] = 'Name is required';
    if (empty($email)) $errors['email'] = 'Email is required';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format';
    if (empty($subject)) $errors['subject'] = 'Subject is required';
    if (empty($message)) $errors['message'] = 'Message is required';
    
    if (empty($errors)) {
        $success = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Thank you for your message! We\'ll get back to you within 24 hours.</div>';
        $name = $email = $subject = $message = '';
    }
}
?>

<main class="main-content">
    <div class="page-container">
        <section class="page-hero" style="background-color: var(--bg-secondary);">
            <div class="container">
                <h1>Contact Us</h1>
                <p class="hero-text">We're here to help! Get in touch with our support team</p>
            </div>
        </section>
        
        <section class="content-section" style="background-color: var(--bg-primary);">
            <div class="container">
                <div class="contact-layout">
                    <div class="contact-info">
                        <div class="info-grid">
                            <div class="info-card" style="background-color: var(--bg-secondary);">
                                <div class="info-icon"><i class="fas fa-envelope"></i></div>
                                <h3>Email Support</h3>
                                <p>support@tragos.com</p>
                            </div>
                            <div class="info-card" style="background-color: var(--bg-secondary);">
                                <div class="info-icon"><i class="fas fa-clock"></i></div>
                                <h3>Response Time</h3>
                                <p>Within 24 hours</p>
                            </div>
                            <div class="info-card" style="background-color: var(--bg-secondary);">
                                <div class="info-icon"><i class="fas fa-question-circle"></i></div>
                                <h3>FAQ</h3>
                                <p><a href="help.php">Visit Help Center</a></p>
                            </div>
                            <div class="info-card" style="background-color: var(--bg-secondary);">
                                <div class="info-icon"><i class="fas fa-shield-alt"></i></div>
                                <h3>Report Issues</h3>
                                <p>security@tragos.com</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-form-container" style="background-color: var(--bg-secondary);">
                        <div class="form-card">
                            <h2>Send us a Message</h2>
                            <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="contact-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="name">Name *</label>
                                        <input type="text" id="name" name="name" class="form-control <?php echo isset($errors['name']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                        <?php if (isset($errors['name'])): ?>
                                        <div class="error-message"><?php echo $errors['name']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" id="email" name="email" class="form-control <?php echo isset($errors['email']) ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                        <?php if (isset($errors['email'])): ?>
                                        <div class="error-message"><?php echo $errors['email']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="subject">Subject *</label>
                                    <select id="subject" name="subject" class="form-control <?php echo isset($errors['subject']) ? 'error' : ''; ?>" required>
                                        <option value="">Select a subject</option>
                                        <option value="Account Issues">Account Issues</option>
                                        <option value="Group Problems">Group Problems</option>
                                        <option value="Chat Issues">Chat Issues</option>
                                        <option value="Safety Concerns">Safety Concerns</option>
                                        <option value="Bug Report">Bug Report</option>
                                        <option value="Feature Request">Feature Request</option>
                                        <option value="General Inquiry">General Inquiry</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <?php if (isset($errors['subject'])): ?>
                                    <div class="error-message"><?php echo $errors['subject']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="message">Message *</label>
                                    <textarea id="message" name="message" class="form-control <?php echo isset($errors['message']) ? 'error' : ''; ?>" rows="6" required placeholder="Please provide as much detail as possible..."><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                                    <?php if (isset($errors['message'])): ?>
                                    <div class="error-message"><?php echo $errors['message']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Send Message
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<style>
.contact-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.hero-text {
    margin-bottom: 1rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 0.5rem;
}

.info-card {
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.info-icon {
    width: 50px;
    height: 50px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.75rem;
    color: #fff;
    font-size: 1.25rem;
}

.info-card h3 {
    color: var(--primary-color);
    margin-bottom: 0.25rem;
    font-size: 1.1rem;
}

.info-card p {
    color: var(--text-secondary);
    margin: 0;
    font-size: 0.9rem;
}

.contact-form-container {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-card {
    padding: 1.5rem;
}

.form-card h2 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    text-align: center;
    font-size: 1.5rem;
}

.contact-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-actions {
    text-align: center;
    margin-top: 0.5rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
}

.alert-success {
    background-color: rgb(175, 231, 175);
    padding: 10px;
    border-radius: 5px;
}

@media (max-width: 768px) {
    .contact-layout {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-card {
        padding: 1rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
