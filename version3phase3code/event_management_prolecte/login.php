<?php
require_once 'includes/db.php'; // Connects, starts session
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/index.php' : 'dashboard.php');
}

$pageTitle = "Login";
$username = ''; // Sticky field value
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic Validation
    if (empty($username)) $errors[] = 'Username is required.';
    if (empty($password)) $errors[] = 'Password is required.';

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                 // Login successful! Regenerate session ID for security
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();

                $_SESSION['message'] = ['type' => 'success', 'text' => 'Welcome back, ' . escape($user['username']) . '!'];
                // Redirect based on role, check intended destination first?
                 $destination = $_SESSION['redirect_url'] ?? (isAdmin() ? 'admin/index.php' : 'dashboard.php');
                 unset($_SESSION['redirect_url']); // Clear stored redirect url
                 redirect($destination);

            } else {
                 $errors[] = 'Invalid username or password.';
                 error_log("Failed login attempt for user: {$username}");
            }
        } catch (PDOException $e) {
             error_log("Login Database Error: " . $e->getMessage());
             $errors[] = 'An error occurred during login. Please try again later.';
        }
    }

     // Transfer errors to flash message system if they occurred
    if (!empty($errors)) {
        $_SESSION['message'] = ['type' => 'error', 'text' => implode('<br>', $errors)];
    }

} // End POST request

includeTemplate('header', ['pageTitle' => $pageTitle]);
?>
    <div class="content-section form-container-wrapper">
        <div class="form-container auth-form">
            <h1 class="form-title">Login</h1>
            <?php /* displayMessages(); // Now handled in header */ ?>

             <form method="POST" action="login.php" novalidate>
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" id="username" value="<?php e($username); ?>" required class="form-input" autocomplete="username">
                 </div>
                 <div class="form-group">
                     <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" id="password" required class="form-input" autocomplete="current-password">
                 </div>
                <?php /* Add Remember me later if needed */ ?>
                 <button type="submit" class="button button-primary full-width"><i class="fas fa-sign-in-alt"></i> Log In</button>
             </form>
            <p class="form-footer-link">Don't have an account? <a href="register.php">Register here</a>.</p>
            <?php /* <p class="form-footer-link"><a href="forgot_password.php">Forgot Password?</a></p> */ ?>
         </div>
    </div>

<?php includeTemplate('footer'); ?>
