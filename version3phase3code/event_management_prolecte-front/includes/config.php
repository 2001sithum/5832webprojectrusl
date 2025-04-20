<?php
/**
 * Application Configuration
 *
 * Database settings, paths, session details, etc.
 */
declare(strict_types=1);

// Environment Configuration (Development or Production)
// In a real app, you might set this based on server environment variable
define('ENVIRONMENT', 'development'); // 'development' or 'production'

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    // Ensure errors are logged in production
    ini_set('log_errors', '1');
    // Set error log path appropriate for your production server
    // Example: ini_set('error_log', '/var/log/php/app_errors.log');
    ini_set('error_log', __DIR__ . '/../data/php_errors.log'); // Log errors to data dir in this demo
}


// --- Database Configuration ---
// !! CHOOSE ONE: 'sqlite' or 'mysql' !!
define('DB_TYPE', 'sqlite');

// SQLite Configuration (Only used if DB_TYPE is 'sqlite')
// Make sure the 'data' directory is writable by the web server!
define('SQLITE_PATH', __DIR__ . '/../data/event_management.db'); // Path to SQLite file

// MySQL Configuration (Only used if DB_TYPE is 'mysql')
define('DB_HOST', '127.0.0.1'); // Use 127.0.0.1 instead of localhost for potential consistency
define('DB_NAME', 'event_mgmt_pro_db'); // Database name - !! CREATE THIS DATABASE MANUALLY !!
define('DB_USER', 'root'); // Database username - !! CHANGE THIS !!
define('DB_PASS', 'password'); // Database password - !! CHANGE THIS TO A STRONG PASSWORD !!
define('DB_CHARSET', 'utf8mb4');


// --- Application Settings ---
define('APP_NAME', 'Event Ticket Pro');

// Base URL: Must end with a forward slash '/'.
// Detect automatically or set manually. Manual is often more reliable.
// Example: define('BASE_URL', 'http://localhost/event_management_pro/public/');
// Auto-detection (use with caution, ensure web server config is correct)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
// Basic detection of subdirectory - might need adjustment for complex setups
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$subdir = str_replace('/index.php', '', $script_name); // Assuming index.php is the entry point in public/
$subdir = rtrim($subdir, '/');
// Make sure it points to the public directory!
// If the project root is the web root, $subdir might be empty.
// If project is in /var/www/html/event_management_pro, and web root is /var/www/html,
// BASE_URL should be http://host/event_management_pro/public/
// The auto-detection might need manual adjustment if web root != project root
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/public/') !== false) {
     $subdir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
} elseif ($subdir) {
    $subdir .= '/public'; // Append /public if detected subdir doesn't include it
} else {
     $subdir = '/public'; // Assume /public if no subdir detected
}
define('BASE_URL', $protocol . $host . $subdir . '/');


// Default Page Title (if not set in specific page)
define('DEFAULT_PAGE_TITLE', APP_NAME);


// --- Session Settings ---
define('SESSION_NAME', 'EVENTMGMTSESS');
define('SESSION_LIFETIME', 3600); // Session lifetime in seconds (1 hour)
// Set session cookie parameters for security
define('SESSION_COOKIE_SECURE', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')); // True if HTTPS
define('SESSION_COOKIE_HTTPONLY', true); // Prevent JS access to cookie
define('SESSION_COOKIE_SAMESITE', 'Lax'); // Mitigate CSRF ('Lax' or 'Strict')

// --- Other ---
// define('UNSPLASH_ACCESS_KEY', 'YOUR_UNSPLASH_ACCESS_KEY'); // For image API (add key if using)

?>
