<?php
require_once '../includes/db.php'; // Relative path
require_once '../includes/functions.php';

requireAdmin(); // Only admins

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid request method for delete.'];
    redirect('admin/index.php'); // Back to admin list
}

// Get and validate the event ID from POST data
// Simplified - no CSRF token validation here as requested
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

// Check if the event ID is valid
if (!$eventId || $eventId <= 0) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid event ID specified.'];
    redirect('admin/index.php');
}

// Optional: Fetch the event name before deleting for a more informative message
$eventName = "Event ID {$eventId}"; // Default message
try {
    $stmtName = $db->prepare("SELECT name FROM events WHERE id = ?");
    $stmtName->execute([$eventId]);
    $nameResult = $stmtName->fetchColumn(); // Fetch the name column
    if ($nameResult) {
        $eventName = $nameResult; // Use the actual name if found
    }
} catch (PDOException $e) {
    // Ignore error fetching name, proceed with deletion using the ID
    error_log("Admin Delete - Error fetching name for ID [$eventId]: " . $e->getMessage());
}

// Attempt to delete the event
try {
    // Assuming 'ON DELETE CASCADE' is set for related tables (like rsvps) in the database schema
    $stmtDelete = $db->prepare("DELETE FROM events WHERE id = ?");
    $success = $stmtDelete->execute([$eventId]);

    if ($success && $stmtDelete->rowCount() > 0) {
        // Deletion successful and at least one row was affected
        $_SESSION['message'] = ['type' => 'success', 'text' => "Event '" . escape($eventName) . "' was deleted."];
        error_log("Admin deleted Event ID: {$eventId} ('" . escape($eventName) . "')");
    } elseif ($success) {
        // Deletion command executed successfully, but no rows were affected (event likely already deleted)
        $_SESSION['message'] = ['type' => 'warning', 'text' => "Event '" . escape($eventName) . "' not found or already deleted."];
    } else {
        // The execute call failed
        $_SESSION['message'] = ['type' => 'error', 'text' => "Could not delete event '" . escape($eventName) . "'."];
        error_log("Admin Delete Failed (Execute returned false): ID [$eventId]");
    }

} catch (PDOException $e) {
    // Database error during deletion
    error_log("Admin Delete Error: ID [$eventId] - " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'error', 'text' => "Database error while deleting event. Check logs."];
}

// Redirect back to the admin index page regardless of outcome
redirect('admin/index.php');
?>