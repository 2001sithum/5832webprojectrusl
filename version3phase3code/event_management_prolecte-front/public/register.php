<?php
declare(strict_types=1);
require_once '../includes/database.php'; // Handles session start
require_once '../includes/functions.php';

// Prevent logged-in users from accessing the registration page
if (isAuthenticated()) {
    redirect(isAdmin() ? 'admin.php' : 'dashboard.php');
}

$pageTitle = "Register";
// Variables to hold submitted values for sticky form fields
$inputUsername = '';
// Don't store passwords for sticky fields for security

$errors = []; // Store validation errors (field => message)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Retrieve and Trim Input ---
    $inputUsername = trim($_POST['username'] ?? '');
    $inputPassword = $_POST['password'] ?? ''; // Don't trim passwords initially
    $inputPasswordConfirm = $_POST['password_confirm'] ?? '';
    // For security, role should NOT be set by users during public registration
    $inputRole = 'user'; // Force role to 'user'

    // --- CSRF Token Validation ---
     $submittedToken = $_POST['csrf_token'] ?? '';
     if (!validateCsrfToken($submittedToken)) {
         $errors['csrf'] = 'Invalid request origin. Please try submitting the form again.';
         error_log("CSRF token mismatch for registration attempt.");
     }

    // --- Server-Side Validation ---
    // Username validation
    if (empty($inputUsername)) {
        $errors['username'] = 'Username is required.';
    } elseif (strlen($inputUsername) < 3 || strlen($inputUsername) > 50) {
        $errors['username'] = 'Username must be between 3 and 50 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $inputUsername)) { // Allow letters, numbers, underscore
        $errors['username'] = 'Username can only contain letters, numbers, and underscores.';
    }

    // Password validation
    if (empty($inputPassword)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($inputPassword) < 8) {
        // Simple length check - add more complexity rules in a real app
        // E.g., preg_match('/[A-Z]/', $inputPassword) for uppercase, etc.
        $errors['password'] = 'Password must be at least 8 characters long.';
    }

    // Password confirmation validation
    if (empty($inputPasswordConfirm)) {
         $errors['password_confirm'] = 'Please confirm your password.';
    } elseif ($inputPassword !== $inputPasswordConfirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    // --- Process Registration if Validation Passes ---
    if (empty($errors)) {
        try {
            $db->beginTransaction(); // Use transaction for checking existence and inserting

            // Check if username already exists (case-insensitive check recommended)
            // Using LOWER() function for cross-database compatibility
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(:username) LIMIT 1");
            $stmtCheck->bindParam(':username', $inputUsername, PDO::PARAM_STR);
            $stmtCheck->execute();

            if ($stmtCheck->fetch()) {
                $errors['username'] = 'This username is already taken. Please choose another.';
                $db->rollBack(); // Rollback as the check failed
            } else {
                // --- Hash the Password ---
                // Use default BCRYPT algorithm (or ARGON2 if available and preferred)
                $hashedPassword = password_hash($inputPassword, PASSWORD_DEFAULT);
                if ($hashedPassword === false) {
                     // Handle potential hashing failure (rare)
                     throw new Exception("Password hashing failed unexpectedly.");
                }

                // --- Insert New User ---
                $stmtInsert = $db->prepare("INSERT INTO users (username, password, role, created_at) VALUES (:username, :password, :role, datetime('now'))");
                 // Adjust datetime('now') for MySQL: NOW()
                 if (DB_TYPE === 'mysql') {
                    $stmtInsert = $db->prepare("INSERT INTO users (username, password, role, created_at) VALUES (:username, :password, :role, NOW())");
                 }

                $stmtInsert->bindParam(':username', $inputUsername, PDO::PARAM_STR);
                $stmtInsert->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
                $stmtInsert->bindParam(':role', $inputRole, PDO::PARAM_STR);

                $success = $stmtInsert->execute();

                if ($success) {
                    $db->commit(); // Commit transaction
                    unsetCsrfToken(); // Invalidate token after successful action
                    setFlashMessage('success', 'Registration successful! You can now log in.');
                    redirect('login.php');
                } else {
                    $db->rollBack(); // Rollback if insert failed
                    $errors['db'] = 'Registration failed due to a database issue. Please try again.';
                    error_log("Registration INSERT failed without PDO exception for username: {$inputUsername}");
                }
            } // End else block (username not taken)

        } catch (PDOException $e) {
             if ($db->inTransaction()) $db->rollBack();
             error_log("Registration PDO Error: User [{$inputUsername}] - " . $e->getMessage());
             $errors['db'] = 'A database error occurred during registration. Please try again later.';
        } catch (Exception $e) {
             if ($db->inTransaction()) $db->rollBack();
             error_log("Registration General Error: User [{$inputUsername}] - " . $e->getMessage());
             $errors['general'] = 'An unexpected error occurred: ' . $e->getMessage();
        }
    } // End empty($errors) check

    // --- Display Errors if Any Occurred ---
    if (!empty($errors)) {
         // Combine errors into a single flash message or handle individually
         $errorMsg = "Please correct the following errors: " . implode(' ', array_values($errors));
         setFlashMessage('error', $errorMsg);
         // Regenerate CSRF token for the next attempt if validation failed
         generateCsrfToken();
    }

} // End of POST request handling

