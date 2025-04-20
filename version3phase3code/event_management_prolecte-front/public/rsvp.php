<?php
declare(strict_types=1);
require_once '../includes/database.php'; // Handles session start
require_once '../includes/functions.php';

// --- Authorization & Pre-checks ---
// Require user to be logged in, but NOT an admin (admins don't RSVP)
requireAuth(); // Ensures user is logged in
if (isAdmin()) {
    setFlashMessage('warning', 'Administrators manage events, they do not RSVP.');
    redirect($_POST['redirect_to'] ?? 'admin.php'); // Redirect admin back
}

// Only allow POST requests for changing state (RSVPing or cancelling)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Silently redirect for non-POST requests to avoid errors/direct access
    redirect('index.php');
}

// --- CSRF Token Validation ---
$submittedToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($submittedToken)) {
    setFlashMessage('error', 'Invalid security token. RSVP action failed.');
    // Determine where to redirect - prioritize posted value, then HTTP_REFERER, fallback to index
    $redirectUrl = filter_input(INPUT_POST, 'redirect_to', FILTER_SANITIZE_URL);
    redirect($redirectUrl ?: ($_SERVER['HTTP_REFERER'] ?? baseUrl('index.php')));
}

// --- Input Validation ---
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS); // Expect 'rsvp' or 'cancel'
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$userId = (int)$_SESSION['user_id']; // Assumed to be integer from login (requireAuth ensures it)

// Determine redirect URL (sanitize it)
$rawRedirectTo = $_POST['redirect_to'] ?? '';
// Basic validation: ensure it's a relative path or points to our base URL to prevent open redirect
$redirectTo = baseUrl('index.php'); // Default fallback
if (!empty($rawRedirectTo) && (str_starts_with($rawRedirectTo, '/') || str_starts_with($rawRedirectTo, BASE_URL))) {
    // Sanitize the potentially valid URL
    $redirectTo = filter_var($rawRedirectTo, FILTER_SANITIZE_URL);
     // Double check if filter removed too much or resulted in invalid URL
     if (filter_var($redirectTo, FILTER_VALIDATE_URL) === false && !str_starts_with($redirectTo, '/')) {
        $redirectTo = baseUrl('index.php'); // Fallback if sanitization breaks it
     }
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    // Fallback to referer if 'redirect_to' is missing/invalid, with similar checks
    $referer = filter_var($_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL);
    if (str_starts_with($referer, BASE_URL)) { // Only trust referer if it's from our site
        $redirectTo = $referer;
    }
}


// Basic validation of required inputs
if (!$eventId || !$action || !in_array($action, ['rsvp', 'cancel'])) {
    setFlashMessage('error', 'Invalid RSVP request parameters.');
    redirect($redirectTo);
}

// --- Process RSVP/Cancel Action ---
$eventName = "Event ID " . $eventId; // Default for messages if event fetch fails

