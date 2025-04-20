<?php
/**
 * Insert Sample Data Script
 *
 * Populates the database with some initial data for demonstration purposes.
 * Reads configuration from includes/config.php.
 * Should be run from the command line via scripts/setup.sh AFTER initialize_db.php.
 */
declare(strict_types=1);

// --- Basic CLI Check and Error Handling ---
if (php_sapi_name() !== 'cli') {
    die("Error: This script must be run from the command line (CLI).\n");
}
error_reporting(E_ALL);
ini_set('display_errors', '1');
$logDir = __DIR__ . '/../data';
if (!is_dir($logDir)) { mkdir($logDir, 0775); }
ini_set('error_log', $logDir . '/php_sample_data_errors.log');

echo "--- Sample Data Inserter Script ---\n";

// --- Load Configuration and Database Connection ---
$configPath = __DIR__ . '/../includes/config.php';
if (!file_exists($configPath)) { die("Error: Configuration file not found at {$configPath}\n"); }
require_once $configPath;

if (!defined('DB_TYPE')) die("Error: DB_TYPE not defined in config.php\n");

$db = null;
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

try {
    echo "Connecting to database (Type: " . DB_TYPE . ")...\n";
    if (DB_TYPE === 'sqlite') {
        if (!defined('SQLITE_PATH') || !file_exists(SQLITE_PATH)) die("Error: SQLITE_PATH not defined or DB file doesn't exist. Run initialize_db.php first.\n");
        $dsn = 'sqlite:' . SQLITE_PATH;
        $db = new PDO($dsn, null, null, $options);
        $db->exec('PRAGMA foreign_keys = ON;');
    } elseif (DB_TYPE === 'mysql') {
        if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_CHARSET')) die("Error: MySQL config missing.\n");
        // Connect TO the specific database this time
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $db = new PDO($dsn, DB_USER, DB_PASS, $options);
    } else {
        die("Error: Unsupported DB_TYPE '" . DB_TYPE . "'.\n");
    }
    echo "Database connection successful.\n";

} catch (PDOException $e) {
    die("Database Connection Error during sample data insertion: " . $e->getMessage() . "\nEnsure initialize_db.php ran successfully and config.php is correct.\n");
} catch (Exception $e) {
     die("General Setup Error: " . $e->getMessage() . "\n");
}

// --- Sample Data Definitions ---
$users = [
    [
        'username' => 'admin',
        // !! INSECURE DEFAULT PASSWORD - FOR DEMO ONLY !!
        // !! CHANGE THIS IMMEDIATELY IN A REAL SCENARIO !!
        'password' => password_hash('adminpass', PASSWORD_DEFAULT),
        'role' => 'admin'
    ],
    [
        'username' => 'testuser',
        'password' => password_hash('userpass', PASSWORD_DEFAULT),
        'role' => 'user'
    ]
];

// Get current date + offsets for sample events
$today = new DateTime();
$oneWeek = new DateInterval('P7D');
$twoWeeks = new DateInterval('P14D');
$oneMonth = new DateInterval('P1M');
$pastDate = (new DateTime())->sub(new DateInterval('P10D')); // For a completed event

$events = [
    [
        'name' => 'Tech Conference 2024',
        'date' => (clone $today)->add($oneWeek)->format('Y-m-d'),
        'time' => '09:00:00',
        'location' => 'Grand Convention Center, Room A',
        'description' => "Join us for the annual Tech Conference featuring talks on AI, blockchain, and web development. Keynote by industry leaders.\nNetworking opportunities available.",
        'phone' => '1-800-TECHCONF',
        'image_url' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?ixlib=rb-4.0.3&q=85&fm=jpg&crop=entropy&cs=srgb&w=600', // Example Unsplash URL
        'category' => 'Conference',
        'status' => 'Upcoming',
        'capacity' => 250,
        'attendees_count' => 0, // Start with 0
        'created_by' => 1 // Assuming admin user ID is 1
    ],
    [
        'name' => 'Live Jazz Night',
        'date' => (clone $today)->add($twoWeeks)->format('Y-m-d'),
        'time' => '20:00:00',
        'location' => 'The Blue Note Cafe',
        'description' => "An evening of smooth jazz performed by the renowned 'Night Owls Quartet'. Enjoy great music, food, and drinks.",
        'phone' => '555-JAZZ-CLUB',
        'image_url' => 'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?ixlib=rb-4.0.3&q=85&fm=jpg&crop=entropy&cs=srgb&w=600',
        'category' => 'Music Concert',
        'status' => 'Upcoming',
        'capacity' => 80,
        'attendees_count' => 0,
        'created_by' => 1
    ],
    [
        'name' => 'Community Charity Run',
        'date' => (clone $today)->add($oneMonth)->format('Y-m-d'),
        'time' => '08:00:00',
        'location' => 'City Park - Starting Line',
        'description' => "Participate in our 5k charity run/walk to support local schools. Fun for the whole family! Refreshments provided.",
        'phone' => null,
        'image_url' => 'https://images.unsplash.com/photo-1475666675596-cca1127f5d7a?ixlib=rb-4.0.3&q=85&fm=jpg&crop=entropy&cs=srgb&w=600',
        'category' => 'Charity',
        'status' => 'Upcoming',
        'capacity' => 0, // Unlimited capacity
        'attendees_count' => 0,
        'created_by' => 1
    ],
     [
        'name' => 'Web Dev Workshop',
        'date' => $pastDate->format('Y-m-d'),
        'time' => '10:00:00',
        'location' => 'Online via Zoom',
        'description' => "A hands-on workshop covering modern JavaScript frameworks (React, Vue). Led by expert developers.",
        'phone' => null,
        'image_url' => 'https://images.unsplash.com/photo-1542744095-291d1f67b221?ixlib=rb-4.0.3&q=85&fm=jpg&crop=entropy&cs=srgb&w=600',
        'category' => 'Workshop',
        'status' => 'Completed', // Past event
        'capacity' => 50,
        'attendees_count' => 45, // Example attendees for past event
        'created_by' => 1
    ]
];

