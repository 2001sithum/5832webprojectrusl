<?php
// Strict types help catch type errors
declare(strict_types=1);

// Load essentials BUT make sure database connection IS NOT necessarily required for logout
// as it might unnecessarily load DB resources or fail if DB is down.
// We need config for session settings and functions for logoutUser/redirect/flash.
require_once '../includes/config.php';
require_once '../includes/functions.php'; // Needs to be loaded for logoutUser()

// Ensure session is started (required by logoutUser logic to access $_SESSION etc.)
// Use @ to suppress potential "headers already sent" warnings if session_start was called earlier,
// though ideally logout.php should be accessed directly with no prior output.
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Log the logout action *before* destroying session (optional but good practice)
$userId = $_SESSION['user_id'] ?? 'guest'; // Get user ID if available
$username = $_SESSION['username'] ?? 'guest';
error_log("User logout initiated: ID [{$userId}], Username [{$username}]"); // Example log

// Call the centralized logout function from functions.php
logoutUser();

// Provide feedback *after* logout and redirect the user
// Note: setFlashMessage requires a session, but logoutUser technically destroys it.
// We might need to start a new temporary session just for the flash message,
// or rely on the fact that session_destroy() doesn't immediately prevent writing to $_SESSION
// before the script ends and headers are sent. Let's try the simpler approach first.
if (session_status() === PHP_SESSION_NONE) { @session_start(); } // Restart briefly for flash
setFlashMessage('success', 'You have been successfully logged out.');
redirect('login.php'); // Redirect to the login page after logout

// Note: execution stops at redirect()
?>
