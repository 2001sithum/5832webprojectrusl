<?php
/**
 * Database Connection and Initial Setup (SQLite Focused)
 */
require_once __DIR__ . '/config.php';

$db = null; // Initialize connection variable

$dsn = 'sqlite:' . DB_PATH;
$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $needs_setup = !file_exists(DB_PATH);
    if ($needs_setup) {
         $dir = dirname(DB_PATH);
        if(!is_dir($dir)) { mkdir($dir, 0775, true); } // Create dir if not exists
        if (!is_writable($dir)) {
             throw new Exception("Database directory is not writable: {$dir}");
        }
    }

    $db = new PDO($dsn, null, null, $pdo_options);
    $db->exec('PRAGMA foreign_keys = ON;'); // Enable Foreign Key support

    if ($needs_setup) {
        // --- Initial Table Creation (run only if DB file doesn't exist) ---
        $db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL, -- Store HASHED passwords only!
                role TEXT NOT NULL DEFAULT 'user' CHECK(role IN ('user', 'admin')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $db->exec("
            CREATE TABLE events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                date TEXT NOT NULL,
                time TEXT NOT NULL,
                location TEXT NOT NULL,
                description TEXT NOT NULL,
                image_url TEXT NULL, -- URL or relative path like 'images/event1.jpg'
                category TEXT NULL,
                capacity INTEGER DEFAULT 100,
                attendees_count INTEGER DEFAULT 0,
                status TEXT DEFAULT 'Upcoming' CHECK(status IN ('Upcoming', 'Ongoing', 'Completed', 'Cancelled')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $db->exec("
            CREATE TABLE rsvps (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                event_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
                UNIQUE (user_id, event_id)
            );
        ");

         // Add default admin user (change password!)
         $adminUser = 'admin';
         $adminPassHash = password_hash('admin123', PASSWORD_BCRYPT); // !! CHANGE DEFAULT ADMIN PASSWORD !!
         $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
         $stmt->execute([$adminUser, $adminPassHash]);

         error_log("Database schema created and default admin user added."); // Log setup
    }

} catch (PDOException | Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    // Don't echo detailed errors in production potentially
    die("Database connection or setup failed. Check error log or permissions. Message: " . $e->getMessage());
}

// Session Start
if (session_status() == PHP_SESSION_NONE) {
     session_name(SESSION_NAME);
     if (!@session_start()) { // Suppress warning if already started
        error_log('Failed to start session.');
     }
}

?>