// Generate CSRF token for the form display if not generated above
$csrfToken = generateCsrfToken();

// --- Render Page ---
renderTemplate('header', ['pageTitle' => $pageTitle]);
?>

<div class="form-container auth-form">
    <h2 class="form-title"><i class="fas fa-user-plus" aria-hidden="true"></i> <?php echo escape($pageTitle); ?></h2>

    <?php // Flash messages rendered globally in header.php ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo escape($csrfToken); ?>">

        <div class="form-group">
            <label for="username">Username <span class="required">*</span></label>
            <input type="text" name="username" id="username" placeholder="Letters, numbers, _ (3-50 chars)"
                   value="<?php echo escape($inputUsername); ?>" required class="form-input <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                   minlength="3" maxlength="50" pattern="^[a-zA-Z0-9_]+$" aria-required="true"
                   aria-describedby="usernameHelp usernameError">
            <small id="usernameHelp" class="form-text text-muted">Only letters, numbers, and underscore allowed.</small>
            <?php if (isset($errors['username'])): ?>
                <span id="usernameError" class="invalid-feedback"><?php echo escape($errors['username']); ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Password <span class="required">*</span></label>
            <input type="password" name="password" id="password" placeholder="Minimum 8 characters" required
                   class="form-input <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" minlength="8" aria-required="true" aria-describedby="passwordHelp passwordError">
             <small id="passwordHelp" class="form-text text-muted">Must be at least 8 characters long.</small>
             <?php /* Add hints for complexity if needed in a real app */ ?>
            <?php if (isset($errors['password'])): ?>
                <span id="passwordError" class="invalid-feedback"><?php echo escape($errors['password']); ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirm Password <span class="required">*</span></label>
            <input type="password" name="password_confirm" id="password_confirm" placeholder="Re-enter password" required
                   class="form-input <?php echo isset($errors['password_confirm']) ? 'is-invalid' : ''; ?>" minlength="8" aria-required="true" aria-describedby="passwordConfirmError">
             <?php if (isset($errors['password_confirm'])): ?>
                <span id="passwordConfirmError" class="invalid-feedback"><?php echo escape($errors['password_confirm']); ?></span>
            <?php endif; ?>
        </div>

        <?php /* Role selection is hidden from public registration for security */ ?>
        <input type="hidden" name="role" value="user">

        <button type="submit" class="form-button">
            <i class="fas fa-user-plus" aria-hidden="true"></i> Register
        </button>

         <p class="required-note mt-2"><small><span class="required">*</span> Required fields</small></p>
    </form>

    <p class="form-link mt-3 text-center">Already have an account? <a href="<?php echo baseUrl('login.php'); ?>">Login here</a>.</p>
</div>

<?php
renderTemplate('footer');
?>
