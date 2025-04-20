<?php
/**
 * Database Initialization Script
 *
 * Creates the necessary tables for the Event Ticket Management Pro application.
 * Reads configuration from includes/config.php.
 * Should be run from the command line via scripts/setup.sh.
 * Generates a mysql_schema.sql file if using MySQL.
 */
declare(strict_types=1);

// --- Basic CLI Check and Error Handling ---
if (php_sapi_name() !== 'cli') {
    die("Error: This script must be run from the command line (CLI).\n");
}
error_reporting(E_ALL);
ini_set('display_errors', '1');
// Log errors to a specific file in the data directory for setup issues
$logDir = __DIR__ . '/../data';
if (!is_dir($logDir)) { mkdir($logDir, 0775); } // Attempt to create if missing
ini_set('error_log', $logDir . '/php_init_errors.log');

echo "--- Database Initializer Script ---\n";

// --- Load Configuration and Database Connection ---
$configPath = __DIR__ . '/../includes/config.php';
if (!file_exists($configPath)) {
    die("Error: Configuration file not found at {$configPath}\n");
}
require_once $configPath;

// Check if constants are defined
if (!defined('DB_TYPE')) die("Error: DB_TYPE not defined in config.php\n");

$db = null;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    echo "Attempting to connect to database (Type: " . DB_TYPE . ")...\n";
    if (DB_TYPE === 'sqlite') {
        if (!defined('SQLITE_PATH')) die("Error: SQLITE_PATH not defined for SQLite.\n");
        $dbDir = dirname(SQLITE_PATH);
        if (!is_dir($dbDir)) {
             if (!mkdir($dbDir, 0775, true)) { throw new Exception("Failed to create SQLite directory: {$dbDir}"); }
             echo "Created directory: {$dbDir}\n";
        }
         if (!is_writable($dbDir)) { throw new Exception("SQLite directory is not writable: {$dbDir}"); }

        // Delete existing SQLite file if it exists during setup? Optional, can be risky.
        if (file_exists(SQLITE_PATH)) {
            echo "Warning: Existing SQLite database file found at " . SQLITE_PATH . ". It will be overwritten.\n";
            // Uncomment to enable overwrite:
            // if (!unlink(SQLITE_PATH)) { throw new Exception("Could not delete existing SQLite file."); }
            // echo "Deleted existing SQLite file.\n";
        }

        $dsn = 'sqlite:' . SQLITE_PATH;
        $db = new PDO($dsn, null, null, $options);
        $db->exec('PRAGMA foreign_keys = ON;'); // Enable FK constraints for SQLite
        echo "SQLite connection established (" . SQLITE_PATH . ").\n";

    } elseif (DB_TYPE === 'mysql') {
        // For MySQL, we connect WITHOUT specifying the database first to create it if it doesn't exist
         if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_CHARSET')) {
             die("Error: MySQL configuration (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET) missing in config.php\n");
         }
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        echo "Connecting to MySQL server at " . DB_HOST . "...\n";
        $db = new PDO($dsn, DB_USER, DB_PASS, $options);
        echo "MySQL server connection established.\n";

        // Attempt to create the database if it doesn't exist
        try {
             // Use backticks for the database name in case it contains special characters or is a reserved word
             $db->exec("CREATE DATABASE IF NOT EXISTS \`" . DB_NAME . "\` CHARACTER SET " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci");
             echo "Ensured database '" . DB_NAME . "' exists (created if needed).\n";
        } catch (PDOException $e) {
            // Ignore "database exists" errors, re-throw others
            if ($e->getCode() != 'HY000' || !str_contains($e->getMessage(), 'database exists')) {
                 throw $e; // Re-throw other errors
            }
             echo "Database '" . DB_NAME . "' already exists.\n";
        }

        // Now connect specifically to the target database
        $db->exec("USE \`" . DB_NAME . "\`");
        echo "Connected to MySQL database '" . DB_NAME . "'.\n";

    } else {
        die("Error: Unsupported DB_TYPE '" . DB_TYPE . "' specified in configuration.\n");
    }

    if ($db === null) {
        die("Error: Failed to establish database connection after configuration checks.\n");
    }

} catch (PDOException $e) {
    die("Database Connection/Setup Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\nPlease check your config.php settings and ensure the database server is running and accessible.\nFor MySQL, ensure user '" . (defined('DB_USER') ? DB_USER : 'N/A') . "' has privileges to connect and create/use the database '" . (defined('DB_NAME') ? DB_NAME : 'N/A') . "'.\nFor SQLite, check directory permissions for the path containing '" . (defined('SQLITE_PATH') ? SQLITE_PATH : 'N/A') . "'.\n");
} catch (Exception $e) {
     die("General Setup Error: " . $e->getMessage() . "\n");
}

