<?php
declare(strict_types=1);
require_once '../includes/database.php'; // Handles session start
require_once '../includes/functions.php';

// Prevent logged-in users from accessing the login page
if (isAuthenticated()) {
    redirect(isAdmin() ? 'admin.php' : 'dashboard.php');
}

$pageTitle = "Login";
$usernameInput = ''; // Store submitted username for sticky form
$errors = []; // Store potential validation errors (though flash messages are primary)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Basic Input Retrieval ---
    $usernameInput = trim($_POST['username'] ?? '');
    $passwordInput = $_POST['password'] ?? ''; // Don't trim password

    // --- Basic Validation ---
    if (empty($usernameInput)) $errors['username'] = 'Username is required.';
    if (empty($passwordInput)) $errors['password'] = 'Password is required.';

    // --- CSRF Token Validation ---
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($submittedToken)) {
         $errors['csrf'] = 'Invalid request. Please try submitting the form again.';
         error_log("CSRF token mismatch for login attempt.");
    }

    // --- Attempt Login if no basic validation/CSRF errors ---
    if (empty($errors)) {
        try {
            // Fetch user by username (case-insensitive compare depends on DB collation, usually CI by default)
            // Use LOWER() for explicit case-insensitivity if needed: WHERE LOWER(username) = LOWER(:username)
            $stmt = $db->prepare("SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $usernameInput, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch();

            // Verify user exists and password is correct using password_verify
            if ($user && password_verify($passwordInput, $user['password'])) {
                // --- Login Success ---
                unsetCsrfToken(); // Invalidate the used token

                // Regenerate session ID for security (prevents session fixation)
                session_regenerate_id(true);

                // Store essential user data in session
                $_SESSION['user_id'] = (int)$user['id']; // Cast to int
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time(); // Optionally store login time

                // Redirect user based on role
                setFlashMessage('success', 'Login successful! Welcome back, ' . escape($user['username']) . '.');

                 // Check for redirect parameter passed from previous page (e.g., login prompt)
                $redirectUrl = filter_input(INPUT_GET, 'redirect', FILTER_SANITIZE_URL);
                 if ($redirectUrl && str_starts_with($redirectUrl, '/')) { // Basic validation for relative URL
                    // Prepend BASE_URL if needed (or handle relative paths carefully)
                     // This part can be complex, ensure it doesn't create open redirect vulnerabilities
                     // Maybe only allow specific known redirects? For demo, keep simple:
                    // header("Location: " . $redirectUrl); // Directly use if it's already absolute or server-relative
                    // exit();
                    // Simplified: ignore redirect param for now, just go to default dashboard/admin
                     $defaultRedirect = ($_SESSION['role'] === 'admin') ? 'admin.php' : 'dashboard.php';
                     redirect($defaultRedirect);
                 } else {
                    $defaultRedirect = ($_SESSION['role'] === 'admin') ? 'admin.php' : 'dashboard.php';
                    redirect($defaultRedirect);
                 }

            } else {
                // --- Login Failure ---
                // Generic error message for security (don't reveal if username exists)
                setFlashMessage('error', 'Invalid username or password.');
                 error_log("Failed login attempt for username: {$usernameInput}");
                // Regenerate CSRF token after failed attempt to prevent replay
                generateCsrfToken();
            }

        } catch (PDOException $e) {
            // --- Database Error ---
            error_log("Login PDO Error: User [{$usernameInput}] - " . $e->getMessage());
            setFlashMessage('error', 'A database error occurred during login. Please try again later.');
            generateCsrfToken(); // Regenerate token
        } catch (Exception $e) {
             // --- Other Errors ---
             error_log("Login General Error: User [{$usernameInput}] - " . $e->getMessage());
             setFlashMessage('error', 'An unexpected error occurred. Please try again later.');
             generateCsrfToken(); // Regenerate token
        }
    } else {
         // --- Display Validation Errors as Flash Messages ---
         $errorMsg = implode(' ', array_values($errors)); // Combine errors into one message
         setFlashMessage('error', $errorMsg);
         // Regenerate CSRF token if validation failed (like invalid CSRF itself)
         generateCsrfToken();
    }
}

// Generate CSRF token for the form display if not already generated in POST error handling
$csrfToken = generateCsrfToken();

// --- Render Page ---
renderTemplate('header', ['pageTitle' => $pageTitle]);
?>

<div class="form-container auth-form">
    <h2 class="form-title"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> <?php echo escape($pageTitle); ?></h2>

    <?php // Flash messages are now rendered globally in header.php's call to renderTemplate('messages') ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . (!empty($_GET['redirect']) ? '?redirect='.urlencode($_GET['redirect']) : ''); ?>" novalidate>

        <input type="hidden" name="csrf_token" value="<?php echo escape($csrfToken); ?>">

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" placeholder="Enter your username" value="<?php echo escape($usernameInput); ?>" required class="form-input <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" aria-required="true" autofocus>
            <?php if (isset($errors['username'])): ?>
                <span class="invalid-feedback"><?php echo escape($errors['username']); ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required class="form-input <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" aria-required="true">
             <?php if (isset($errors['password'])): ?>
                <span class="invalid-feedback"><?php echo escape($errors['password']); ?></span>
            <?php endif; ?>
             <?php /* Add a "Forgot Password?" link later - requires separate implementation */ ?>
             <?php /* <p class="form-link-small"><a href="<?php echo baseUrl('forgot_password.php'); ?>">Forgot Password?</a></p> */ ?>
        </div>

        <button type="submit" class="form-button">
            <i class="fas fa-sign-in-alt" aria-hidden="true"></i> Login
        </button>
    </form>

     <p class="form-link mt-3 text-center">Don't have an account? <a href="<?php echo baseUrl('register.php'); ?>">Register now</a>.</p>

</div>

<?php
renderTemplate('footer');
?>
