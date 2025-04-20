<?php
/**
 * Database Connection and Session Initialization
 *
 * Loads configuration, sets up the session, and establishes a PDO connection.
 */
declare(strict_types=1);

// Include configuration (ensure this runs only once)
require_once __DIR__ . '/config.php';

// --- Session Configuration ---
// Ensure session configuration uses defined constants
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/', // Generally root path is fine
        'domain' => '', // Current domain
        'secure' => SESSION_COOKIE_SECURE,
        'httponly' => SESSION_COOKIE_HTTPONLY,
        'samesite' => SESSION_COOKIE_SAMESITE
    ]);
    // Start session only if headers haven't been sent (basic check)
    if (!headers_sent()) {
         session_start();
    } else {
        // Log error if session couldn't start because headers were sent
        error_log('Session cannot be started - headers already sent.');
    }
}

// --- Database Connection ---
/** @var ?PDO $db Database connection object */
$db = null; // Initialize database connection variable

// PDO Options for consistent behavior and error handling
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays by default
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

try {
    if (DB_TYPE === 'sqlite') {
        $dsn = 'sqlite:' . SQLITE_PATH;
        // Ensure the directory exists and is writable (basic check)
        $dbDir = dirname(SQLITE_PATH);
        if (!is_dir($dbDir)) {
             // Attempt to create if not exists (only in dev)
             if (ENVIRONMENT === 'development') {
                if (!mkdir($dbDir, 0775, true)) { // Use appropriate permissions (775 common)
                    error_log('Error: Failed to create SQLite database directory: ' . $dbDir);
                    throw new Exception('Database directory could not be created.');
                }
                 // Add .htaccess if just created
                if (!file_exists($dbDir . '/.htaccess')) {
                    file_put_contents($dbDir . '/.htaccess', 'Deny from all');
                }
             } else {
                  error_log('Error: SQLite database directory does not exist: ' . $dbDir);
                  throw new Exception('Database directory setup error.');
             }
        }
        if (!is_writable($dbDir)) {
             error_log('Error: SQLite database directory is not writable: ' . $dbDir . ' - Check permissions for the web server user.');
             throw new Exception('Database directory permissions error. Ensure the web server can write to the data directory.');
        }
        $db = new PDO($dsn, null, null, $options);
         // Enable foreign key constraints for SQLite session (important!)
        $db->exec('PRAGMA foreign_keys = ON;');

    } elseif (DB_TYPE === 'mysql') {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $db = new PDO($dsn, DB_USER, DB_PASS, $options);

    } else {
        // Handle unsupported database type
        throw new Exception("Unsupported database type specified in configuration: " . DB_TYPE);
    }

    // Additional check if connection succeeded (though PDO constructor usually throws)
    if ($db === null) {
         throw new Exception("Failed to establish database connection.");
    }

} catch (PDOException $e) {
    // Specific PDO connection error
    error_log("Database Connection Failed: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
    // Display user-friendly message based on environment
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        // Provide more specific hints in development
        $errorMsg = "Database connection failed (PDO): " . $e->getMessage();
        if (DB_TYPE === 'mysql' && $e->getCode() === 1049) { // Unknown database
             $errorMsg .= "\nHint: Did you create the database '" . DB_NAME . "' in MySQL?";
        } elseif (DB_TYPE === 'mysql' && $e->getCode() === 1045) { // Access denied
             $errorMsg .= "\nHint: Check MySQL username ('".DB_USER."') and password in config.php.";
        } elseif (DB_TYPE === 'sqlite' && str_contains($e->getMessage(), 'unable to open database file')) {
             $errorMsg .= "\nHint: Check file/directory permissions for '" . SQLITE_PATH . "'. Web server needs write access.";
        }
        die("<pre>" . htmlspecialchars($errorMsg) . "</pre>");
    } else {
        die("Could not connect to the database. Please check configuration or contact support if the problem persists.");
    }
} catch (Exception $e) {
    // General exceptions (like config errors, permissions)
    error_log("Application Error: " . $e->getMessage());
     if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        die("Application error: " . htmlspecialchars($e->getMessage()));
    } else {
        die("An application error occurred. Please try again later.");
    }
}

// Make $db available globally (common practice in simpler apps, consider Dependency Injection for larger ones)
// Global scope makes it accessible in included page files.
?>
