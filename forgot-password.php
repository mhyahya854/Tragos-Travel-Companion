<?php
$page_title = 'Forgot Password';
include 'includes/header.php';

// Redirect if already logged in
if ($current_user) {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitizeInput($_POST['email']);
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if email exists
        $user_query = "SELECT id, username FROM users WHERE email = :email";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindParam(':email', $email);
        $user_stmt->execute();
        
        if ($user_stmt->rowCount() > 0) {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token (you would need to create a password_resets table)
            // For now, we'll just show a success message
            $success = 'If an account with that email exists, we\'ve sent you a password reset link.';
        } else {
            // Don't reveal if email doesn't exist for security
            $success = 'If an account with that email exists, we\'ve sent you a password reset link.';
        }
    }
}
?>

<main class="main-content">
    <div class="page-container">
        <section class="auth-section">
            <div class="container">
                <div class="auth-container">
                    <div class="auth-card">
                        <div class="auth-header">
                            <h1>Forgot Password</h1>
                            <p>This Feature is not valid currently. This is just Demo that how this feature works </p>
                        </div>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo $success; ?>
                            </div>
                            <div class="auth-links">
                                <a href="login.php" class="btn btn-primary">Back to Login</a>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="auth-form">
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" 
                                           class="form-control <?php echo isset($errors['email']) ? 'error' : ''; ?>" 
                                           value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                           placeholder="Enter your email address" required>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="error-message"><?php echo $errors['email']; ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-full">
                                    <i class="fas fa-paper-plane"></i> Send Reset Link
                                </button>
                            </form>
                            
                            <div class="auth-links">
                                <a href="login.php">
                                    <i class="fas fa-arrow-left"></i> Back to Login
                                </a>
                                <a href="register.php">Create Account</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<style>


.auth-section {
    background: var(--bg-secondary);
}
.auth-container {
    max-width: 510px;
    margin: 20px auto;
}
.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}
.auth-header h1 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    font-size: 2rem;
}
.auth-header p {
    color: var(--text-secondary);
}
.auth-card {
    background: var(--bg-primary);
    border-radius: var(--border-radius-lg);
    padding: 3rem;
    box-shadow: var(--shadow-lg);
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.btn-full {
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
}

.auth-links {
    display: flex;
    justify-content: center;
    gap: 5rem;
    align-items: center;
    color: var(--text-muted);
    margin-top: 1rem;
}
.auth-links a {
    color: var(--primary-color);
    font-weight: 500;
}
.auth-links a:hover {
    text-decoration: underline;
}

.alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: var(--border-radius-sm);
    font-weight: 500;
}

@media (max-width: 480px) {
    .auth-card {
        padding: 2rem;
        margin: 1rem;
    }
    
    .auth-links {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .separator {
        display: none;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