// --- Define Schema SQL (Adapt syntax slightly for SQLite vs MySQL) ---
echo "Defining database schema...\n";

$sqlStatements = [];

// Drop existing tables (optional, use with caution during development)
 $dropTables = false; // Set to true to drop tables on every setup run
 if ($dropTables) {
    echo "Dropping existing tables (if they exist)...\n";
    // Order matters due to foreign keys - drop child tables first
    $sqlStatements[] = "DROP TABLE IF EXISTS rsvps;";
    $sqlStatements[] = "DROP TABLE IF EXISTS events;";
    $sqlStatements[] = "DROP TABLE IF EXISTS users;";
     // Add other tables here if needed (e.g., comments)
 }


// Table: users
$sqlStatements['users'] = "
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT, -- SQLite syntax
    username VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Store hashed passwords (long enough for bcrypt)
    role VARCHAR(20) NOT NULL DEFAULT 'user', -- 'user' or 'admin'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);";
if (DB_TYPE === 'mysql') {
    $sqlStatements['users'] = "
    CREATE TABLE users (
        id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT, -- MySQL syntax
        username VARCHAR(60) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'user',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_users_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_CHARSET . "_unicode_ci;";
}

// Table: events
$sqlStatements['events'] = "
CREATE TABLE events (
    id INTEGER PRIMARY KEY AUTOINCREMENT, -- SQLite syntax
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    phone VARCHAR(30) NULL, -- Optional contact phone
    image_url VARCHAR(512) NULL, -- URL for event image (e.g., from Unsplash)
    category VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Upcoming', -- Upcoming, Ongoing, Completed, Cancelled
    capacity INTEGER UNSIGNED NOT NULL DEFAULT 0, -- 0 means unlimited
    attendees_count INTEGER UNSIGNED NOT NULL DEFAULT 0, -- Current RSVP count
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER NULL, -- Optional: Track admin who created it
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL -- Optional FK
);";
// Indexes for SQLite (created separately)
$sqlStatements['events_indexes_sqlite'] = [
    "CREATE INDEX idx_events_date_time ON events (date, time);",
    "CREATE INDEX idx_events_status ON events (status);",
    "CREATE INDEX idx_events_category ON events (category);"
];

if (DB_TYPE === 'mysql') {
    $sqlStatements['events'] = "
    CREATE TABLE events (
        id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT, -- MySQL syntax
        name VARCHAR(255) NOT NULL,
        date DATE NOT NULL,
        time TIME NOT NULL,
        location VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        phone VARCHAR(30) NULL,
        image_url VARCHAR(512) NULL,
        category VARCHAR(100) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Upcoming',
        capacity INT UNSIGNED NOT NULL DEFAULT 0,
        attendees_count INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_by INT UNSIGNED NULL,
        INDEX idx_events_date_time (date, time),
        INDEX idx_events_status (status),
        INDEX idx_events_category (category),
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_CHARSET . "_unicode_ci;";
     // Remove SQLite specific index array
     unset($sqlStatements['events_indexes_sqlite']);
}


// Table: rsvps (Many-to-Many relationship between users and events)
$sqlStatements['rsvps'] = "
CREATE TABLE rsvps (
    id INTEGER PRIMARY KEY AUTOINCREMENT, -- SQLite syntax
    user_id INTEGER NOT NULL,
    event_id INTEGER NOT NULL,
    rsvp_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, event_id), -- Prevent duplicate RSVPs per user per event
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, -- If user deleted, remove their RSVPs
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE -- If event deleted, remove its RSVPs
);";
// Index for SQLite
$sqlStatements['rsvps_index_sqlite'] = "CREATE INDEX idx_rsvps_event_user ON rsvps (event_id, user_id);";

if (DB_TYPE === 'mysql') {
    $sqlStatements['rsvps'] = "
    CREATE TABLE rsvps (
        id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT, -- MySQL syntax
        user_id INT UNSIGNED NOT NULL,
        event_id INT UNSIGNED NOT NULL,
        rsvp_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_event (user_id, event_id), -- MySQL unique key syntax
        INDEX idx_rsvps_event_id (event_id), -- Index for faster event lookups
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_CHARSET . "_unicode_ci;";
     // Remove SQLite specific index
     unset($sqlStatements['rsvps_index_sqlite']);
}

// Add other tables here (e.g., comments, tickets - not in this basic demo)


// --- Execute Schema SQL ---
echo "Executing schema statements...\n";
$fullSchemaSql = ''; // For MySQL dump

try {
    foreach ($sqlStatements as $key => $sql) {
        if (is_array($sql)) { // Handle array of statements (like SQLite indexes)
            foreach ($sql as $subSql) {
                 echo "Executing: " . substr(trim($subSql), 0, 80) . "...\n"; // Show start of statement
                 $db->exec($subSql);
                 $fullSchemaSql .= $subSql . "\n"; // Append for dump
            }
        } else {
            echo "Executing: " . substr(trim($sql), 0, 80) . "...\n"; // Show start of statement
            $db->exec($sql);
             $fullSchemaSql .= $sql . "\n"; // Append for dump
        }
    }
    echo "Schema creation completed successfully.\n";

} catch (PDOException $e) {
    die("Error executing schema statement: " . $e->getMessage() . "\nSQL that failed (approximate): " . ($sql ?? 'N/A') . "\n");
}


// --- Generate MySQL Schema Dump File (if applicable) ---
if (DB_TYPE === 'mysql') {
     $dumpFile = __DIR__ . '/mysql_schema.sql';
     echo "Generating MySQL schema dump file (structure only) to: {$dumpFile}\n";
     // Basic header for the SQL file
     $dumpContent = "-- Event Ticket Management Pro - MySQL Schema Dump\n";
     $dumpContent .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
     $dumpContent .= "-- Database: " . DB_NAME . "\n";
     $dumpContent .= "-- --------------------------------------------------------\n\n";
     $dumpContent .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
     $dumpContent .= "SET time_zone = \"+00:00\";\n\n";
      $dumpContent .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
     $dumpContent .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
     $dumpContent .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
     $dumpContent .= "/*!40101 SET NAMES " . DB_CHARSET . " */;\n\n";
     $dumpContent .= "--\n-- Database: `" . DB_NAME . "`\n--\n";
     $dumpContent .= "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci;\n";
     $dumpContent .= "USE `" . DB_NAME . "`;\n\n";

     $dumpContent .= "-- --------------------------------------------------------\n\n";

     // Append the executed SQL statements
     $dumpContent .= $fullSchemaSql;

     $dumpContent .= "\n-- --------------------------------------------------------\n";
      $dumpContent .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
     $dumpContent .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
     $dumpContent .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";

     if (file_put_contents($dumpFile, $dumpContent) === false) {
         echo "Warning: Could not write MySQL schema dump file to {$dumpFile}. Check script permissions.\n";
     } else {
         echo "MySQL schema dump file created successfully.\n";
     }
}


echo "\n--- Database Initialization Finished ---\n";
exit(0); // Success exit code

?>
