<?php
/**
 * Helper Functions Library
 *
 * Common functions used throughout the application.
 * Assumes config.php and database.php have been loaded.
 */
declare(strict_types=1);

// --- URL & Redirection ---

/**
 * Generates an absolute URL relative to the BASE_URL defined in config.php.
 * @param string $path Path relative to the public directory (e.g., 'login.php', 'css/style.css').
 * @return string Absolute URL.
 */
function baseUrl(string $path = ''): string {
     if (!defined('BASE_URL')) {
        error_log('Error: BASE_URL is not defined in config.php');
        // Basic fallback, may not work correctly if app is in subdirectory
        return '/' . ltrim($path, '/');
     }
     // Ensure BASE_URL ends with / and path doesn't start with /
     $baseUrl = rtrim(BASE_URL, '/') . '/';
     $path = ltrim($path, '/');
    return $baseUrl . $path;
}

/**
 * Redirects the browser to a specified URL.
 * Exits the script immediately after sending the header.
 * @param string $url The URL to redirect to (can be relative to BASE_URL or absolute).
 */
function redirect(string $url): void {
    // Prevent header injection attacks
    $url = str_replace(["\r", "\n"], '', $url);

    // If it's not an absolute URL, prepend the BASE_URL
    if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
        $url = baseUrl($url);
    }

    // Ensure session data is saved before redirecting
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // Perform the redirect
    header("Location: " . $url);
    exit(); // Terminate script execution
}

// --- Authentication & Authorization ---

/**
 * Checks if a user is currently logged in.
 * @return bool True if a valid user session exists, false otherwise.
 */
function isAuthenticated(): bool {
    // Ensure session is active before checking
     if (session_status() !== PHP_SESSION_ACTIVE) { return false; }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && is_int($_SESSION['user_id']);
}

/**
 * Checks if the currently logged-in user has the 'admin' role.
 * @return bool True if the logged-in user is an admin, false otherwise.
 */
function isAdmin(): bool {
    return isAuthenticated() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Requires user authentication. Redirects to login page if not authenticated.
 * Can optionally require the user to be an administrator.
 * @param bool $adminOnly If true, requires admin privileges as well. Defaults to false.
 */
function requireAuth(bool $adminOnly = false): void {
    if (!isAuthenticated()) {
        // Store the intended destination to redirect back after login (optional)
        // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'] ?? baseUrl('index.php');
        setFlashMessage('error', 'Please login to access this page.');
        redirect('login.php');
    }
    // If admin is required, but user is not an admin
    if ($adminOnly && !isAdmin()) {
        setFlashMessage('error', 'Access Denied: You do not have permission to view this page.');
        // Redirect non-admins away from admin pages - dashboard or index is usually safe
        redirect('dashboard.php');
    }
}

/**
 * Logs out the current user by destroying the session and relevant cookies.
 */
function logoutUser(): void {
    // Ensure session is started before destroying
    if (session_status() !== PHP_SESSION_ACTIVE) {
       // If somehow inactive, nothing to destroy really
       return;
    }

    // Unset all of the session variables.
    $_SESSION = [];

    // If using session cookies, delete the cookie by setting it to the past.
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session.
    @session_destroy();
}

// --- Output & Escaping ---

/**
 * Escapes HTML special characters in a string for safe output in HTML.
 * @param string|null $string The string to escape. Converts null to empty string.
 * @return string The escaped string.
 */
function escape(?string $string): string {
    // ENT_QUOTES escapes both single and double quotes.
    // ENT_SUBSTITUTE replaces invalid code unit sequences with a Unicode Replacement Character
    // instead of returning an empty string.
    return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Shortcut for escaping and echoing a string.
 * @param string|null $string The string to escape and echo.
 */
function e(?string $string): void {
    echo escape($string);
}

// --- Flash Messages ---

/**
 * Sets a short-lived message (e.g., success or error) stored in the session.
 * @param string $type The type of message (e.g., 'success', 'error', 'info', 'warning'). Used as CSS class.
 * @param string $message The message text.
 */
function setFlashMessage(string $type, string $message): void {
    // Ensure session is active
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Log an error if session isn't active - flash messages won't work
        error_log("Attempted to set flash message ('{$type}') without an active session.");
        return;
    }
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieves and removes the flash message from the session.
 * Should be called exactly once per request where messages might be displayed.
 * @return array|null An array ['type' => string, 'message' => string] or null if no message exists.
 */
function getFlashMessage(): ?array {
    if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['flash_message'])) {
        return null;
    }
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']); // Clear after retrieving
    // Basic validation of retrieved message structure
    if (is_array($message) && isset($message['type']) && isset($message['message'])) {
        return $message;
    }
    return null; // Return null if structure is invalid
}

// --- Template Rendering ---

/**
 * Includes a template file from the 'includes/templates' directory.
 * Extracts the data array into the template's local scope.
 * @param string $templateName The name of the template file (without .php extension).
 * @param array<string, mixed> $data Associative array of data to make available in the template. Keys become variable names.
 */
