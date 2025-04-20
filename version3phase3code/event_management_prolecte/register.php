<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(isAdmin() ? 'admin/index.php' : 'dashboard.php');
}

$pageTitle = "Register";
$username = ''; // Sticky
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $role = 'user'; // Hardcode role to 'user' for public registration

    // Basic Validation
    if (empty($username)) $errors[] = 'Username is required.';
    elseif (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
    // Add more checks: max length, allowed characters

    if (empty($password)) $errors[] = 'Password is required.';
    elseif (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match.';

    // Check if username exists only if other validation passes
    if (empty($errors)) {
         try {
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmtCheck->execute([$username]);
             if ($stmtCheck->fetch()) {
                 $errors[] = 'Username is already taken. Please choose another.';
             }
         } catch (PDOException $e) {
              error_log("Register Check Error: " . $e->getMessage());
              $errors[] = 'Error checking username availability.';
         }
    }


    if (empty($errors)) {
        // --- Passed Validation ---
        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
             if ($hashedPassword === false) {
                 throw new Exception("Password hashing failed.");
             }

             $stmtInsert = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            if ($stmtInsert->execute([$username, $hashedPassword, $role])) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Registration successful! You can now log in.'];
                 redirect('login.php');
            } else {
                $errors[] = 'Registration failed due to a server issue.';
             }

        } catch (PDOException $e) {
             error_log("Register Insert Error: " . $e->getMessage());
             $errors[] = 'A database error occurred during registration.';
        } catch (Exception $e) {
             error_log("Register Error: " . $e->getMessage());
            $errors[] = 'An unexpected error occurred.';
        }
    }

     // Set flash message for errors if any occurred
     if (!empty($errors)) {
         $_SESSION['message'] = ['type' => 'error', 'text' => implode('<br>', $errors)];
     }

} // End POST handling


includeTemplate('header', ['pageTitle' => $pageTitle]);
?>
    <div class="content-section form-container-wrapper">
        <div class="form-container auth-form">
            <h1 class="form-title">Create Account</h1>
            <?php /* displayMessages(); // Now handled in header */ ?>

            <form method="POST" action="register.php" novalidate>
                <div class="form-group">
                    <label for="username"><i class="fas fa-user-plus"></i> Choose Username <span class="required">*</span></label>
                    <input type="text" name="username" id="username" value="<?php e($username); ?>" required minlength="3" class="form-input">
                     <small class="form-text">Min 3 characters, letters/numbers/_</small>
                 </div>
                 <div class="form-group">
                    <label for="password"><i class="fas fa-key"></i> Create Password <span class="required">*</span></label>
                    <input type="password" name="password" id="password" required minlength="8" class="form-input">
                     <small class="form-text">Min 8 characters</small>
                </div>
                <div class="form-group">
                     <label for="password_confirm"><i class="fas fa-check-double"></i> Confirm Password <span class="required">*</span></label>
                    <input type="password" name="password_confirm" id="password_confirm" required minlength="8" class="form-input">
                 </div>
                 <button type="submit" class="button button-primary full-width"><i class="fas fa-check"></i> Register</button>
             </form>
            <p class="form-footer-link">Already have an account? <a href="login.php">Login here</a>.</p>
         </div>
     </div>
<?php includeTemplate('footer'); ?>
