<?php
declare(strict_types=1);
require_once '../includes/database.php'; // Handles session start
require_once '../includes/functions.php';

// --- Authorization ---
// Require user to be logged in AND be an admin
requireAuth(true); // The 'true' argument requires admin role

$pageTitle = "Admin Panel - Manage Events";
$events = []; // Initialize event list

try {
    // Fetch all events for the admin panel, maybe newest first or by status
    // Order by status priority then date/time
    $stmt = $db->query("
        SELECT * FROM events
        ORDER BY CASE status
                    WHEN 'Ongoing' THEN 1
                    WHEN 'Upcoming' THEN 2
                    WHEN 'Cancelled' THEN 3
                    WHEN 'Completed' THEN 4
                    ELSE 5
                 END ASC,
                 date DESC, time DESC
        ");
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Admin Panel: Fetch Events Error - " . $e->getMessage());
    setFlashMessage('error', 'Could not load the list of events due to a database issue.');
    // Let the page render with the error message displayed by the header template
}

$csrfToken = generateCsrfToken(); // For delete forms

// --- Render Page ---
renderTemplate('header', ['pageTitle' => $pageTitle]);
?>

    <h1 class="page-title"><?php echo escape($pageTitle); ?></h1>
    <p class="lead">Oversee and manage all events in the system.</p>

    <div class="admin-actions mb-3">
        <a href="<?php echo baseUrl('event_edit.php'); // Link to add new event page (edit page without ID) ?>">
           <button type="button" class="action-button action-add">
             <i class="fas fa-plus" aria-hidden="true"></i> Add New Event
           </button>
        </a>
        <?php /* Add other admin overview links here if needed, e.g., User Management */ ?>
        <?php /* <a href="<?php echo baseUrl('admin_users.php'); ?>"><button class="action-button action-view">Manage Users</button></a> */ ?>
    </div>

    <?php if (empty($events) && !isset($_SESSION['flash_message'])): ?>
        <div class="message info">
            No events found in the system yet.
            <a href="<?php echo baseUrl('event_edit.php'); ?>" style="text-decoration: underline; color: inherit;">Create the first event now!</a>
        </div>
    <?php elseif (!empty($events)): ?>
        <p>Total events found: <strong><?php echo count($events); ?></strong></p>
        <div class="event-container admin-event-list">
            <?php foreach ($events as $event):
                 $eventId = (int)$event['id'];
            ?>
                <div class="event-card admin-card status-<?php echo strtolower(escape($event['status'])); ?>">
                    <h2 class="event-card-title"><?php e($event['name']); ?></h2>
                    <div class="event-card-details">
                        <p><i class="fas fa-calendar-alt" aria-hidden="true"></i> <span><?php echo formatDate($event['date'], 'M j, Y'); ?> @ <?php echo formatDate($event['time'], 'g:i A'); ?></span></p>
                        <p><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <span><?php e($event['location']); ?></span></p>
                        <p><i class="fas fa-tags" aria-hidden="true"></i> <span><?php e($event['category']); ?></span></p>
                        <p><i class="fas fa-check-square" aria-hidden="true"></i> Status: <strong class="event-status-<?php echo strtolower(escape($event['status'])); ?>"><?php e($event['status']); ?></strong></p>
                        <p><i class="fas fa-users" aria-hidden="true"></i> Capacity: <span><?php echo ((int)$event['capacity'] === 0 ? 'Unlimited' : (int)$event['capacity']); ?></span></p>
                        <p><i class="fas fa-user-check" aria-hidden="true"></i> Attendees: <span><?php echo (int)$event['attendees_count']; ?></span></p>
                    </div>

                    <div class="event-card-actions admin-card-actions">
                         <a href="<?php echo baseUrl('event_view.php?id=' . $eventId); ?>" class="action-button action-view" title="View Event Details">
                           <i class="fas fa-eye" aria-hidden="true"></i> View
                        </a>
                         <a href="<?php echo baseUrl('event_edit.php?id=' . $eventId); ?>" class="action-button action-edit" title="Edit Event">
                            <i class="fas fa-edit" aria-hidden="true"></i> Edit
                        </a>
                         <?php // Use a form for delete action (more secure than GET link) ?>
                         <form method="POST" action="<?php echo baseUrl('event_delete.php'); ?>" class="inline-form">
                            <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                            <input type="hidden" name="csrf_token" value="<?php e($csrfToken); ?>">
                            <button type="submit" class="action-button action-delete" title="Delete Event"
                                    data-confirm="Permanently delete the event '<?php e($event['name']); ?>'? This will also remove associated RSVPs. This action cannot be undone.">
                               <i class="fas fa-trash-alt" aria-hidden="true"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php
renderTemplate('footer');
?>
