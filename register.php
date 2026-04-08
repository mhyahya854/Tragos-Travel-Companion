<?php
$page_title = 'Sign Up';
include 'includes/header.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $display_name = sanitizeInput($_POST['display_name']);
    
    // Validation
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username must be at least 3 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Username can only contain letters, numbers, and underscores';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($display_name)) {
        $errors['display_name'] = 'Display name is required';
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $errors['general'] = 'Username or email already exists';
        }
    }
    
    // Create user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (username, email, password, display_name) VALUES (:username, :email, :password, :display_name)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':username', $username);
        $insert_stmt->bindParam(':email', $email);
        $insert_stmt->bindParam(':password', $hashed_password);
        $insert_stmt->bindParam(':display_name', $display_name);
        
        if ($insert_stmt->execute()) {
            $success = 'Account created successfully! You can now log in.';
        } else {
            $errors['general'] = 'Failed to create account. Please try again.';
        }
    }
}
?>

<main class="main-content">
    <section class="auth-section">
        <div class="container">
            <div class="form-container">
                <div class="auth-header">
                    <h2>Join TRAGOS</h2>
                    <p>Create your account and start connecting with fellow travelers</p>
                </div>
                
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-error">
                        <?php echo $errors['general']; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                        <a href="login.php" class="btn btn-primary mt-2">Login Now</a>
                    </div>
                <?php else: ?>
                
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control <?php echo isset($errors['username']) ? 'error' : ''; ?>" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        <?php if (isset($errors['username'])): ?>
                            <div class="error-message"><?php echo $errors['username']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control <?php echo isset($errors['email']) ? 'error' : ''; ?>" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error-message"><?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="display_name">Display Name</label>
                        <input type="text" id="display_name" name="display_name" class="form-control <?php echo isset($errors['display_name']) ? 'error' : ''; ?>" 
                               value="<?php echo isset($_POST['display_name']) ? htmlspecialchars($_POST['display_name']) : ''; ?>" required>
                        <?php if (isset($errors['display_name'])): ?>
                            <div class="error-message"><?php echo $errors['display_name']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control <?php echo isset($errors['password']) ? 'error' : ''; ?>" required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="error-message"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="error-message"><?php echo $errors['confirm_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
                
                <?php endif; ?>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Sign in here</a></p>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.auth-section {
    padding: 6rem 0;
    background: var(--bg-secondary);
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-header h2 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.auth-header p {
    color: var(--text-secondary);
}

.auth-form {
    margin-bottom: 2rem;
}

.auth-footer {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.auth-footer a {
    color: var(--primary-color);
    font-weight: 500;
}
.auth-footer a:hover {
    text-decoration: underline;
}
.alert {
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1.5rem;
}

.alert-error {
    background: rgba(220, 20, 60, 0.1);
    border: 1px solid rgba(220, 20, 60, 0.3);
    color: var(--error-color);
}

.alert-success {
    background: rgba(34, 139, 34, 0.1);
    border: 1px solid rgba(34, 139, 34, 0.3);
    color: var(--success-color);
}
.btn i {
    margin-right: 0.5rem;
}
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius);
    font-weight: 500;
    transition: background 0.3s ease;
}
</style>

<?php include 'includes/footer.php'; ?>