function renderTemplate(string $templateName, array $data = []): void {
    // Construct the full path to the template file
    // Use DIRECTORY_SEPARATOR for cross-platform compatibility
    $templatePath = __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $templateName . '.php';

    if (file_exists($templatePath)) {
        // Extract makes array keys available as variables ($key => $value becomes $key)
        // EXTR_SKIP prevents overriding existing variables in this scope (like $templatePath)
        extract($data, EXTR_SKIP);

        // Include the template file. Variables from $data are now available inside it.
        // Make $db and helper functions accessible within the template naturally
        // Since database.php defines $db globally and functions.php is loaded, they are already accessible.
        include $templatePath;

    } else {
        // Log the error for the administrator
        error_log("Template Rendering Error: Template not found at '{$templatePath}'");
        // Display error only in development for security
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
             echo "<p style='color:red; border:1px solid red; padding:10px; background:white; font-family:sans-serif;'>Error: Template '<strong>" . htmlspecialchars($templateName) . "</strong>' not found at expected path: " . htmlspecialchars($templatePath) . "</p>";
        } else {
             // In production, maybe show a generic error or just omit the template section
             echo "<!-- Template '{$templateName}' missing -->";
        }
    }
}

// --- Input Validation & Sanitization ---

/**
 * Basic validation for required fields in an array (e.g., $_POST).
 * @param array<string, mixed> $data The input data array.
 * @param list<string> $requiredFields List of field names that are required.
 * @return array<string> An array of error messages for missing fields.
 */
function validateRequiredFields(array $data, array $requiredFields): array {
    $errors = [];
    foreach ($requiredFields as $field) {
        // Check if the key exists and the value is not null and not an empty string after trimming
        if (!isset($data[$field]) || $data[$field] === null || trim((string)$data[$field]) === '') {
            // Capitalize first letter and replace underscores for user-friendly field name
            $fieldName = ucfirst(str_replace('_', ' ', $field));
            $errors[$field] = "{$fieldName} is required."; // Use field name as key for potential specific display
        }
    }
    return $errors;
}

// --- Miscellaneous ---

/**
 * Generates a secure CSRF token and stores it in the session.
 * Regenerates if one doesn't exist.
 * @return string The generated CSRF token.
 */
function generateCsrfToken(): string {
     if (session_status() !== PHP_SESSION_ACTIVE) {
        error_log("Cannot generate CSRF token - session not active.");
        return ''; // Return empty string if session is inactive
     }
     if (empty($_SESSION['csrf_token'])) {
        try {
             $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
             error_log("Failed to generate CSRF token: " . $e->getMessage());
             // Fallback to a less secure method if random_bytes fails (should not happen normally)
             $_SESSION['csrf_token'] = md5(uniqid((string)mt_rand(), true));
        }
     }
     return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token against the one in the session.
 * Uses hash_equals for timing-attack resistance.
 * @param string|null $submittedToken The token submitted via the form. Null if not provided.
 * @return bool True if the token is valid and matches the session token, false otherwise.
 */
function validateCsrfToken(?string $submittedToken): bool {
     if (session_status() !== PHP_SESSION_ACTIVE) {
         error_log("Cannot validate CSRF token - session not active.");
         return false;
     }
     // Check if token exists in session and was submitted
     if (empty($_SESSION['csrf_token']) || empty($submittedToken)) {
         return false;
     }
     // Compare using hash_equals to mitigate timing attacks
     return hash_equals($_SESSION['csrf_token'], $submittedToken);
}

/**
 * Removes the CSRF token from the session (use after successful validation and action completion).
 */
function unsetCsrfToken(): void {
     if (session_status() === PHP_SESSION_ACTIVE) {
         unset($_SESSION['csrf_token']);
     }
}

/**
 * Formats a date string.
 * @param string|null $dateString The date string (e.g., from DB 'YYYY-MM-DD HH:MM:SS').
 * @param string $format The desired PHP date format string.
 * @return string Formatted date or empty string on failure.
 */
function formatDate(?string $dateString, string $format = 'F j, Y'): string {
    if (empty($dateString) || $dateString === '0000-00-00' || $dateString === '0000-00-00 00:00:00') {
        return ''; // Handle empty or zero dates
    }
    try {
        $date = new DateTime($dateString);
        return $date->format($format);
    } catch (Exception $e) {
        error_log("Error formatting date '{$dateString}': " . $e->getMessage());
        return ''; // Return empty on formatting error
    }
}

/**
 * Fetches an image URL from Unsplash API (Conceptual Example - requires key and implementation).
 * THIS IS A PLACEHOLDER AND DOES NOT WORK WITHOUT AN API KEY AND FURTHER CODE.
 * @param string $query Search term (e.g., event category or name).
 * @return string|null URL of an image or null on failure/if disabled.
 */
function getUnsplashImage(string $query): ?string {
    /*
    // --- ACTUAL IMPLEMENTATION REQUIRES MORE ---
    if (!defined('UNSPLASH_ACCESS_KEY') || UNSPLASH_ACCESS_KEY === 'YOUR_UNSPLASH_ACCESS_KEY') {
        // error_log("Unsplash Access Key not configured.");
        return null; // API key not set
    }

    $accessKey = UNSPLASH_ACCESS_KEY;
    $url = "https://api.unsplash.com/search/photos?page=1&per_page=1&query=" . urlencode($query) . "&orientation=landscape";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Client-ID ' . $accessKey
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (isset($data['results'][0]['urls']['regular'])) {
            return $data['results'][0]['urls']['regular']; // Or 'small', 'thumb' etc.
        }
    } else {
        error_log("Unsplash API request failed. HTTP Code: {$httpCode}, Query: {$query}, Response: {$response}");
    }
    */
    // --- Placeholder Return ---
    // Return a default placeholder or null if API is not implemented/used
    // return "https://via.placeholder.com/400x250.png?text=" . urlencode($query); // Placeholder service
    return null;
}

?>
