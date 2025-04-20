<?php
declare(strict_types=1);
require_once '../includes/database.php'; // Handles session start
require_once '../includes/functions.php';

// Authorization: Only admins can delete events
requireAuth(true); // Require admin role

// Security: Only accept POST requests for deletion to prevent accidental deletion via GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('error', 'Invalid request method. Event deletion must be done via POST.');
    redirect('admin.php');
}

// --- CSRF Token Validation ---
$submittedToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($submittedToken)) {
    setFlashMessage('error', 'Invalid security token. Action prevented.');
    error_log("CSRF token mismatch for event delete attempt.");
    redirect('admin.php'); // Redirect back
}

// --- Input Validation ---
// Get event ID from POST data, ensure it's a positive integer
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$eventId) {
    setFlashMessage('error', 'Invalid or missing Event ID provided for deletion.');
    redirect('admin.php');
}

// --- Perform Deletion ---
$eventName = 'Event ID ' . $eventId; // Default name for messages if fetch fails

try {
    // Start transaction - ensures atomicity if we delete from multiple tables (RSVPs handled by cascade)
    $db->beginTransaction();

    // Fetch event name *before* deleting for user feedback
    $stmtName = $db->prepare("SELECT name FROM events WHERE id = :id");
    $stmtName->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmtName->execute();
    $fetchedName = $stmtName->fetchColumn();

    if ($fetchedName) {
        $eventName = $fetchedName; // Use the actual name in messages
    } else {
        // Event doesn't exist, maybe already deleted?
        throw new Exception("Event with ID {$eventId} not found. Cannot delete.");
    }

    // Delete the event. Foreign key constraints with ON DELETE CASCADE in the schema
    // should handle deleting related rsvps automatically.
    // If comments or other related tables exist without cascade, delete them here first.
    // Example: $db->prepare("DELETE FROM comments WHERE event_id = :id")->execute([':id' => $eventId]);

    $stmtDelete = $db->prepare("DELETE FROM events WHERE id = :id");
    $stmtDelete->bindParam(':id', $eventId, PDO::PARAM_INT);
    $deleted = $stmtDelete->execute();

    // Check if deletion was successful AND if any row was actually affected
    if ($deleted && $stmtDelete->rowCount() > 0) {
        // Deletion successful
        $db->commit(); // Commit the transaction
        unsetCsrfToken(); // Important: Invalidate the token after successful action
        setFlashMessage('success', 'Event "' . escape($eventName) . '" and associated RSVPs have been successfully deleted.');
        error_log("Event Deleted: ID [$eventId], Name ['$eventName'] by Admin ID [{$_SESSION['user_id']}]"); // Audit log
    } elseif ($deleted) {
         // Execute succeeded but rowCount was 0 (event didn't exist anymore?) - covered by initial check mostly
        $db->rollBack(); // Rollback transaction
        setFlashMessage('warning', "Event '" . escape($eventName) . "' was not found or already deleted.");
    } else {
         // Execute() returned false - indicates an error during deletion itself
         $db->rollBack();
         $errorInfo = $stmtDelete->errorInfo();
         error_log("Event Delete Failed (PDO execute returned false): ID [$eventId] - Error: " . ($errorInfo[2] ?? 'Unknown error'));
         setFlashMessage('error', 'Failed to delete event "' . escape($eventName) . '". A database error occurred.');
    }

} catch (PDOException $e) {
    // Catch database specific errors during the process
    if ($db->inTransaction()) {
        $db->rollBack(); // Rollback on error
    }
    error_log("Event Delete PDO Error: ID [$eventId] - " . $e->getMessage());
    // Provide a more specific message if possible, e.g., foreign key issues if cascade wasn't set up
     if ((defined('DB_TYPE') && DB_TYPE === 'mysql' && $e->getCode() == '1451') || // MySQL FK constraint
         (defined('DB_TYPE') && DB_TYPE === 'sqlite' && $e->getCode() == 19 && str_contains($e->getMessage(), 'FOREIGN KEY constraint failed')) // SQLite FK constraint
        ) {
        setFlashMessage('error', "Cannot delete event '" . escape($eventName) . "'. It might still be referenced by other records (check database constraints if ON DELETE CASCADE is missing).");
    } else {
        setFlashMessage('error', 'A critical database error occurred while attempting to delete the event.');
    }
} catch (Exception $e) {
    // Catch logical errors (like event not found exception)
      if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Event Delete General Error: ID [$eventId] - " . $e->getMessage());
     setFlashMessage('error', escape($e->getMessage())); // Show the specific logic error message
}

// Redirect back to the admin event list regardless of outcome
redirect('admin.php');
?>