try {
    // Use a transaction to ensure atomicity of checking capacity/RSVP status and updating
    $db->beginTransaction();

    // Lock the event row to prevent race conditions when checking/updating capacity
    // This is more critical in high-concurrency environments.
    // MySQL/PostgreSQL syntax: ' FOR UPDATE'. SQLite transactions provide good locking for typical web app load.
    $lockSuffix = (DB_TYPE === 'mysql' || DB_TYPE === 'pgsql') ? ' FOR UPDATE' : ''; // Adjust for DB specifics
    $stmtEvent = $db->prepare("SELECT name, capacity, attendees_count, status FROM events WHERE id = :id" . $lockSuffix);
    $stmtEvent->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmtEvent->execute();
    $event = $stmtEvent->fetch();

    // Check if event exists
    if (!$event) {
        throw new Exception("Event not found."); // This will rollback the transaction
    }
    $eventName = $event['name']; // Update for better user messages

    // Check event status - cannot RSVP/Cancel for completed or cancelled events
     if (!in_array($event['status'], ['Upcoming', 'Ongoing'])) {
          throw new Exception("You can only manage RSVPs for 'Upcoming' or 'Ongoing' events. This event is currently '{$event['status']}'.");
     }

    // --- Perform Action based on 'action' parameter ---
    if ($action === 'rsvp') {
        // 1. Check if user is already RSVP'd (use COUNT for efficiency)
         $stmtCheck = $db->prepare("SELECT COUNT(*) FROM rsvps WHERE user_id = :user_id AND event_id = :event_id");
         $stmtCheck->execute([':user_id' => $userId, ':event_id' => $eventId]);
         if ($stmtCheck->fetchColumn() > 0) {
              // User already RSVP'd, treat as success/info, no DB change needed
              setFlashMessage('info', "You are already RSVP'd for '" . escape($eventName) . "'.");
              $db->commit(); // Commit transaction (no changes made)
              unsetCsrfToken(); // Still unset token after check
              redirect($redirectTo);
              exit; // Ensure script stops here
         }

         // 2. Check capacity (if capacity > 0)
         if ($event['capacity'] > 0 && $event['attendees_count'] >= $event['capacity']) {
            // Event is full
            throw new Exception("Sorry, '" . escape($eventName) . "' is now full. Cannot RSVP.");
         }

        // 3. Insert the new RSVP record
        $stmtInsert = $db->prepare("INSERT INTO rsvps (user_id, event_id, rsvp_time) VALUES (:user_id, :event_id, datetime('now'))");
         // Adjust datetime('now') for MySQL: NOW()
         if (DB_TYPE === 'mysql') {
             $stmtInsert = $db->prepare("INSERT INTO rsvps (user_id, event_id, rsvp_time) VALUES (:user_id, :event_id, NOW())");
         }
        $stmtInsert->execute([':user_id' => $userId, ':event_id' => $eventId]);

        // 4. Increment the attendees count on the event record
        $stmtUpdateCount = $db->prepare("UPDATE events SET attendees_count = attendees_count + 1 WHERE id = :id");
        $stmtUpdateCount->execute([':id' => $eventId]);

        // If all steps succeeded
        setFlashMessage('success', "Successfully RSVP'd for '" . escape($eventName) . "'! See you there!");
        error_log("RSVP Success: User [$userId] for Event [$eventId] ('$eventName')");

    } elseif ($action === 'cancel') {
         // 1. Check if user actually has an RSVP to cancel (fetch the RSVP ID)
         $stmtCheck = $db->prepare("SELECT id FROM rsvps WHERE user_id = :user_id AND event_id = :event_id LIMIT 1");
         $stmtCheck->execute([':user_id' => $userId, ':event_id' => $eventId]);
         $rsvpId = $stmtCheck->fetchColumn();

         if ($rsvpId === false) {
              // User was not RSVP'd, treat as success/info, no DB change needed
              setFlashMessage('info', "You were not RSVP'd for '" . escape($eventName) . "', so no cancellation was needed.");
              $db->commit(); // Commit transaction (no changes made)
              unsetCsrfToken(); // Still unset token
              redirect($redirectTo);
              exit; // Ensure script stops
         }

        // 2. Delete the RSVP record using its primary key (more efficient)
        $stmtDelete = $db->prepare("DELETE FROM rsvps WHERE id = :rsvp_id");
        $stmtDelete->execute([':rsvp_id' => $rsvpId]);

        // 3. Decrement the attendees count (ensure it doesn't go below zero)
        $stmtUpdateCount = $db->prepare("UPDATE events SET attendees_count = CASE WHEN attendees_count > 0 THEN attendees_count - 1 ELSE 0 END WHERE id = :id");
        $stmtUpdateCount->execute([':id' => $eventId]);

        // If deletion and update succeeded
        setFlashMessage('success', "Your RSVP for '" . escape($eventName) . "' has been successfully cancelled.");
        error_log("RSVP Cancelled: User [$userId] for Event [$eventId] ('$eventName')");
    }

    // Commit transaction if all database operations within the action block succeeded
    $db->commit();
    unsetCsrfToken(); // Unset token after successful action completion

} catch (PDOException $e) {
    // Database error occurred during the transaction
    if ($db->inTransaction()) $db->rollBack(); // Rollback any partial changes
    error_log("RSVP PDO Error: User [$userId], Event [$eventId], Action [$action] - " . $e->getMessage());
    setFlashMessage('error', 'A database error occurred. Your RSVP could not be processed at this time.');
} catch (Exception $e) {
     // Logical error caught (e.g., capacity full, event not found, bad status)
     if ($db->inTransaction()) $db->rollBack(); // Rollback transaction
     error_log("RSVP Logic Error: User [$userId], Event [$eventId], Action [$action] - " . $e->getMessage());
     // Show the specific logical error message directly to the user (it's designed to be user-friendly)
     setFlashMessage('error', escape($e->getMessage()));
}

// Redirect back to the originating page (or index as fallback)
redirect($redirectTo);
?>
