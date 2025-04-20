<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin(); // Must be logged in to RSVP/Cancel

// Simple security: Non-admins only for RSVP actions
if (isAdmin()) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Administrators cannot perform this action.'];
     redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
     $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid request method.'];
     redirect('events.php');
}

$action = $_POST['action'] ?? null; // 'rsvp' or 'cancel'
$eventId = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user_id']; // Assumed set by requireLogin

// Where to redirect back to? Default to event details page.
$redirectTo = $_POST['redirect_to'] ?? "event_details.php?id={$eventId}";

if (!$eventId || !$action || !in_array($action, ['rsvp', 'cancel'])) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid RSVP parameters.'];
    redirect('events.php');
}


try {
     // Fetch event details (name, status, capacity)
     $stmtEvent = $db->prepare("SELECT name, status, capacity, attendees_count FROM events WHERE id = ?");
     $stmtEvent->execute([$eventId]);
     $event = $stmtEvent->fetch();

     if (!$event) {
         throw new Exception("Event not found.");
    }

     $eventName = $event['name'];

     // Check if event is active for RSVP changes
     if (!in_array($event['status'], ['Upcoming', 'Ongoing'])) {
         throw new Exception("RSVP actions are not allowed for events that are '{$event['status']}'.");
     }


     if ($action === 'rsvp') {
         // --- Attempt to RSVP ---
         // Check if already RSVP'd
          $stmtCheck = $db->prepare("SELECT 1 FROM rsvps WHERE user_id = ? AND event_id = ?");
         $stmtCheck->execute([$userId, $eventId]);
          if ($stmtCheck->fetch()) {
             $_SESSION['message'] = ['type' => 'info', 'text' => "You are already RSVP'd for '" . escape($eventName) . "'."];
         } else {
             // Check capacity
              if ($event['attendees_count'] >= $event['capacity']) {
                  throw new Exception("Sorry, '" . escape($eventName) . "' is currently full.");
              }
             // Perform RSVP insert and update count within a transaction for safety
              $db->beginTransaction();
              $stmtInsert = $db->prepare("INSERT INTO rsvps (user_id, event_id) VALUES (?, ?)");
              $stmtInsert->execute([$userId, $eventId]);
             $stmtUpdate = $db->prepare("UPDATE events SET attendees_count = attendees_count + 1 WHERE id = ?");
             $stmtUpdate->execute([$eventId]);
             $db->commit();
             $_SESSION['message'] = ['type' => 'success', 'text' => "Successfully RSVP'd for '" . escape($eventName) . "'."];
             error_log("RSVP Success: User[$userId] Event[$eventId]");
         }

     } elseif ($action === 'cancel') {
         // --- Attempt to Cancel RSVP ---
         // Check if they have an RSVP first
         $stmtCheck = $db->prepare("SELECT id FROM rsvps WHERE user_id = ? AND event_id = ?");
         $stmtCheck->execute([$userId, $eventId]);
         $rsvpId = $stmtCheck->fetchColumn();

         if (!$rsvpId) {
            $_SESSION['message'] = ['type' => 'info', 'text' => "You were not RSVP'd for '" . escape($eventName) . "'."];
         } else {
             // Perform delete and update count within a transaction
              $db->beginTransaction();
              $stmtDelete = $db->prepare("DELETE FROM rsvps WHERE id = ?");
              $stmtDelete->execute([$rsvpId]);
             // Ensure count doesn't go below zero
             $stmtUpdate = $db->prepare("UPDATE events SET attendees_count = MAX(0, attendees_count - 1) WHERE id = ?");
             $stmtUpdate->execute([$eventId]);
              $db->commit();
              $_SESSION['message'] = ['type' => 'success', 'text' => "Your RSVP for '" . escape($eventName) . "' has been cancelled."];
              error_log("RSVP Cancel: User[$userId] Event[$eventId]");
         }
    }

} catch (PDOException $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    error_log("RSVP Handler PDO Error: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'error', 'text' => "A database error occurred. Please try again."];
} catch (Exception $e) {
    // Catch specific logic errors (e.g., full, not found)
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); } // Rollback if transaction was started
    error_log("RSVP Handler Error: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'error', 'text' => $e->getMessage()];
}


// Redirect back (either to event page or wherever specified)
redirect($redirectTo);
?>