// --- Insert Data ---
try {
    echo "Inserting sample users...\n";
    $userSql = "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)";
    $userStmt = $db->prepare($userSql);
    foreach ($users as $user) {
        // Check if user already exists (simple check for demo)
        $checkStmt = $db->prepare("SELECT id FROM users WHERE username = :username");
        $checkStmt->execute([':username' => $user['username']]);
        if ($checkStmt->fetch()) {
             echo "User '{$user['username']}' already exists, skipping.\n";
        } else {
            $userStmt->execute($user);
            echo "Inserted user: {$user['username']}\n";
        }
    }

    echo "\nInserting sample events...\n";
    $eventSql = "INSERT INTO events (name, date, time, location, description, phone, image_url, category, status, capacity, attendees_count, created_by)
                 VALUES (:name, :date, :time, :location, :description, :phone, :image_url, :category, :status, :capacity, :attendees_count, :created_by)";
    $eventStmt = $db->prepare($eventSql);
    foreach ($events as $event) {
         // Simple check if event name already exists (adjust logic if needed)
         $checkStmt = $db->prepare("SELECT id FROM events WHERE name = :name AND date = :date");
         $checkStmt->execute([':name' => $event['name'], ':date' => $event['date']]);
         if ($checkStmt->fetch()) {
             echo "Event '{$event['name']}' on {$event['date']} already exists, skipping.\n";
         } else {
            $eventStmt->execute($event);
            echo "Inserted event: {$event['name']}\n";
         }
    }

     // Optional: Add a sample RSVP
     echo "\nAdding sample RSVP (testuser for Tech Conference)...\n";
     $userIdToRsvp = 2; // Assuming 'testuser' gets ID 2
     $eventIdToRsvp = 1; // Assuming 'Tech Conference' gets ID 1

     // Verify IDs exist before attempting RSVP
     $userCheck = $db->query("SELECT COUNT(*) FROM users WHERE id = $userIdToRsvp")->fetchColumn();
     $eventCheck = $db->query("SELECT COUNT(*) FROM events WHERE id = $eventIdToRsvp")->fetchColumn();

     if ($userCheck > 0 && $eventCheck > 0) {
         $rsvpSql = "INSERT OR IGNORE INTO rsvps (user_id, event_id) VALUES (?, ?)"; // Use IGNORE for SQLite/MySQL to avoid error if already exists
         if (DB_TYPE === 'mysql') { $rsvpSql = "INSERT IGNORE INTO rsvps (user_id, event_id) VALUES (?, ?)"; }
         $rsvpStmt = $db->prepare($rsvpSql);
         $rsvpStmt->execute([$userIdToRsvp, $eventIdToRsvp]);

         // Update attendees count for the event if RSVP was newly inserted
         if ($rsvpStmt->rowCount() > 0) {
             $updateCountSql = "UPDATE events SET attendees_count = attendees_count + 1 WHERE id = ?";
             $db->prepare($updateCountSql)->execute([$eventIdToRsvp]);
             echo "Sample RSVP added for user ID $userIdToRsvp to event ID $eventIdToRsvp and attendee count updated.\n";
         } else {
              echo "Sample RSVP already exists or failed to insert, attendee count not updated.\n";
         }
     } else {
         echo "Skipping sample RSVP: User ID $userIdToRsvp or Event ID $eventIdToRsvp not found.\n";
     }


    echo "\nSample data insertion completed successfully.\n";

} catch (PDOException $e) {
    die("Error inserting sample data: " . $e->getMessage() . "\n");
}

echo "\n--- Sample Data Insertion Finished ---\n";
exit(0); // Success
?>
