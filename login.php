<?php
$page_title = 'Login';
include 'includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = sanitizeInput($_POST['login']); // Can be username or email
    $password = $_POST['password'];
    
    if (empty($login)) {
        $errors['login'] = 'Username or email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "SELECT * FROM users WHERE (username = :login OR email = :login) AND is_active = TRUE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':login', $login);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Redirect to intended page or dashboard
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                header('Location: index.php' . $redirect);
                exit();
            } else {
                $errors['general'] = 'Invalid credentials';
            }
        } else {
            $errors['general'] = 'Invalid credentials';
        }
    }
}
?>

<main class="main-content">
    <section class="auth-section">
        <div class="container">
            <div class="form-container">
                <div class="auth-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your TRAGOS account</p>
                </div>
                
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-error">
                        <?php echo $errors['general']; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="login">Username or Email</label>
                        <input type="text" id="login" name="login" class="form-control <?php echo isset($errors['login']) ? 'error' : ''; ?>" 
                               value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>" required>
                        <?php if (isset($errors['login'])): ?>
                            <div class="error-message"><?php echo $errors['login']; ?></div>
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
                        <div class="form-options">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember_me"> Remember me
                            </label>
                            <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Sign up here</a></p>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
.auth-section {
    background: var(--bg-secondary);
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
.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.forgot-link {
    font-size: 0.9rem;
    color: var(--primary-color);
}
.forgot-link:hover {
    text-decoration: underline;
}
.auth-footer {
    margin-top: 1rem;
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}
.auth-footer p {
    margin-bottom: 0.5rem;
}
.auth-footer a {
    color: var(--primary-color);
}
.auth-footer a:hover {
    text-decoration: underline;
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